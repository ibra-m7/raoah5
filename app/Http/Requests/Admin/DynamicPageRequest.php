<?php

namespace App\Http\Requests\Admin;

use App\Enums\DynamicPagePlacement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DynamicPageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'show_title' => ['nullable', 'boolean'],
            'banner_image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096'],
            'banner_image_url' => ['nullable', 'url', 'max:2048'],
            'appbar_image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096'],
            'appbar_image_url' => ['nullable', 'url', 'max:2048'],
            'product_ids' => ['nullable', 'array'],
            'product_ids.*' => ['integer', 'exists:products,id'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
            'placement' => ['required', Rule::enum(DynamicPagePlacement::class)],
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'العنوان',
            'banner_image' => 'صورة البنر',
            'banner_image_url' => 'رابط صورة البنر',
            'appbar_image' => 'صورة رأس الصفحة',
            'appbar_image_url' => 'رابط صورة الرأس',
            'product_ids' => 'المنتجات',
            'placement' => 'مكان الظهور',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'show_title' => $this->boolean('show_title'),
            'banner_image_url' => $this->input('banner_image_url') ?: null,
            'appbar_image_url' => $this->input('appbar_image_url') ?: null,
        ]);
    }
}
