<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class Media
{
    public static function path(?string $path): string
    {
        $path = preg_replace('~^/?uploads/~', '', (string) $path);
        abort_unless($path !== '' && ! str_contains($path, '..') && preg_match('~^[a-zA-Z0-9/_ .-]+$~', $path), 404);
        abort_unless(Storage::disk('public')->exists($path), 404);

        return $path;
    }

    public static function url(?string $path): ?string
    {
        if (! $path) {
            return null;
        }
        $path = preg_replace('~^/?uploads/~', '', $path);

        return route('media', ['path' => $path]);
    }
}
