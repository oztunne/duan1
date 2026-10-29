<?php
namespace Tests\Feature;
use App\Models\{User,Task};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
class WorkspaceTest extends TestCase {
 use RefreshDatabase;
 private function member(string $role='staff'): User { $u=User::factory()->create(); $u->forceFill(['role'=>$role])->save(); return $u; }
 public function test_public_pages_and_authentication():void {
  $this->get('/')->assertOk(); $this->get('/login')->assertOk(); $this->get('/dashboard')->assertRedirect('/login');
  $u=$this->member();
  $this->post('/login',['email'=>$u->email,'password'=>'wrong'])->assertSessionHasErrors('email'); $this->assertGuest();
  $this->post('/login',['email'=>$u->email,'password'=>'password'])->assertRedirect('/dashboard'); $this->assertAuthenticatedAs($u);
  $this->post('/logout')->assertRedirect('/'); $this->assertGuest();
 }
 public function test_staff_permissions_and_task_isolation():void {
  $u=$this->member(); $other=$this->member(); $task=Task::create(['title'=>'Private task','user_id'=>$other->id]);
  $this->actingAs($u)->get('/users')->assertForbidden();
  $this->post('/users',[])->assertForbidden(); $this->post('/tasks',[])->assertForbidden();
  $this->delete('/tasks/'.$task->id)->assertForbidden(); $this->patch('/users/'.$other->id)->assertForbidden();
  $this->patch('/tasks/'.$task->id,['status'=>'done'])->assertForbidden();
  $this->get('/dashboard')->assertOk()->assertDontSee('Private task');
 }
 public function test_admin_manages_staff_and_work():void {
  $admin=$this->member('admin'); $this->actingAs($admin)->post('/users',['name'=>'Staff','email'=>'staff@example.test','password'=>'LongPassword123','password_confirmation'=>'LongPassword123','role'=>'admin'])->assertSessionHasNoErrors();
  $staff=User::where('email','staff@example.test')->firstOrFail(); $this->assertSame('staff',$staff->role);
  $this->post('/tasks',['title'=>'New task','user_id'=>$staff->id])->assertSessionHasNoErrors();
  $task=Task::firstOrFail(); $this->get('/dashboard')->assertOk()->assertSee('New task'); $this->get('/users')->assertOk();
  $this->actingAs($staff)->patch('/tasks/'.$task->id,['status'=>'done','user_id'=>$admin->id])->assertSessionHasNoErrors();
  $this->assertDatabaseHas('tasks',['id'=>$task->id,'status'=>'done','user_id'=>$staff->id]);
  $this->patch('/tasks/'.$task->id,['status'=>'bad'])->assertSessionHasErrors('status');
 }
 public function test_lock_revokes_access():void {
  $u=$this->member(); $admin=$this->member('admin');
  $this->actingAs($admin)->patch('/users/'.$admin->id)->assertForbidden();
  $this->patch('/users/'.$u->id)->assertRedirect();
  $this->actingAs($u->fresh())->get('/dashboard')->assertRedirect('/login'); $this->assertGuest();
  $this->post('/login',['email'=>$u->email,'password'=>'password'])->assertSessionHasErrors('email');
 }
 public function test_password_change():void {
  $u=$this->member(); $this->actingAs($u)->get('/account')->assertOk();
  $data=['current_password'=>'wrong','password'=>'AnotherPassword123','password_confirmation'=>'AnotherPassword123'];
  $this->put('/account/password',$data)->assertSessionHasErrors('current_password');
  $data['current_password']='password'; $this->put('/account/password',$data)->assertSessionHasNoErrors();
  $this->assertTrue(Hash::check($data['password'],$u->fresh()->password));
 }
 public function test_login_rate_limit():void {
  $u=$this->member();
  for($i=0;$i<6;$i++) $this->post('/login',['email'=>$u->email,'password'=>'wrong'])->assertSessionHasErrors('email');
  $this->post('/login',['email'=>$u->email,'password'=>'password'])->assertSessionHasErrors('email');
  $this->assertGuest();
 }
}

