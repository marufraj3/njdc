<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedContent;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasLocalizedContent;

    protected $guarded = [];

    public static function valueFor(string $key, ?string $locale = null, mixed $default = null): mixed
    {
        $setting = static::query()->where('key', $key)->first();
        if (! $setting) return $default;
        $value = $setting->localized('value', $locale);
        if ($setting->type === 'boolean') return filter_var($value, FILTER_VALIDATE_BOOL);
        if ($setting->type === 'json') return json_decode($value ?: 'null', true) ?? $default;
        return filled($value) ? $value : $default;
    }
}
