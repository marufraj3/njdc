<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContentItem;
use App\Models\ContentRevision;
use App\Models\ContentType;
use App\Models\MediaAttachment;
use App\Rules\PublicPathAvailable;
use App\Services\ContentWorkflow;
use App\Services\MediaManager;
use App\Support\HtmlSanitizer;
use App\Support\PublicPath;
use Illuminate\Http\Request;

class ContentItemController extends Controller
{
    public function index(Request $request)
    {
        $types=ContentType::where('is_active',true)->orderBy('name_bn')->get();
        $items=ContentItem::with('type')->when($request->type,fn($q,$v)=>$q->where('content_type_id',$v))->when($request->status,fn($q,$v)=>$q->where('status',$v))->latest('updated_at')->paginate(25)->withQueryString();
        return view('admin.content.index',compact('types','items'));
    }
    public function create(Request $request) { return view('admin.content.form',['item'=>new ContentItem,'types'=>ContentType::where('is_active',true)->orderBy('name_bn')->get(),'selectedType'=>$request->integer('type')]); }
    public function store(Request $request, ContentWorkflow $workflow, HtmlSanitizer $sanitizer, MediaManager $media)
    {
        $item=$workflow->saveDraft(new ContentItem,$this->validated($request,$sanitizer),$request->user());
        $this->addFiles($request,$item,$media);$this->advance($item,$request,$workflow);
        return redirect()->route('admin.content.index')->with('status','কনটেন্ট সংরক্ষিত হয়েছে।');
    }
    public function edit(ContentItem $content) { $content->load('attachments.media');return view('admin.content.form',['item'=>$content,'types'=>ContentType::where('is_active',true)->orderBy('name_bn')->get(),'selectedType'=>null]); }
    public function update(Request $request, ContentItem $content, ContentWorkflow $workflow, HtmlSanitizer $sanitizer, MediaManager $media)
    {
        $result=$workflow->saveDraft($content,$this->validated($request,$sanitizer,$content),$request->user(),$request->input('note'));
        $removeIds=MediaAttachment::where('attachable_type',$content->getMorphClass())->where('attachable_id',$content->id)->whereIn('id',(array)$request->input('remove_attachments',[]))->pluck('id')->all();
        if($result instanceof ContentItem){
            $this->addFiles($request,$result,$media);
            $removedMediaIds=MediaAttachment::whereIn('id',$removeIds)->pluck('media_file_id');
            MediaAttachment::whereIn('id',$removeIds)->delete();
            foreach($removedMediaIds as $mediaId)$workflow->cleanUnusedMedia($mediaId);
        } else $this->stageRevisionMedia($request,$result,$media,$removeIds);
        $this->advance($result,$request,$workflow);
        return redirect()->route('admin.content.index')->with('status',$result instanceof ContentRevision?'সংশোধন অনুমোদনের জন্য সংরক্ষিত হয়েছে; প্রকাশিত সংস্করণ অপরিবর্তিত আছে।':'কনটেন্ট হালনাগাদ হয়েছে।');
    }
    public function submit(Request $request, ContentItem $content, ContentWorkflow $workflow){$workflow->submit($content,$request->user());return back()->with('status','অনুমোদনের জন্য জমা হয়েছে।');}
    public function publish(Request $request, ContentItem $content, ContentWorkflow $workflow){$workflow->publish($content,$request->user());return back()->with('status','কনটেন্ট প্রকাশিত হয়েছে।');}
    public function reject(Request $request, ContentItem $content, ContentWorkflow $workflow){$request->validate(['note'=>'required|string|max:1000']);$workflow->reject($content,$request->user(),$request->note);return back()->with('status','কনটেন্ট ফেরত পাঠানো হয়েছে।');}
    public function destroy(ContentItem $content){$content->delete();return back()->with('status','কনটেন্ট ট্র্যাশে পাঠানো হয়েছে।');}
    public function destroyAttachment(ContentItem $content, MediaAttachment $attachment, ContentWorkflow $workflow){abort_unless($attachment->attachable_type===$content->getMorphClass()&&$attachment->attachable_id===$content->id,404);$mediaId=$attachment->media_file_id;$attachment->delete();$workflow->cleanUnusedMedia($mediaId);return back()->with('status','সংযুক্তি সরানো হয়েছে।');}

    private function validated(Request $request, HtmlSanitizer $sanitizer, ?ContentItem $item=null):array
    {
        $request->merge(['path' => PublicPath::normalize($request->input('path'))]);
        $data=$request->validate(['content_type_id'=>'required|exists:content_types,id','path'=>['required','string','max:512',new PublicPathAvailable('content_items', $item?->id)],'title_bn'=>'required|string|max:255','title_en'=>'nullable|string|max:255','summary_bn'=>'nullable|string|max:2000','summary_en'=>'nullable|string|max:2000','body_bn'=>'nullable|string|max:2000000','body_en'=>'nullable|string|max:2000000','published_at'=>'nullable|date','expires_at'=>'nullable|date|after:published_at','metadata_json'=>'nullable|json','files.*'=>'file|max:20480','images.*'=>'image|max:10240','remove_attachments'=>'nullable|array','remove_attachments.*'=>'integer|exists:media_attachments,id']);
        $data['path']='/'.ltrim($data['path'],'/');$data['slug']=basename($data['path']);$data['body_bn']=$sanitizer->clean($data['body_bn']??'');$data['body_en']=$sanitizer->clean($data['body_en']??'');$data['metadata']=json_decode($data['metadata_json']??'{}',true);unset($data['metadata_json'],$data['files'],$data['images'],$data['remove_attachments']);return $data;
    }
    private function addFiles(Request $request, ContentItem $item, MediaManager $manager):void
    {
        $created=[];
        try {
            foreach(['files'=>'attachment','images'=>'image'] as $field=>$role)foreach($request->file($field,[]) as $order=>$file){
                $stored=$manager->store($file,$request->user());
                try {
                    $attachment=$item->attachments()->create(['media_file_id'=>$stored->id,'role'=>$role,'label_bn'=>$file->getClientOriginalName(),'sort_order'=>$order]);
                } catch (\Throwable $exception) {
                    $manager->delete($stored);
                    throw $exception;
                }
                $created[]=$attachment;
            }
        } catch (\Throwable $exception) {
            foreach($created as $attachment){$media=$attachment->media;$attachment->delete();if($media)$manager->delete($media);}
            throw $exception;
        }
    }
    private function stageRevisionMedia(Request $request,ContentRevision $revision,MediaManager $manager,array $removeIds):void
    {
        $pending=[];$storedMedia=[];
        try {
            foreach(['files'=>'attachment','images'=>'image'] as $field=>$role)foreach($request->file($field,[]) as $order=>$file){$stored=$manager->store($file,$request->user());$storedMedia[]=$stored;$pending[]=['media_file_id'=>$stored->id,'role'=>$role,'label_bn'=>$file->getClientOriginalName(),'sort_order'=>$order];}
            $snapshot=$revision->snapshot;
            if($pending)$snapshot['_pending_attachments']=array_merge($snapshot['_pending_attachments']??[],$pending);
            if($removeIds)$snapshot['_remove_attachment_ids']=array_values(array_unique(array_merge($snapshot['_remove_attachment_ids']??[],$removeIds)));
            $revision->update(['snapshot'=>$snapshot]);
        } catch (\Throwable $exception) {
            foreach($storedMedia as $stored)$manager->delete($stored);
            throw $exception;
        }
    }
    private function advance(ContentItem|ContentRevision $subject,Request $request,ContentWorkflow $workflow):void
    {
        if($subject instanceof ContentItem && $subject->status==='published') return;
        if($request->intent==='submit')$subject instanceof ContentRevision?$workflow->submitRevision($subject,$request->user()):$workflow->submit($subject,$request->user());
        if($request->intent==='publish'&&$subject instanceof ContentItem)$workflow->publish($subject,$request->user());
    }
}
