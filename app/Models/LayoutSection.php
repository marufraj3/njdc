<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedContent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LayoutSection extends Model
{
    use HasLocalizedContent;
    protected $guarded = [];
    protected function casts(): array { return ['settings' => 'array', 'is_active' => 'boolean']; }
    public function items(): HasMany { return $this->hasMany(LayoutItem::class)->whereNull('parent_id')->where('is_active', true)->orderBy('sort_order'); }
    public function children(): HasMany { return $this->hasMany(self::class, 'parent_id')->where('is_active', true)->orderBy('sort_order'); }
}
