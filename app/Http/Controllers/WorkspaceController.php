<?php
namespace App\Http\Controllers;
use App\Models\{User,Task};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth,Hash,RateLimiter};
use Illuminate\Validation\{Rule,Rules\Password,ValidationException};
class WorkspaceController extends Controller {
 public function login(Request $request) {
  $data=$request->validate(['email'=>'required|email|max:255','password'=>'required|string|max:255']);
  $key='login:'.hash('sha256',mb_strtolower($data['email']).'|'.$request->ip());
  if(RateLimiter::tooManyAttempts($key,5)) throw ValidationException::withMessages(['email'=>'Vui lòng thử lại sau '.RateLimiter::availableIn($key).' giây.']);
  if(!Auth::attempt([...$data,'active'=>true])) {
   RateLimiter::hit($key,60);
   throw ValidationException::withMessages(['email'=>'Email hoặc mật khẩu không đúng, hoặc tài khoản đã bị khóa.']);
  }
  RateLimiter::clear($key); $request->session()->regenerate();
  return redirect()->intended(route('dashboard'));
 }
 public function logout(Request $request) { Auth::logout(); $request->session()->invalidate(); $request->session()->regenerateToken(); return redirect('/'); }
 public function dashboard(Request $request) {
  $query=Task::query()->with('assignee');
  if($request->user()->role!=='admin') $query->where('user_id',$request->user()->id);
  $stats=(clone $query)->get();
  $status=$request->query('status');
  if(in_array($status,['todo','doing','done'],true)) $query->where('status',$status);
  if($request->filled('q')) $query->where('title','like','%'.mb_substr($request->string('q'),0,100).'%');
  return view('dashboard',['tasks'=>$query->latest()->paginate(10)->withQueryString(),'stats'=>$stats,'staff'=>User::where('role','staff')->where('active',true)->orderBy('name')->get()]);
 }
 public function createTask(Request $request) {
  $data=$request->validate(['title'=>'required|string|max:200','description'=>'nullable|string|max:5000','user_id'=>['required',Rule::exists('users','id')->where('role','staff')->where('active',true)],'due_date'=>'nullable|date']);
  Task::create($data); return back()->with('success','Đã giao công việc.');
 }
 public function updateTask(Request $request,Task $task) {
  abort_unless($request->user()->role==='admin'||$task->user_id===$request->user()->id,403);
  $task->update($request->validate(['status'=>['required',Rule::in(['todo','doing','done'])]]));
  return back()->with('success','Đã cập nhật tiến độ.');
 }
 public function deleteTask(Task $task) { $task->delete(); return back()->with('success','Đã xóa công việc.'); }
 public function users() { return view('users',['users'=>User::orderBy('name')->paginate(15)]); }
 public function createUser(Request $request) {
  $data=$request->validate(['name'=>'required|string|max:100','email'=>'required|email|max:255|unique:users','password'=>['required','confirmed',Password::min(12)->letters()->numbers()]]);
  $user=new User($data); $user->role='staff'; $user->save();
  return back()->with('success','Đã tạo tài khoản nhân viên.');
 }
 public function toggleUser(Request $request,User $user) {
  abort_if($user->role==='admin',403);
  $user->active=!$user->active; $user->save();
  if(!$user->active) \Illuminate\Support\Facades\DB::table('sessions')->where('user_id',$user->id)->delete();
  return back()->with('success','Đã cập nhật tài khoản.');
 }
 public function password(Request $request) {
  $data=$request->validate(['current_password'=>'required|current_password','password'=>['required','confirmed',Password::min(12)->letters()->numbers()]]);
  $request->user()->update(['password'=>$data['password']]); $request->session()->regenerate();
  return back()->with('success','Đã đổi mật khẩu.');
 }
}

