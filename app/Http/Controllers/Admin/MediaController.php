<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;use App\Models\MediaFile;use App\Services\ContentWorkflow;use App\Services\MediaManager;use Illuminate\Http\Request;
class MediaController extends Controller
{
 public function index(Request $r){$media=MediaFile::when($r->q,fn($q,$v)=>$q->where('original_name','like','%'.str_replace(['%','_'],['\\%','\\_'],$v).'%'))->latest()->paginate(30)->withQueryString();return view('admin.media.index',compact('media'));}
 public function store(Request $r,MediaManager $m){$r->validate(['file'=>'required|file|max:20480','alt_bn'=>'nullable|max:255','alt_en'=>'nullable|max:255','caption_bn'=>'nullable|max:2000','caption_en'=>'nullable|max:2000']);$m->store($r->file('file'),$r->user(),$r->only('alt_bn','alt_en','caption_bn','caption_en'));return back()->with('status','ফাইল আপলোড হয়েছে।');}
 public function update(Request $r,MediaFile $medium){$d=$r->validate(['alt_bn'=>'nullable|max:255','alt_en'=>'nullable|max:255','caption_bn'=>'nullable|max:2000','caption_en'=>'nullable|max:2000']);$medium->update($d);return back()->with('status','মিডিয়া তথ্য হালনাগাদ হয়েছে।');}
 public function destroy(MediaFile $medium,MediaManager $m,ContentWorkflow $workflow){if($workflow->mediaIsUsed($medium))return back()->withErrors(['media'=>'ফাইলটি কনটেন্ট বা চলমান সংশোধনে ব্যবহৃত হচ্ছে; আগে সংশ্লিষ্ট সংযোগ সরান।']);$m->delete($medium);return back()->with('status','ফাইল মুছে ফেলা হয়েছে।');}
}
