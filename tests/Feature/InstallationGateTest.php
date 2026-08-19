<?php
namespace Tests\Feature;
use Tests\TestCase;
class InstallationGateTest extends TestCase
{
 public function test_uninstalled_site_redirects_to_browser_installer():void
 {
  @unlink(storage_path('app/installed'));
  $this->get('/')->assertRedirect(route('install.show'));
  $this->get('/install')->assertOk()->assertSee('JDPC');
 }
 public function test_installer_is_unavailable_after_installation():void
 {
  $this->get('/install')->assertRedirect('/');
 }
}
