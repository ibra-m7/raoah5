<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

final class Media
{
    public static function store(?UploadedFile $file, string $directory, ?string $oldPath = null): ?string
    {
        if ($file === null || ! $file->isValid()) {
            return $oldPath;
        }

        self::delete($oldPath);

        Storage::disk('public')->makeDirectory($directory);

        $path = $file->store($directory, 'public');
        if (! is_string($path) || $path === '') {
            throw new \RuntimeException('تعذّر حفظ الملف المرفوع.');
        }

        return $path;
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

        $relative = 'storage/'.ltrim($path, '/');
        if (app()->runningInConsole()) {
            return asset($relative);
        }

        return rtrim(request()->getSchemeAndHttpHost(), '/').'/'.$relative;
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
