<?php

namespace Tests\Unit;

use App\Services\Admin\ProductImportService;
use Illuminate\Http\UploadedFile;
use ReflectionMethod;
use Tests\TestCase;
use ZipArchive;

class ProductImportZipTest extends TestCase
{
    public function test_normalize_zip_entry_name_accepts_windows_backslashes(): void
    {
        $this->assertSame(
            'images/3.png',
            ProductImportService::normalizeZipEntryName('images\\3.png'),
        );
        $this->assertSame(
            '1.png',
            ProductImportService::normalizeZipEntryName('/1.png'),
        );
        $this->assertNull(ProductImportService::normalizeZipEntryName('../secret.png'));
        $this->assertNull(ProductImportService::normalizeZipEntryName('folder/'));
    }

    public function test_extracts_images_from_nested_zip_folders(): void
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+ip1sAAAAASUVORK5CYII=');
        $zipPath = tempnam(sys_get_temp_dir(), 'imp').'.zip';

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true);
        $zip->addFromString('folder/1.jpg', $png);
        $zip->addFromString('3.png', $png);
        $zip->close();

        $service = app(ProductImportService::class);
        $method = new ReflectionMethod(ProductImportService::class, 'extractImageZip');
        $file = new UploadedFile($zipPath, 'images.zip', 'application/zip', null, true);

        try {
            [$map] = $method->invoke($service, $file);
            $this->assertArrayHasKey('1', $map);
            $this->assertArrayHasKey('3', $map);
            $this->assertFileExists($map['1']);
            $this->assertFileExists($map['3']);
        } finally {
            @unlink($zipPath);
        }
    }
}
