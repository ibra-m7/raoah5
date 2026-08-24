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

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, 'data:')) {
            return $path;
        }

        return self::absoluteUrl('storage/'.ltrim($path, '/'));
    }

    public static function absoluteUrl(string $path): string
    {
        $path = ltrim($path, '/');

        if (! app()->runningInConsole()) {
            $host = rtrim((string) request()->getSchemeAndHttpHost(), '/');
            if ($host !== '') {
                return $host.'/'.$path;
            }
        }

        $appUrl = rtrim((string) config('app.url'), '/');

        return ($appUrl !== '' ? $appUrl : '').'/'.$path;
    }

    public static function isMissingLocal(?string $path): bool
    {
        if ($path === null || $path === '') {
            return true;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, 'data:')) {
            return false;
        }

        return ! Storage::disk('public')->exists($path);
    }

    private static function isStored(?string $path): bool
    {
        return $path !== null
            && $path !== ''
            && ! str_starts_with($path, 'http://')
            && ! str_starts_with($path, 'https://')
            && ! str_starts_with($path, 'data:')
            && Storage::disk('public')->exists($path);
    }
}
