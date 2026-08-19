<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedContent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LayoutItem extends Model
{
    use HasLocalizedContent;
    protected $guarded = [];
    protected function casts(): array { return ['settings' => 'array', 'is_active' => 'boolean']; }
    public function section(): BelongsTo { return $this->belongsTo(LayoutSection::class, 'layout_section_id'); }
    public function children(): HasMany { return $this->hasMany(self::class, 'parent_id')->where('is_active', true)->orderBy('sort_order'); }
    public function contentItem(): BelongsTo { return $this->belongsTo(ContentItem::class); }
    public function media(): BelongsTo { return $this->belongsTo(MediaFile::class, 'media_file_id'); }
}
