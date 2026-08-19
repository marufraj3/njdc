<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;use App\Models\ContentType;use Illuminate\Http\Request;use Illuminate\Validation\Rule;
class ContentTypeController extends Controller
{
 public function store(Request $r){$d=$r->validate(['code'=>['required','alpha_dash','max:64','unique:content_types,code'],'name_bn'=>'required|max:255','name_en'=>'nullable|max:255','base_path'=>'required|max:255|unique:content_types,base_path']);$d['base_path']='/'.ltrim($d['base_path'],'/');$d['is_active']=true;ContentType::create($d);return back()->with('status','কনটেন্টের ধরন তৈরি হয়েছে।');}
 public function update(Request $r,ContentType $type){$d=$r->validate(['code'=>['required','alpha_dash','max:64',Rule::unique('content_types')->ignore($type)],'name_bn'=>'required|max:255','name_en'=>'nullable|max:255','base_path'=>['required','max:255',Rule::unique('content_types')->ignore($type)],'is_active'=>'nullable|boolean']);$d['base_path']='/'.ltrim($d['base_path'],'/');$d['is_active']=$r->boolean('is_active');$type->update($d);return back()->with('status','ধরন হালনাগাদ হয়েছে।');}
}
