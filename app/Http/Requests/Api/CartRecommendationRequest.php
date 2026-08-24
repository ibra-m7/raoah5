<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class CartRecommendationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_ids' => ['required', 'array', 'min:1', 'max:40'],
            'product_ids.*' => ['required'],
        ];
    }

    /**
     * @return list<int>
     */
    public function productIds(): array
    {
        $ids = [];
        foreach ($this->validated('product_ids') as $id) {
            $value = (int) $id;
            if ($value > 0) {
                $ids[] = $value;
            }
        }

        return array_values(array_unique($ids));
    }
}
