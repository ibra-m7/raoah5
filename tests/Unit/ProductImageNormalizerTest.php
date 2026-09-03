<?php

namespace Tests\Unit;

use App\Support\Image\ProductImageNormalizer;
use App\Support\Media;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductImageNormalizerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! extension_loaded('gd') && ! extension_loaded('imagick')) {
            $this->markTestSkipped('GD or Imagick extension is required.');
        }
    }

    public function test_portrait_landscape_and_square_outputs_are_800_by_800(): void
    {
        $normalizer = new ProductImageNormalizer;
        $size = (int) config('products.image.size', 800);

        foreach ([
            'portrait' => $this->makeSampleImage(400, 800, 'ff0000'),
            'landscape' => $this->makeSampleImage(800, 400, '00ff00'),
            'square' => $this->makeSampleImage(500, 500, '0000ff'),
        ] as $label => $source) {
            $output = $normalizer->normalizePath($source);
            try {
                $manager = extension_loaded('gd')
                    ? \Intervention\Image\ImageManager::gd()
                    : \Intervention\Image\ImageManager::imagick();
                $image = $manager->read($output);
                $this->assertSame($size, $image->width(), "Width mismatch for {$label}");
                $this->assertSame($size, $image->height(), "Height mismatch for {$label}");
                $this->assertStringEndsWith('.png', $output, "Output should be PNG for {$label}");
            } finally {
                @unlink($output);
                @unlink($source);
            }
        }
    }

    public function test_landscape_output_has_transparent_canvas_corners(): void
    {
        $normalizer = new ProductImageNormalizer;
        $source = $this->makeSampleImage(800, 400, '00ff00');
        $output = $normalizer->normalizePath($source);

        try {
            $this->assertTrue($this->cornerPixelIsTransparent($output, 0, 0));
        } finally {
            @unlink($output);
            @unlink($source);
        }
    }

    public function test_transparent_png_preserves_alpha_on_canvas_without_crop(): void
    {
        $normalizer = new ProductImageNormalizer;
        $source = $this->makeTransparentPng(600, 300);
        $output = $normalizer->normalizePath($source);

        try {
            $manager = extension_loaded('gd')
                ? \Intervention\Image\ImageManager::gd()
                : \Intervention\Image\ImageManager::imagick();
            $image = $manager->read($output);
            $this->assertSame(800, $image->width());
            $this->assertSame(800, $image->height());
            $this->assertTrue($this->cornerPixelIsTransparent($output, 0, 0));
        } finally {
            @unlink($output);
            @unlink($source);
        }
    }

    public function test_media_store_normalizes_product_uploads_to_png(): void
    {
        Storage::fake('public');

        $source = $this->makeSampleImage(900, 300, 'abcdef');
        $upload = new UploadedFile($source, 'sample.png', 'image/png', null, true);

        $path = Media::store($upload, 'products');

        $this->assertIsString($path);
        $this->assertStringStartsWith('products/', $path);
        $this->assertStringEndsWith('.png', $path);
        Storage::disk('public')->assertExists($path);

        $manager = extension_loaded('gd')
            ? \Intervention\Image\ImageManager::gd()
            : \Intervention\Image\ImageManager::imagick();
        $stored = $manager->read(Storage::disk('public')->path($path));
        $this->assertSame(800, $stored->width());
        $this->assertSame(800, $stored->height());
        $this->assertTrue($this->cornerPixelIsTransparent(Storage::disk('public')->path($path), 0, 0));

        @unlink($source);
    }

    private function cornerPixelIsTransparent(string $path, int $x, int $y): bool
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('GD extension is required for alpha pixel assertions.');
        }

        $image = imagecreatefrompng($path);
        if ($image === false) {
            return false;
        }

        imagesavealpha($image, true);
        $rgba = imagecolorat($image, $x, $y);
        imagedestroy($image);

        $alpha = ($rgba >> 24) & 0x7F;

        return $alpha >= 100;
    }

    private function makeSampleImage(int $width, int $height, string $hex): string
    {
        $image = imagecreatetruecolor($width, $height);
        $rgb = sscanf($hex, '%02x%02x%02x');
        $color = imagecolorallocate($image, $rgb[0], $rgb[1], $rgb[2]);
        imagefilledrectangle($image, 0, 0, $width, $height, $color);

        $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.'product-test-'.uniqid('', true).'.png';
        imagepng($image, $path);
        imagedestroy($image);

        return $path;
    }

    private function makeTransparentPng(int $width, int $height): string
    {
        $image = imagecreatetruecolor($width, $height);
        imagesavealpha($image, true);
        $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
        imagefill($image, 0, 0, $transparent);
        $solid = imagecolorallocate($image, 20, 40, 60);
        imagefilledrectangle($image, 50, 50, $width - 50, $height - 50, $solid);

        $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.'product-alpha-'.uniqid('', true).'.png';
        imagepng($image, $path);
        imagedestroy($image);

        return $path;
    }
}
