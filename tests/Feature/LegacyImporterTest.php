<?php
namespace Tests\Feature;
use App\Models\ImportRun;use App\Models\Setting;use App\Models\User;use App\Services\LegacyContentImporter;use Illuminate\Foundation\Testing\RefreshDatabase;use Tests\TestCase;
class LegacyImporterTest extends TestCase
{
 use RefreshDatabase;
 public function test_import_is_normalized_and_checksum_idempotent():void
 {
  $user=User::factory()->create(['role'=>'super_admin']);
  $path=storage_path('app/test-import.json');
  file_put_contents($path,json_encode(['site'=>['officeTitle'=>'পরীক্ষা'],'pages'=>[],'menu'=>[],'home'=>[],'sidebar'=>[],'footer'=>[]],JSON_UNESCAPED_UNICODE));
  $importer=app(LegacyContentImporter::class);
  $first=$importer->import($path,$user->id);
  $second=$importer->import($path,$user->id);
  $this->assertSame($first->id,$second->id);
  $this->assertSame('পরীক্ষা',Setting::where('key','site.office_title')->value('value_bn'));
  $this->assertSame(1,ImportRun::where('status','completed')->count());
  @unlink($path);
 }
}
