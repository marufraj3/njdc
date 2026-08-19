<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;use App\Models\User;use Illuminate\Http\Request;use Illuminate\Support\Facades\Hash;use Illuminate\Validation\Rule;
class UserController extends Controller
{
 public function index(){return view('admin.users.index',['users'=>User::orderBy('name')->paginate(30)]);}
 public function create(){return view('admin.users.form',['managedUser'=>new User]);}
 public function store(Request $r){$d=$this->data($r);$d['password']=Hash::make($d['password']);$d['email_verified_at']=now();User::create($d);return redirect()->route('admin.users.index')->with('status','ব্যবহারকারী তৈরি হয়েছে।');}
 public function edit(User $user){return view('admin.users.form',['managedUser'=>$user]);}
 public function update(Request $r,User $user){$d=$this->data($r,$user);if(blank($d['password']??null))unset($d['password']);else $d['password']=Hash::make($d['password']);if($r->user()->is($user)&&!($d['is_active']??false))return back()->withErrors(['is_active'=>'নিজের অ্যাকাউন্ট নিষ্ক্রিয় করা যাবে না।']);$user->update($d);return redirect()->route('admin.users.index')->with('status','ব্যবহারকারী হালনাগাদ হয়েছে।');}
 public function destroy(Request $r,User $user){if($r->user()->is($user))return back()->withErrors(['user'=>'নিজের অ্যাকাউন্ট নিষ্ক্রিয় করা যাবে না।']);$user->update(['is_active'=>false]);return back()->with('status','ব্যবহারকারী নিষ্ক্রিয় করা হয়েছে।');}
 private function data(Request $r,?User $u=null):array{$d=$r->validate(['name'=>'required|max:255','email'=>['required','email','max:255',Rule::unique('users')->ignore($u)],'role'=>['required',Rule::in(['editor','publisher','super_admin'])],'password'=>[$u?'nullable':'required','min:12','confirmed'],'is_active'=>'nullable|boolean']);$d['is_active']=$r->boolean('is_active');return $d;}
}
