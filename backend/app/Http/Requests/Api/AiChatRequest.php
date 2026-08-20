<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class AiChatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:2000'],
            'conversation_id' => ['nullable', 'integer', 'min:1'],
            'guest_token' => ['nullable', 'string', 'max:64'],
            'intent' => ['nullable', 'in:chat,complement'],
            'product_id' => ['nullable', 'string', 'max:32'],
        ];
    }

    public function messages(): array
    {
        return [
            'message.required' => 'اكتب رسالتك أولاً.',
            'message.max' => 'الرسالة طويلة جداً.',
        ];
    }
}
