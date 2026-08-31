<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class CourierLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    public function attributes(): array
    {
        return [
            'phone' => 'رقم الجوال',
            'password' => 'كلمة المرور',
        ];
    }

    protected function prepareForValidation(): void
    {
        $rawPhone = trim((string) $this->input('phone', ''));
        $password = (string) $this->input('password', '');

        $this->merge([
            'phone' => $rawPhone,
            'password' => $password,
        ]);
    }
}
