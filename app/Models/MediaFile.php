<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedContent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class MediaFile extends Model
{
    use HasLocalizedContent, SoftDeletes;
    protected $guarded = [];
    protected function casts(): array { return ['metadata' => 'array']; }
    public function getUrlAttribute(): string
    {
        return $this->path ? Storage::disk($this->disk)->url($this->path) : (string) $this->remote_url;
    }
}
