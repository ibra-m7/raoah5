<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'parent_id' => [
                Rule::requiredIf(fn () => $this->input('level') !== 'root'),
                'nullable',
                'integer',
                'exists:categories,id',
            ],
            'level' => ['nullable', 'in:root,category,sub'],
            'icon' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,svg,gif,bmp,heic,avif', 'max:4096'],
            'image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,gif,bmp,heic,avif', 'max:8192'],
            'color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        $parent = match ($this->input('level')) {
            'sub' => 'القسم',
            'category' => 'التبويب',
            default => 'القسم الأب',
        };

        $name = match ($this->input('level')) {
            'sub' => 'اسم التصنيف',
            'category' => 'اسم القسم',
            default => 'اسم التبويب',
        };

        return [
            'name' => $name,
            'parent_id' => $parent,
            'icon' => 'الأيقونة',
            'image' => 'الصورة',
            'color' => 'اللون',
            'sort_order' => 'الترتيب',
            'is_active' => 'الحالة',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'parent_id' => $this->input('level') === 'root' ? null : ($this->input('parent_id') ?: null),
            'color' => $this->input('color') ?: null,
        ]);
    }
}
