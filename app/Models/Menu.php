<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedContent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Menu extends Model
{
    use HasLocalizedContent;
    protected $guarded = [];
    protected function casts(): array { return ['is_active' => 'boolean']; }
    public function items(): HasMany { return $this->hasMany(MenuItem::class)->whereNull('parent_id')->where('is_active', true)->orderBy('sort_order'); }
}
