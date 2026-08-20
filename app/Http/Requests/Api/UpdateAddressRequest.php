<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'label' => ['sometimes', 'string', 'min:2', 'max:80'],
            'details' => ['sometimes', 'required', 'string', 'min:3', 'max:255'],
            'latitude' => ['sometimes', 'numeric', 'between:-90,90'],
            'longitude' => ['sometimes', 'numeric', 'between:-180,180'],
            'city' => ['sometimes', 'nullable', 'string', 'max:80'],
            'district' => ['sometimes', 'nullable', 'string', 'max:80'],
            'street' => ['sometimes', 'nullable', 'string', 'max:120'],
            'is_default' => ['sometimes', 'boolean'],
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
