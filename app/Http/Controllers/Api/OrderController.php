<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CancelOrderRequest;
use App\Http\Requests\Api\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Services\Orders\OrderService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orders) {}

    public function index(Request $request): JsonResponse
    {
        $page = $this->orders->paginateForUser($request->user());

        return ApiResponse::paginated(
            'الطلبات',
            OrderResource::collection($page->getCollection())->resolve(),
            $page,
        );
    }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        $order = $this->orders->create($request->user(), $request->validated());

        return ApiResponse::success('تم إنشاء الطلب بنجاح', (new OrderResource($order))->resolve(), 201);
    }

    public function show(Request $request, string $order): JsonResponse
    {
        $model = $this->orders->findForUser($request->user(), $order);
        if ($model === null) {
            return ApiResponse::error('الطلب غير موجود.', 404);
        }

        return ApiResponse::success('تفاصيل الطلب', (new OrderResource($model))->resolve());
    }

    public function cancel(CancelOrderRequest $request, string $order): JsonResponse
    {
        $model = $this->orders->cancelForUser(
            $request->user(),
            $order,
            $request->validated('reason'),
        );

        return ApiResponse::success('تم إلغاء الطلب', (new OrderResource($model))->resolve());
    }
}
