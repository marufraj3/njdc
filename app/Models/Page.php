<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedContent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Page extends Model
{
    use HasLocalizedContent, SoftDeletes;
    protected $guarded = [];
    protected function casts(): array { return ['metadata' => 'array', 'published_at' => 'datetime', 'submitted_at' => 'datetime', 'is_indexable' => 'boolean']; }
    public function scopePublished(Builder $query): Builder { return $query->where('status', 'published')->where(fn ($q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now())); }
    public function attachments(): MorphMany { return $this->morphMany(MediaAttachment::class, 'attachable')->orderBy('sort_order'); }
}
