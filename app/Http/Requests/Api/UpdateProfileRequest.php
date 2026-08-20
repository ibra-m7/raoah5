<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'min:3',
                'max:80',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $name = trim(preg_replace('/\s+/u', ' ', (string) $value) ?? '');
                    if ($name === 'عميل') {
                        $fail('أدخل اسمك الحقيقي.');
                    }
                    if (count(preg_split('/\s+/u', $name) ?: []) < 2) {
                        $fail('أدخل الاسم الثنائي على الأقل.');
                    }
                },
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('name')) {
            $this->merge([
                'name' => trim(preg_replace('/\s+/u', ' ', (string) $this->input('name')) ?? ''),
            ]);
        }
    }

    public function messages(): array
    {
        return [
            'name.required' => 'أدخل اسمك الكامل.',
            'name.min' => 'أدخل الاسم الثنائي على الأقل.',
        ];
    }
}
