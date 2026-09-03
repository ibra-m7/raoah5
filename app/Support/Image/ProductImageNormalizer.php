<?php

namespace App\Support\Image;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageManagerInterface;
use RuntimeException;

final class ProductImageNormalizer
{
    public function normalizeUploadedFile(UploadedFile $file): string
    {
        if (! $file->isValid()) {
            throw new RuntimeException('فشل رفع الصورة: '.$file->getErrorMessage());
        }

        $path = $file->getRealPath();
        if (! is_string($path) || $path === '') {
            throw new RuntimeException('تعذر قراءة ملف الصورة المرفوع.');
        }

        return $this->normalizePath($path);
    }

    public function normalizePath(string $absolutePath): string
    {
        if ($absolutePath === '' || ! is_file($absolutePath)) {
            throw new RuntimeException('ملف الصورة غير موجود.');
        }

        $size = max(64, (int) config('products.image.size', 800));
        $quality = max(1, min(100, (int) config('products.image.quality', 82)));
        $format = self::outputExtension();

        $manager = $this->manager();
        $image = $manager->read($absolutePath);
        if (method_exists($image, 'orient')) {
            $image->orient();
        }

        $image->scaleDown(width: $size, height: $size);

        if ($format === 'png') {
            $canvas = $manager->create($size, $size);
            $canvas->place($image, 'center');
            $output = $canvas;
        } else {
            $background = (string) config('products.image.background', '#F5F5F5');
            $output = $image->contain($size, $size, $background, 'center');
        }

        $temp = sys_get_temp_dir().DIRECTORY_SEPARATOR.'product-img-'.Str::random(32).'.'.$format;

        if ($format === 'png') {
            $output->save($temp);
        } else {
            $output->save($temp, quality: $quality);
        }

        if (! is_file($temp)) {
            throw new RuntimeException('تعذر حفظ الصورة المعالجة.');
        }

        return $temp;
    }

    public function isNormalized(string $absolutePath): bool
    {
        if (! is_file($absolutePath)) {
            return false;
        }

        $size = max(64, (int) config('products.image.size', 800));
        $format = self::outputExtension();

        if (strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION)) !== $format) {
            return false;
        }

        try {
            $image = $this->manager()->read($absolutePath);
        } catch (\Throwable) {
            return false;
        }

        return $image->width() === $size && $image->height() === $size;
    }

    public static function outputExtension(): string
    {
        $format = strtolower((string) config('products.image.format', 'png'));

        return in_array($format, ['jpg', 'jpeg'], true) ? 'jpg' : 'png';
    }

    private function manager(): ImageManagerInterface
    {
        if (extension_loaded('gd')) {
            return ImageManager::gd();
        }

        if (extension_loaded('imagick')) {
            return ImageManager::imagick();
        }

        throw new RuntimeException(
            'امتداد GD أو Imagick غير مفعّل على الخادم. فعّل ext-gd لمعالجة صور المنتجات.'
        );
    }
}
