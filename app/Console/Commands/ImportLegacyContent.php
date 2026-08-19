<?php
namespace App\Console\Commands;
use App\Models\User;use App\Services\LegacyContentImporter;use Illuminate\Console\Command;
class ImportLegacyContent extends Command
{
 protected $signature='jdpc:import {file? : JSON source file} {--user= : User ID attributed as importer}';
 protected $description='Normalize and import the legacy JDPC JSON content into the CMS database';
 public function handle(LegacyContentImporter $importer):int
 {
  $userId=$this->option('user')?:User::whereIn('role',['super_admin','publisher'])->value('id');
  if(!$userId){$this->error('Create a publisher or super administrator first, or provide --user=ID.');return self::FAILURE;}
  $path=$this->argument('file')?:database_path('seeders/data/jdpc.json');
  if(!is_file($path)){$this->error("Source file not found: {$path}");return self::FAILURE;}
  $run=$importer->import($path,(int)$userId);
  $this->info("Import {$run->status}. Run #{$run->id}.");
  foreach(($run->counts??[]) as $type=>$count)$this->line("{$type}: {$count}");
  return $run->status==='completed'?self::SUCCESS:self::FAILURE;
 }
}
