<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

class PublicPathAvailable implements ValidationRule
{
    public function __construct(
        private readonly string $currentTable,
        private readonly ?int $currentId = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)
            || $value === '/'
            || preg_match('/[\\x00-\\x20\\x7f?#\\\\]/u', $value)
            || preg_match('#^/(?:en|admin|install|search|assets|build|uploads)(?:/|$)#i', $value)) {
            $fail('The :attribute must be a safe, non-reserved public path.');
            return;
        }

        foreach (['pages', 'content_items', 'officers', 'gallery_albums'] as $table) {
            $query = DB::table($table)->where('path', $value);
            if ($table === $this->currentTable && $this->currentId) {
                $query->where('id', '!=', $this->currentId);
            }
            if ($query->exists()) {
                $fail('The :attribute is already used by another public record.');
                return;
            }
        }
    }
}
