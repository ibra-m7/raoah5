<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class HomeSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $section = $this->route('home_section');

        return [
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'display_style' => ['required', 'in:best_prices,most_requested,fresh_groceries,general'],
            'key' => [
                'nullable',
                'string',
                'max:64',
                Rule::unique('home_sections', 'key')->ignore($section),
            ],
            'product_ids' => ['nullable', 'array'],
            'product_ids.*' => ['integer', 'exists:products,id'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'الاسم التجاري',
            'subtitle' => 'العنوان الفرعي',
            'display_style' => 'شكل العرض',
            'key' => 'شكل العرض',
            'product_ids' => 'المنتجات',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'display_style' => $this->input('display_style') ?: 'general',
            'key' => $this->input('display_style') === 'general'
                ? null
                : $this->input('display_style'),
            'subtitle' => $this->input('subtitle') ?: null,
            'product_ids' => array_values(array_filter((array) $this->input('product_ids', []))),
        ]);
    }
}
