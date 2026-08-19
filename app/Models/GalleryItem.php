<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedContent;
use Illuminate\Database\Eloquent\Model;

class GalleryItem extends Model
{
    use HasLocalizedContent;
    protected $guarded = [];
    protected function casts(): array { return ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'is_active' => 'boolean']; }
    public function media() { return $this->belongsTo(MediaFile::class, 'media_file_id'); }
    public function getUrlAttribute(): string { return $this->media?->url ?? $this->image_url ?? ''; }
}
