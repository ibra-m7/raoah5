<?php

namespace Tests\Unit;

use App\Support\Media;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Config::set('app.url', 'https://raoah5.onrender.com');
    }

    public function test_is_missing_local_detects_missing_self_hosted_storage_url(): void
    {
        $url = 'https://raoah5.onrender.com/storage/products/missing.png';

        $this->assertTrue(Media::isMissingLocal($url));
    }

    public function test_is_missing_local_returns_false_when_file_exists(): void
    {
        Storage::disk('public')->put('products/exists.png', 'image');

        $this->assertFalse(Media::isMissingLocal('products/exists.png'));
        $this->assertFalse(Media::isMissingLocal('https://raoah5.onrender.com/storage/products/exists.png'));
    }

    public function test_normalize_stored_path_converts_self_hosted_url_to_relative_path(): void
    {
        $this->assertSame(
            'products/abc.png',
            Media::normalizeStoredPath('https://raoah5.onrender.com/storage/products/abc.png'),
        );
    }

    public function test_store_returns_null_when_no_file(): void
    {
        $this->assertNull(Media::store(null, 'products'));
    }
}
