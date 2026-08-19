<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedContent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContentType extends Model
{
    use HasLocalizedContent;
    protected $guarded = [];
    protected function casts(): array { return ['columns' => 'array', 'fields' => 'array', 'is_active' => 'boolean']; }
    public function items(): HasMany { return $this->hasMany(ContentItem::class); }
}
