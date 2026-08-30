<?php

namespace Tests\Feature;

use App\Http\Requests\Admin\ProductRequest;
use Tests\TestCase;

class ProductImageValidationTest extends TestCase
{
    public function test_image_url_allows_any_text_value(): void
    {
        $rules = (new ProductRequest)->rules();

        $this->assertSame(['nullable', 'string', 'max:2048'], $rules['image_url']);
    }
}
