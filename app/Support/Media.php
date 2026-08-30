<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class Media
{
    public static function store(?UploadedFile $file, string $directory, ?string $oldPath = null): ?string
    {
        if ($file === null) {
            return $oldPath;
        }

        if (! $file->isValid()) {
            throw new \RuntimeException('فشل رفع الصورة: '.$file->getErrorMessage());
        }

        self::delete($oldPath);

        Storage::disk('public')->makeDirectory($directory);

        $path = $file->store($directory, 'public');
        if (! is_string($path) || $path === '') {
            throw new \RuntimeException('تعذّر حفظ الملف المرفوع.');
        }

        return $path;
    }

    public static function storePath(string $absolutePath, string $directory, ?string $oldPath = null): ?string
    {
        if ($absolutePath === '' || ! is_file($absolutePath)) {
            return $oldPath;
        }

        self::delete($oldPath);
        Storage::disk('public')->makeDirectory($directory);

        $extension = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION) ?: 'jpg');
        $filename = Str::random(40).'.'.$extension;
        $path = trim($directory, '/').'/'.$filename;

        $stream = fopen($absolutePath, 'rb');
        if ($stream === false) {
            throw new \RuntimeException('تعذّر قراءة ملف الصورة.');
        }

        try {
            $stored = Storage::disk('public')->put($path, $stream);
        } finally {
            fclose($stream);
        }

        if (! $stored) {
            throw new \RuntimeException('تعذّر حفظ ملف الصورة.');
        }

        return $path;
    }

    public static function delete(?string $path): void
    {
        if (! self::isStored($path)) {
            return;
        }

        $local = self::localStoragePath($path);
        if ($local === null) {
            return;
        }

        Storage::disk('public')->delete($local);
    }

    public static function url(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        if (str_starts_with($path, 'data:')) {
            return $path;
        }

        $local = self::localStoragePath($path);
        if ($local !== null) {
            return self::absoluteUrl('storage/'.$local);
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return self::absoluteUrl('storage/'.ltrim($path, '/'));
    }

    /**
     * Normalize a stored path or self-hosted /storage/ URL to a relative public-disk path.
     */
    public static function normalizeStoredPath(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        $local = self::localStoragePath($path);

        return $local ?? $path;
    }

    /**
     * Resolve a relative path or same-app /storage/ URL to the public disk path.
     */
    public static function localStoragePath(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        if (str_starts_with($path, 'data:')) {
            return null;
        }

        if (! str_starts_with($path, 'http://') && ! str_starts_with($path, 'https://')) {
            $relative = ltrim(str_replace('\\', '/', $path), '/');

            return ($relative !== '' && ! str_contains($relative, '..')) ? $relative : null;
        }

        $parsed = parse_url($path);
        if (! is_array($parsed) || empty($parsed['path'])) {
            return null;
        }

        if (! self::isSameAppHost($parsed['host'] ?? null)) {
            return null;
        }

        $storagePath = ltrim($parsed['path'], '/');
        if (! str_starts_with($storagePath, 'storage/')) {
            return null;
        }

        $relative = ltrim(substr($storagePath, strlen('storage/')), '/');

        return ($relative !== '' && ! str_contains($relative, '..')) ? $relative : null;
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

        if (str_starts_with($path, 'data:')) {
            return false;
        }

        $local = self::localStoragePath($path);
        if ($local !== null) {
            return ! Storage::disk('public')->exists($local);
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return false;
        }

        return true;
    }

    private static function isStored(?string $path): bool
    {
        $local = self::localStoragePath($path);

        return $local !== null && Storage::disk('public')->exists($local);
    }

    private static function isSameAppHost(?string $host): bool
    {
        if ($host === null || $host === '') {
            return false;
        }

        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);
        if (is_string($appHost) && $appHost !== '' && strcasecmp($appHost, $host) === 0) {
            return true;
        }

        if (! app()->runningInConsole()) {
            $requestHost = parse_url((string) request()->getSchemeAndHttpHost(), PHP_URL_HOST);

            return is_string($requestHost) && $requestHost !== '' && strcasecmp($requestHost, $host) === 0;
        }

        return false;
    }
}
