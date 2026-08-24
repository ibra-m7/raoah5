<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentMethodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $method = $this->route('store_payment_method')
            ?? $this->route('payment_method');

        $id = $method instanceof \App\Models\StorePaymentMethod ? $method->id : null;

        return [
            'slug' => [
                'required',
                'string',
                'max:40',
                'regex:/^[a-z0-9_]+$/',
                Rule::unique('store_payment_methods', 'slug')->ignore($id),
            ],
            'label' => ['required', 'string', 'max:80'],
            'hint' => ['nullable', 'string', 'max:180'],
            'icon' => ['nullable', 'string', 'max:60'],
            'icon_file' => ['nullable', 'image', 'max:2048'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'slug' => 'المعرّف',
            'label' => 'الاسم',
            'hint' => 'الوصف',
            'icon' => 'الأيقونة',
            'icon_file' => 'شعار طريقة الدفع',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'slug' => strtolower(trim((string) $this->input('slug'))),
            'hint' => $this->input('hint') ?: null,
        ]);
    }
}
