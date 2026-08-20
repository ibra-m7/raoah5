<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class SaveLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'city' => ['nullable', 'string', 'max:80'],
            'district' => ['nullable', 'string', 'max:80'],
            'street' => ['nullable', 'string', 'max:120'],
            'details' => ['required', 'string', 'min:3', 'max:255'],
            'label' => ['required', 'string', 'min:2', 'max:80'],
        ];
    }

    public function messages(): array
    {
        return [
            'latitude.required' => 'حدد موقعك للمتابعة.',
            'longitude.required' => 'حدد موقعك للمتابعة.',
            'latitude.between' => 'إحداثيات الموقع غير صالحة.',
            'longitude.between' => 'إحداثيات الموقع غير صالحة.',
            'label.required' => 'أدخل اسم العنوان.',
            'label.min' => 'اسم العنوان قصير جداً.',
            'details.required' => 'أدخل وصف العنوان.',
            'details.min' => 'وصف العنوان قصير جداً.',
        ];
    }
}
