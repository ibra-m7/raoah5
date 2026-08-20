<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

final class Media
{
    public static function store(?UploadedFile $file, string $directory, ?string $oldPath = null): ?string
    {
        if ($file === null) {
            return $oldPath;
        }

        self::delete($oldPath);

        return $file->store($directory, 'public');
    }

    public static function delete(?string $path): void
    {
        if (! self::isStored($path)) {
            return;
        }

        Storage::disk('public')->delete($path);
    }

    public static function url(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return asset('storage/'.$path);
    }

    private static function isStored(?string $path): bool
    {
        return $path !== null
            && $path !== ''
            && ! str_starts_with($path, 'http://')
            && ! str_starts_with($path, 'https://')
            && Storage::disk('public')->exists($path);
    }
}
