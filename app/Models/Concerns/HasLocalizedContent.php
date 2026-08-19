<?php

namespace App\Models\Concerns;

trait HasLocalizedContent
{
    public function localized(string $field, ?string $locale = null): ?string
    {
        $locale ??= app()->getLocale();
        $fallback = config('app.fallback_locale', 'bn');
        $value = $this->getAttribute("{$field}_{$locale}");

        return filled($value) ? $value : $this->getAttribute("{$field}_{$fallback}");
    }
}
