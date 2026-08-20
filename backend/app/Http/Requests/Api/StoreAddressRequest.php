<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'min:2', 'max:80'],
            'details' => ['required', 'string', 'min:3', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'city' => ['nullable', 'string', 'max:80'],
            'district' => ['nullable', 'string', 'max:80'],
            'street' => ['nullable', 'string', 'max:120'],
            'is_default' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'label.required' => 'أدخل اسم العنوان.',
            'label.min' => 'اسم العنوان قصير جداً.',
            'details.required' => 'أدخل وصف العنوان.',
            'details.min' => 'وصف العنوان قصير جداً.',
            'latitude.required' => 'حدد الموقع على الخريطة.',
            'longitude.required' => 'حدد الموقع على الخريطة.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('label')) {
            $this->merge([
                'label' => trim(preg_replace('/\s+/u', ' ', (string) $this->input('label')) ?? ''),
            ]);
        }
        if ($this->has('details')) {
            $this->merge([
                'details' => trim((string) $this->input('details')),
            ]);
        }
    }
}
