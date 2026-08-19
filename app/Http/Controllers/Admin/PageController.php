<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContentRevision;
use App\Models\Page;
use App\Rules\PublicPathAvailable;
use App\Services\ContentWorkflow;
use App\Support\HtmlSanitizer;
use App\Support\PublicPath;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PageController extends Controller
{
    public function index(Request $request)
    {
        $pages = Page::query()->when($request->status, fn($q,$status)=>$q->where('status',$status))->latest('updated_at')->paginate(25)->withQueryString();
        return view('admin.pages.index', compact('pages'));
    }
    public function create() { return view('admin.pages.form', ['page'=>new Page]); }
    public function store(Request $request, ContentWorkflow $workflow, HtmlSanitizer $sanitizer)
    {
        $data=$this->validated($request,$sanitizer);
        $page=$workflow->saveDraft(new Page,$data,$request->user());
        $this->advance($page,$request,$workflow);
        return redirect()->route('admin.pages.index')->with('status','পেজ সংরক্ষণ করা হয়েছে।');
    }
    public function edit(Page $page) { return view('admin.pages.form', compact('page')); }
    public function update(Request $request, Page $page, ContentWorkflow $workflow, HtmlSanitizer $sanitizer)
    {
        $result=$workflow->saveDraft($page,$this->validated($request,$sanitizer,$page),$request->user(),$request->input('note'));
        $this->advance($result,$request,$workflow);
        return redirect()->route('admin.pages.index')->with('status',$result instanceof ContentRevision?'সংশোধনটি অনুমোদনের জন্য সংরক্ষিত হয়েছে।':'পেজ হালনাগাদ হয়েছে।');
    }
    public function submit(Request $request, Page $page, ContentWorkflow $workflow) { $workflow->submit($page,$request->user(),$request->input('note')); return back()->with('status','অনুমোদনের জন্য পাঠানো হয়েছে।'); }
    public function publish(Request $request, Page $page, ContentWorkflow $workflow) { $workflow->publish($page,$request->user(),$request->input('note')); return back()->with('status','পেজ প্রকাশিত হয়েছে।'); }
    public function reject(Request $request, Page $page, ContentWorkflow $workflow) { $request->validate(['note'=>'required|string|max:1000']);$workflow->reject($page,$request->user(),$request->note);return back()->with('status','পেজ ফেরত পাঠানো হয়েছে।'); }
    public function destroy(Page $page) { $page->delete(); return back()->with('status','পেজ ট্র্যাশে পাঠানো হয়েছে।'); }

    private function validated(Request $request, HtmlSanitizer $sanitizer, ?Page $page=null): array
    {
        $request->merge(['path' => PublicPath::normalize($request->input('path'))]);
        $data=$request->validate([
            'path'=>['required','string','max:512',new PublicPathAvailable('pages', $page?->id)],'template'=>['required',Rule::in(['content','raw','table','officers'])],
            'title_bn'=>'required|string|max:255','title_en'=>'nullable|string|max:255','body_bn'=>'nullable|string|max:2000000','body_en'=>'nullable|string|max:2000000',
            'seo_title_bn'=>'nullable|string|max:255','seo_title_en'=>'nullable|string|max:255','seo_description_bn'=>'nullable|string|max:1000','seo_description_en'=>'nullable|string|max:1000','is_indexable'=>'nullable|boolean',
        ]);
        $data['path']='/'.ltrim($data['path'],'/'); $data['slug']=basename($data['path']); $data['body_bn']=$sanitizer->clean($data['body_bn']??'');$data['body_en']=$sanitizer->clean($data['body_en']??'');$data['is_indexable']=$request->boolean('is_indexable');
        return $data;
    }
    private function advance(Page|ContentRevision $subject, Request $request, ContentWorkflow $workflow): void
    {
        if($subject instanceof Page && $subject->status==='published') return;
        if($request->intent==='submit') $subject instanceof ContentRevision?$workflow->submitRevision($subject,$request->user()):$workflow->submit($subject,$request->user());
        if($request->intent==='publish' && $subject instanceof Page) $workflow->publish($subject,$request->user());
    }
}
