<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportRun extends Model
{
    protected $guarded = [];
    protected function casts(): array { return ['counts' => 'array', 'warnings' => 'array', 'started_at' => 'datetime', 'finished_at' => 'datetime']; }
}
