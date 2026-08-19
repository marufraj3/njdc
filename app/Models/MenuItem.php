<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedContent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuItem extends Model
{
    use HasLocalizedContent;
    protected $guarded = [];
    protected function casts(): array { return ['is_active' => 'boolean']; }
    public function menu(): BelongsTo { return $this->belongsTo(Menu::class); }
    public function parent(): BelongsTo { return $this->belongsTo(self::class, 'parent_id'); }
    public function children(): HasMany { return $this->hasMany(self::class, 'parent_id')->where('is_active', true)->orderBy('sort_order'); }
    public function resolvedUrl(): string { return $this->page?->path ?? $this->contentItem?->path ?? $this->url ?? '#'; }
    public function page(): BelongsTo { return $this->belongsTo(Page::class); }
    public function contentItem(): BelongsTo { return $this->belongsTo(ContentItem::class); }
}
