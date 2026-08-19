<?php
namespace Database\Seeders;
use App\Models\User;use App\Services\LegacyContentImporter;use Illuminate\Database\Seeder;
class DatabaseSeeder extends Seeder
{
 public function run():void
 {
  $user=User::whereIn('role',['super_admin','publisher'])->first();
  if($user) app(LegacyContentImporter::class)->import(database_path('seeders/data/jdpc.json'),$user->id);
  else $this->command?->warn('Legacy data was not imported because no publisher or super administrator exists.');
 }
}
