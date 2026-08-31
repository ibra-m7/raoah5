<?php

namespace App\Http\Requests\Admin;

use App\Models\HomeSection;
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
            'title_color' => ['nullable', 'string', 'max:7', 'regex:/^#?[0-9A-Fa-f]{6}$/'],
            'subtitle_color' => ['nullable', 'string', 'max:7', 'regex:/^#?[0-9A-Fa-f]{6}$/'],
            'background_color' => ['nullable', 'string', 'max:7', 'regex:/^#?[0-9A-Fa-f]{6}$/'],
            'background_image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096'],
            'background_image_url' => ['nullable', 'url', 'max:2048'],
            'remove_background_image' => ['nullable', 'boolean'],
            'background_mode' => ['nullable', 'in:color,image'],
            'content_type' => ['required', Rule::in([HomeSection::CONTENT_PRODUCTS, HomeSection::CONTENT_BUNDLES])],
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
            'auto_scroll_cards' => ['nullable', 'boolean'],
            'show_title_icon' => ['nullable', 'boolean'],
            'emphasize_subtitle' => ['nullable', 'boolean'],
            'use_default_background' => ['nullable', 'boolean'],
            'use_default_title_color' => ['nullable', 'boolean'],
            'use_default_subtitle_color' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'الاسم التجاري',
            'subtitle' => 'العنوان الفرعي',
            'title_color' => 'لون اسم القسم',
            'subtitle_color' => 'لون العنوان الفرعي',
            'background_color' => 'لون خلفية القسم',
            'background_image' => 'صورة خلفية القسم',
            'background_image_url' => 'رابط صورة الخلفية',
            'content_type' => 'نوع المحتوى',
            'key' => 'المعرّف',
            'product_ids' => 'المنتجات',
            'auto_scroll_cards' => 'تحريك الكروت',
            'show_title_icon' => 'أيقونة العنوان',
            'emphasize_subtitle' => 'تمييز العنوان الفرعي',
        ];
    }

    protected function prepareForValidation(): void
    {
        $backgroundMode = $this->input('background_mode', 'color');

        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'auto_scroll_cards' => $this->boolean('auto_scroll_cards'),
            'show_title_icon' => $this->boolean('show_title_icon'),
            'emphasize_subtitle' => $this->boolean('emphasize_subtitle'),
            'remove_background_image' => $this->boolean('remove_background_image'),
            'content_type' => $this->input('content_type') ?: HomeSection::CONTENT_PRODUCTS,
            'key' => null,
            'subtitle' => $this->input('subtitle') ?: null,
            'title_color' => $this->boolean('use_default_title_color')
                ? null
                : ($this->input('title_color') ?: null),
            'subtitle_color' => $this->boolean('use_default_subtitle_color')
                ? null
                : ($this->input('subtitle_color') ?: null),
            'background_color' => $backgroundMode === 'image' || $this->boolean('use_default_background')
                ? null
                : ($this->input('background_color') ?: null),
            'background_image_url' => $backgroundMode === 'color'
                ? null
                : ($this->input('background_image_url') ?: null),
            'product_ids' => array_values(array_filter((array) $this->input('product_ids', []))),
        ]);
    }
}
