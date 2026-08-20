<?php

namespace App\Http\Requests\Api;

use App\Enums\DevicePlatform;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterDeviceTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'max:4096'],
            'platform' => ['required', Rule::enum(DevicePlatform::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'token.required' => 'رمز الجهاز مطلوب.',
            'platform.required' => 'حدد نوع الجهاز.',
        ];
    }
}
