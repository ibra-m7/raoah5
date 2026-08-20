<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CouponAppliesTo;
use App\Enums\CouponType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CouponRequest;
use App\Models\Coupon;
use App\Services\Admin\CouponAdminService;
use App\Support\AppStrings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CouponController extends Controller
{
    public function __construct(private readonly CouponAdminService $coupons) {}

    public function index(Request $request): View
    {
        $filters = $request->only(['q', 'status']);

        return view('admin.coupons.index', [
            'title' => AppStrings::NAV_COUPONS,
            'coupons' => $this->coupons->paginate($filters),
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        return view('admin.coupons.create', [
            'title' => AppStrings::ADD_COUPON,
            'coupon' => new Coupon([
                'is_active' => true,
                'type' => CouponType::Percent,
                'applies_to' => CouponAppliesTo::All,
                'usage_limit_per_user' => 1,
                'value' => 10,
            ]),
            'types' => CouponType::cases(),
            'scopes' => CouponAppliesTo::cases(),
            'products' => $this->coupons->productOptions(),
            'categories' => $this->coupons->categoryOptions(),
        ]);
    }

    public function store(CouponRequest $request): RedirectResponse
    {
        $this->coupons->create($request->validated());

        return redirect()
            ->route('admin.coupons.index')
            ->with('success', AppStrings::COUPON_CREATED);
    }

    public function edit(Coupon $coupon): View
    {
        $coupon->load(['products:id', 'categories:id']);

        return view('admin.coupons.edit', [
            'title' => AppStrings::EDIT_COUPON,
            'coupon' => $coupon,
            'types' => CouponType::cases(),
            'scopes' => CouponAppliesTo::cases(),
            'products' => $this->coupons->productOptions(),
            'categories' => $this->coupons->categoryOptions(),
        ]);
    }

    public function update(CouponRequest $request, Coupon $coupon): RedirectResponse
    {
        $this->coupons->update($coupon, $request->validated());

        return redirect()
            ->route('admin.coupons.index')
            ->with('success', AppStrings::COUPON_UPDATED);
    }

    public function destroy(Coupon $coupon): RedirectResponse
    {
        try {
            $this->coupons->delete($coupon);
        } catch (ValidationException $e) {
            return redirect()
                ->route('admin.coupons.index')
                ->with('error', collect($e->errors())->flatten()->first() ?: 'تعذر الحذف.');
        }

        return redirect()
            ->route('admin.coupons.index')
            ->with('success', AppStrings::COUPON_DELETED);
    }
}
