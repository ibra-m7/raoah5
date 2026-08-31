<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Pickup\PickupSlotService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class PickupController extends Controller
{
    public function __construct(private readonly PickupSlotService $slots) {}

    public function slots(): JsonResponse
    {
        return ApiResponse::success('فترات التجهيز.', $this->slots->calendar());
    }
}
