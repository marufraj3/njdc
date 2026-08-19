<?php
namespace Tests\Feature;
use App\Models\Page;use App\Models\User;use Illuminate\Foundation\Testing\RefreshDatabase;use Tests\TestCase;
class AdminAccessTest extends TestCase
{
 use RefreshDatabase;
 public function test_active_editor_can_open_dashboard_but_not_publisher_configuration():void
 {
  $editor=User::factory()->create(['role'=>'editor','is_active'=>true]);
  $this->actingAs($editor)->get('/admin')->assertOk();
  $this->actingAs($editor)->get('/admin/settings')->assertForbidden();
 }
 public function test_inactive_user_is_blocked_from_admin():void
 {
  $user=User::factory()->create(['role'=>'super_admin','is_active'=>false]);
  $this->actingAs($user)->get('/admin')->assertForbidden();
 }
 public function test_editor_cannot_delete_live_content():void
 {
  $editor=User::factory()->create(['role'=>'editor','is_active'=>true]);
  $page=Page::create(['path'=>'/pages/live','title_bn'=>'Live','status'=>'published']);
  $this->actingAs($editor)->delete(route('admin.pages.destroy',$page))->assertForbidden();
  $this->assertDatabaseHas('pages',['id'=>$page->id,'deleted_at'=>null]);
 }
 public function test_admin_named_routes_are_registered():void
 {
  foreach(['admin.dashboard','admin.pages.index','admin.content.index','admin.officers.index','admin.media.index','admin.revisions.index','admin.galleries.index','admin.menus.index','admin.layouts.index','admin.settings.index','admin.users.index'] as $name)$this->assertNotEmpty(route($name));
 }
}
