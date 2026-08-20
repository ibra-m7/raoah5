<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class CancelOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['nullable', 'string', 'max:250'],
        ];
    }

    public function attributes(): array
    {
        return [
            'reason' => 'سبب الإلغاء',
        ];
    }
}
