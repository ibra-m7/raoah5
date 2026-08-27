<?php

namespace App\Http\Requests\Admin;

use App\Enums\BannerLinkType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BannerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $creating = $this->isMethod('post');

        return [
            'title' => ['required', 'string', 'max:255'],
            'show_title' => ['nullable', 'boolean'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'image' => [$creating ? 'required' : 'nullable', 'file', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096'],
            'image_url' => ['nullable', 'url', 'max:2048'],
            'link_type' => ['required', Rule::enum(BannerLinkType::class)],
            'link_id' => [
                'nullable',
                'integer',
                Rule::requiredIf(in_array($this->input('link_type'), [
                    BannerLinkType::Product->value,
                    BannerLinkType::Category->value,
                    BannerLinkType::Page->value,
                ], true)),
                Rule::when($this->input('link_type') === BannerLinkType::Product->value, ['exists:products,id']),
                Rule::when($this->input('link_type') === BannerLinkType::Category->value, ['exists:categories,id']),
                Rule::when($this->input('link_type') === BannerLinkType::Page->value, ['exists:dynamic_pages,id']),
            ],
            'link_product_id' => ['nullable', 'integer', 'exists:products,id'],
            'link_category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'link_page_id' => ['nullable', 'integer', 'exists:dynamic_pages,id'],
            'link_url' => [
                'nullable',
                'url',
                'max:2048',
                Rule::requiredIf($this->input('link_type') === BannerLinkType::Url->value),
            ],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'العنوان',
            'subtitle' => 'العنوان الفرعي',
            'image' => 'الصورة',
            'image_url' => 'رابط الصورة',
            'link_type' => 'نوع الرابط',
            'link_id' => 'الوجهة',
            'link_url' => 'الرابط',
            'starts_at' => 'تاريخ البداية',
            'ends_at' => 'تاريخ النهاية',
        ];
    }

    protected function prepareForValidation(): void
    {
        $type = $this->input('link_type');
        $linkId = match ($type) {
            BannerLinkType::Product->value => $this->input('link_product_id'),
            BannerLinkType::Category->value => $this->input('link_category_id'),
            BannerLinkType::Page->value => $this->input('link_page_id'),
            default => null,
        };

        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'show_title' => $this->boolean('show_title'),
            'link_id' => $linkId ?: null,
            'link_url' => $this->input('link_url') ?: null,
            'image_url' => $this->input('image_url') ?: null,
            'subtitle' => $this->input('subtitle') ?: null,
            'starts_at' => $this->input('starts_at') ?: null,
            'ends_at' => $this->input('ends_at') ?: null,
        ]);
    }
}
