<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Catalog\CatalogService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    public function __construct(private readonly CatalogService $catalog) {}

    public function __invoke(): JsonResponse
    {
        return ApiResponse::success(
            'واجهة المتجر',
            $this->catalog->storefront(Auth::guard('sanctum')->user())
        );
    }
}
