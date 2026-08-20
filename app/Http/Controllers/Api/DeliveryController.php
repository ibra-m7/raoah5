<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\QuoteDeliveryRequest;
use App\Services\Delivery\DeliveryService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class DeliveryController extends Controller
{
    public function __construct(private readonly DeliveryService $delivery) {}

    public function quote(QuoteDeliveryRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = $request->user();
        $address = null;
        if (! empty($data['address_id'])) {
            $address = $user->addresses()->whereKey($data['address_id'])->first();
        }
        $address ??= $user->addresses()->orderByDesc('is_default')->first();

        $quote = $this->delivery->quote(
            $user,
            $address,
            (float) ($data['subtotal'] ?? 0),
        );

        return ApiResponse::success('رسوم التوصيل', $quote->toArray());
    }
}
