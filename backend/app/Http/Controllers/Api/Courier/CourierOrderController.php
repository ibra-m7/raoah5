<?php

namespace App\Http\Controllers\Api\Courier;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\CourierOrderResource;
use App\Models\Courier;
use App\Models\Order;
use App\Services\Orders\OrderService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CourierOrderController extends Controller
{
    public function __construct(private readonly OrderService $orders) {}

    public function current(Request $request): JsonResponse
    {
        /** @var Courier $courier */
        $courier = $request->user();

        $orders = Order::query()
            ->with(['items', 'address', 'courier'])
            ->where(function ($query) use ($courier) {
                $query->where(function ($mine) use ($courier) {
                    $mine->where('courier_id', $courier->id)
                        ->whereIn('status', [OrderStatus::Pending, OrderStatus::Preparing, OrderStatus::OnTheWay]);
                });

                if ($courier->canReceiveOrders()) {
                    $query->orWhere(function ($available) {
                        $available->whereNull('courier_id')
                            ->whereIn('status', [OrderStatus::Pending, OrderStatus::Preparing]);
                    });
                }
            })
            ->latest()
            ->get();

        return ApiResponse::success('الطلبات الحالية.', CourierOrderResource::collection($orders)->resolve());
    }

    public function previous(Request $request): JsonResponse
    {
        /** @var Courier $courier */
        $courier = $request->user();

        $orders = Order::query()
            ->with(['items', 'address'])
            ->where('courier_id', $courier->id)
            ->where('status', OrderStatus::Delivered)
            ->latest()
            ->get();

        return ApiResponse::success('الطلبات السابقة.', CourierOrderResource::collection($orders)->resolve());
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        /** @var Courier $courier */
        $courier = $request->user();

        if (! $this->visibleTo($order, $courier)) {
            return ApiResponse::error('لا يمكنك عرض هذا الطلب.', 403);
        }

        $order->load(['items', 'address']);

        return ApiResponse::success('تفاصيل الطلب.', (new CourierOrderResource($order))->resolve());
    }

    public function accept(Request $request, Order $order): JsonResponse
    {
        /** @var Courier $courier */
        $courier = $request->user();
        $updated = $this->orders->acceptByCourier($order, $courier)->load(['items', 'address']);

        return ApiResponse::success('تم قبول الطلب.', (new CourierOrderResource($updated))->resolve());
    }

    public function pickup(Request $request, Order $order): JsonResponse
    {
        /** @var Courier $courier */
        $courier = $request->user();
        $updated = $this->orders->pickupByCourier($order, $courier)->load(['items', 'address']);

        return ApiResponse::success('تم استلام الطلب وهو في الطريق.', (new CourierOrderResource($updated))->resolve());
    }

    public function deliver(Request $request, Order $order): JsonResponse
    {
        /** @var Courier $courier */
        $courier = $request->user();
        $updated = $this->orders->deliverByCourier($order, $courier)->load(['items', 'address']);

        return ApiResponse::success('تم تسليم الطلب.', (new CourierOrderResource($updated))->resolve());
    }

    private function visibleTo(Order $order, Courier $courier): bool
    {
        if ((int) $order->courier_id === (int) $courier->id) {
            return true;
        }

        return $courier->canReceiveOrders()
            && $order->courier_id === null
            && in_array($order->status, [OrderStatus::Pending, OrderStatus::Preparing], true);
    }
}
