<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedContent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Officer extends Model
{
    use HasLocalizedContent, SoftDeletes;
    protected $guarded = [];
    protected function casts(): array { return ['duties' => 'array', 'submitted_at' => 'datetime', 'published_at' => 'datetime']; }
    public function scopePublished(Builder $query): Builder { return $query->where('status', 'published'); }
    public function getPhotoAttribute(): string { return $this->photoMedia?->url ?? $this->photo_url ?? ''; }
    public function photoMedia() { return $this->belongsTo(MediaFile::class, 'photo_media_id'); }
}
