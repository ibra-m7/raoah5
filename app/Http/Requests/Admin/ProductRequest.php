<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:64'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'description' => ['nullable', 'string', 'max:5000'],
            'price' => ['required', 'numeric', 'min:0'],
            'discount_price' => ['nullable', 'numeric', 'min:0', 'lt:price'],
            'stock' => ['required', 'integer', 'min:0', 'max:999999'],
            'piece_count' => ['nullable', 'integer', 'min:1', 'max:9999'],
            'weight_label' => ['nullable', 'string', 'max:80'],
            'quantity_label' => ['nullable', 'string', 'max:120'],
            'image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096'],
            'image_url' => ['nullable', 'url', 'max:2048'],
            'benefits' => ['nullable', 'string', 'max:3000'],
            'keywords' => ['nullable', 'string', 'max:1000'],
            'usage_instructions' => ['nullable', 'string', 'max:3000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'اسم المنتج',
            'sku' => 'رمز المنتج',
            'category_id' => 'القسم',
            'description' => 'الوصف',
            'price' => 'السعر',
            'discount_price' => 'سعر العرض',
            'stock' => 'المخزون',
            'piece_count' => 'العدد',
            'weight_label' => 'الوزن',
            'quantity_label' => 'وصف الكمية',
            'image' => 'الصورة',
            'image_url' => 'رابط الصورة',
            'benefits' => 'الفوائد',
            'keywords' => 'كلمات البحث',
            'usage_instructions' => 'طريقة الاستخدام',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'is_featured' => $this->boolean('is_featured'),
            'discount_price' => $this->filled('discount_price') ? $this->input('discount_price') : null,
            'sku' => $this->input('sku') ?: null,
            'image_url' => $this->input('image_url') ?: null,
            'piece_count' => $this->filled('piece_count') ? $this->input('piece_count') : null,
            'weight_label' => $this->input('weight_label') ?: null,
            'quantity_label' => $this->input('quantity_label') ?: null,
        ]);
    }
}
