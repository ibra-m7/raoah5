<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SplashScreenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:120'],
            'media_type' => ['required', Rule::in(['image', 'video'])],
            'media_file' => ['nullable', 'file', 'max:51200'],
            'media_url' => ['nullable', 'string', 'max:2048'],
            'duration_ms' => ['nullable', 'integer', 'min:800', 'max:30000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'العنوان',
            'media_type' => 'نوع الوسائط',
            'media_file' => 'الملف',
            'media_url' => 'رابط الوسائط',
            'duration_ms' => 'مدة العرض',
            'sort_order' => 'الترتيب',
            'is_active' => 'التفعيل',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
        ]);
    }
}
