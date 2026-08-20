<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\PreviewCouponRequest;
use App\Services\Orders\OrderService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class CouponController extends Controller
{
    public function __construct(private readonly OrderService $orders) {}

    public function preview(PreviewCouponRequest $request): JsonResponse
    {
        $quote = $this->orders->previewCoupon($request->user(), $request->validated());

        return ApiResponse::success($quote['message'] ?? 'تم تطبيق الكوبون', $quote);
    }
}
