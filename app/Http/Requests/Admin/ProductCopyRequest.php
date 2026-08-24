<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ProductCopyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'description' => ['nullable', 'string', 'max:5000'],
            'weight_label' => ['nullable', 'string', 'max:80'],
            'quantity_label' => ['nullable', 'string', 'max:120'],
            'piece_count' => ['nullable', 'integer', 'min:1', 'max:9999'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'اسم المنتج',
        ];
    }
}
