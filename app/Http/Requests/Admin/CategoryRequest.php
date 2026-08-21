<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
            'icon' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,svg,gif', 'max:2048'],
            'image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096'],
            'color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
            'display_section_ids' => ['nullable', 'array'],
            'display_section_ids.*' => ['integer', 'exists:display_sections,id'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'اسم القسم',
            'parent_id' => 'القسم الأب',
            'icon' => 'الأيقونة',
            'image' => 'الصورة',
            'color' => 'اللون',
            'sort_order' => 'الترتيب',
            'is_active' => 'الحالة',
            'display_section_ids' => 'مجموعة تبويب الأقسام',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'parent_id' => $this->input('parent_id') ?: null,
            'color' => $this->input('color') ?: null,
        ]);
    }
}
