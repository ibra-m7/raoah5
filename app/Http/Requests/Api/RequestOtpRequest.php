<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class RequestOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'min:9', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => 'أدخل الرقم هنا.',
            'phone.min' => 'رقم الجوال قصير جداً.',
            'phone.max' => 'رقم الجوال غير صالح.',
        ];
    }

    public function attributes(): array
    {
        return [
            'phone' => 'رقم الهاتف',
        ];
    }
}
