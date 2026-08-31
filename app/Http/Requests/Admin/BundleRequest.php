<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class BundleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:5000'],
            'image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096'],
            'image_url' => ['nullable', 'url', 'max:2048'],
            'discount_percent' => ['nullable', 'numeric', 'min:0', 'max:99.99'],
            'bundle_price' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['nullable', 'integer', 'min:1', 'max:99'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'اسم السلة',
            'slug' => 'الرابط',
            'summary' => 'الملخص',
            'description' => 'الوصف',
            'image' => 'صورة الغلاف',
            'image_url' => 'رابط صورة الغلاف',
            'discount_percent' => 'نسبة الخصم',
            'bundle_price' => 'سعر السلة',
            'items' => 'منتجات السلة',
            'items.*.product_id' => 'المنتج',
            'items.*.quantity' => 'الكمية',
        ];
    }

    protected function prepareForValidation(): void
    {
        $items = [];
        foreach ((array) $this->input('items', []) as $row) {
            if (! is_array($row)) {
                continue;
            }
            $productId = (int) ($row['product_id'] ?? 0);
            if ($productId <= 0) {
                continue;
            }
            $items[] = [
                'product_id' => $productId,
                'quantity' => max(1, min(99, (int) ($row['quantity'] ?? 1))),
            ];
        }

        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'image_url' => $this->input('image_url') ?: null,
            'discount_percent' => $this->input('discount_percent') !== null && $this->input('discount_percent') !== ''
                ? (float) $this->input('discount_percent')
                : 0,
            'bundle_price' => $this->input('bundle_price') !== null && $this->input('bundle_price') !== ''
                ? (float) $this->input('bundle_price')
                : 0,
            'items' => $items,
        ]);
    }
}
