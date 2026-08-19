<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedContent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class GalleryAlbum extends Model
{
    use HasLocalizedContent, SoftDeletes;
    protected $guarded = [];
    protected function casts(): array { return ['submitted_at' => 'datetime', 'published_at' => 'datetime']; }
    public function scopePublished(Builder $query): Builder { return $query->where('status', 'published'); }
    public function items(): HasMany { return $this->hasMany(GalleryItem::class)->orderBy('sort_order'); }
}
