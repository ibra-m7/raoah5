<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QuickGiftProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0', 'max:999999'],
            'image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096'],
            'current_product_id' => ['nullable', 'integer', 'exists:products,id'],
            'main_product_ids' => ['nullable', 'array'],
            'main_product_ids.*' => ['integer', 'exists:products,id'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'اسم منتج الهدية',
            'category_id' => 'التصنيف',
            'price' => 'السعر',
            'stock' => 'المخزون',
            'image' => 'الصورة',
            'current_product_id' => 'المنتج الحالي',
            'main_product_ids' => 'المنتجات الرئيسية',
            'main_product_ids.*' => 'منتج رئيسي',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'current_product_id' => $this->filled('current_product_id')
                ? $this->integer('current_product_id')
                : null,
            'main_product_ids' => array_values(array_filter(
                array_map('intval', (array) $this->input('main_product_ids', [])),
                fn (int $id) => $id > 0,
            )),
        ]);
    }

    /**
     * @return list<int>
     */
    public function mainProductIds(): array
    {
        $ids = collect($this->input('main_product_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0);

        $current = (int) ($this->input('current_product_id') ?? 0);
        if ($current > 0) {
            $ids->prepend($current);
        }

        return $ids->unique()->values()->all();
    }
}
