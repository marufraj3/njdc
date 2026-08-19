<?php

namespace App\Support;

final class PublicPath
{
    public static function normalize(mixed $value): string
    {
        $path = trim((string) $value);
        $path = '/'.trim($path, '/');
        $path = (string) preg_replace('#/+#', '/', $path);

        return $path === '' ? '/' : $path;
    }
}
