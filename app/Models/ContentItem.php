<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedContent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContentItem extends Model
{
    use HasLocalizedContent, SoftDeletes;
    protected $guarded = [];
    protected function casts(): array { return ['metadata' => 'array', 'published_at' => 'datetime', 'expires_at' => 'datetime', 'submitted_at' => 'datetime']; }
    public function type(): BelongsTo { return $this->belongsTo(ContentType::class, 'content_type_id'); }
    public function attachments(): MorphMany { return $this->morphMany(MediaAttachment::class, 'attachable')->orderBy('sort_order'); }
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')
            ->where(fn ($q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }
}
