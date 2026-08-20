<?php

namespace App\Http\Requests\Admin;

use App\Enums\CouponAppliesTo;
use App\Enums\CouponType;
use App\Models\Coupon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $coupon = $this->route('coupon');
        $id = $coupon instanceof Coupon ? $coupon->id : null;
        $type = $this->input('type');
        $applies = $this->input('applies_to');

        return [
            'code' => [
                'required',
                'string',
                'min:3',
                'max:40',
                'regex:/^[A-Z0-9_-]+$/',
                Rule::unique('coupons', 'code')->ignore($id),
            ],
            'title' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'type' => ['required', Rule::enum(CouponType::class)],
            'value' => [
                Rule::requiredIf($type !== CouponType::FreeShipping->value),
                'nullable',
                'numeric',
                'min:0',
                $type === CouponType::Percent->value ? 'max:100' : 'max:99999',
            ],
            'min_subtotal' => ['nullable', 'numeric', 'min:0'],
            'max_discount' => ['nullable', 'numeric', 'min:0'],
            'applies_to' => ['required', Rule::enum(CouponAppliesTo::class)],
            'product_ids' => [
                Rule::requiredIf($applies === CouponAppliesTo::Products->value),
                'nullable',
                'array',
            ],
            'product_ids.*' => ['integer', 'exists:products,id'],
            'category_ids' => [
                Rule::requiredIf($applies === CouponAppliesTo::Categories->value),
                'nullable',
                'array',
            ],
            'category_ids.*' => ['integer', 'exists:categories,id'],
            'usage_limit' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'usage_limit_per_user' => ['required', 'integer', 'min:1', 'max:99'],
            'first_order_only' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ];
    }

    public function attributes(): array
    {
        return [
            'code' => 'كود الكوبون',
            'title' => 'العنوان',
            'type' => 'نوع الخصم',
            'value' => 'قيمة الخصم',
            'min_subtotal' => 'الحد الأدنى للسلة',
            'max_discount' => 'سقف الخصم',
            'applies_to' => 'نطاق التطبيق',
            'product_ids' => 'المنتجات',
            'category_ids' => 'الأقسام',
            'usage_limit' => 'حد الاستخدام الكلي',
            'usage_limit_per_user' => 'حد الاستخدام لكل عميل',
            'starts_at' => 'تاريخ البداية',
            'ends_at' => 'تاريخ الانتهاء',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => Coupon::normalizeCode($this->input('code')),
            'is_active' => $this->boolean('is_active'),
            'first_order_only' => $this->boolean('first_order_only'),
            'title' => $this->input('title') ?: null,
            'description' => $this->input('description') ?: null,
            'usage_limit' => $this->filled('usage_limit') ? $this->input('usage_limit') : null,
            'max_discount' => $this->filled('max_discount') ? $this->input('max_discount') : null,
            'min_subtotal' => $this->input('min_subtotal') ?: 0,
            'starts_at' => $this->input('starts_at') ?: null,
            'ends_at' => $this->input('ends_at') ?: null,
            'product_ids' => array_filter((array) $this->input('product_ids', [])),
            'category_ids' => array_filter((array) $this->input('category_ids', [])),
        ]);
    }
}
