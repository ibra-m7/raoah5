<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BundleResource;
use App\Services\Catalog\CatalogService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class BundleController extends Controller
{
    public function __construct(private readonly CatalogService $catalog) {}

    public function show(string $bundle): JsonResponse
    {
        $model = $this->catalog->findBundle($bundle);
        if ($model === null) {
            return ApiResponse::error('السلة غير موجودة.', 404);
        }

        return ApiResponse::success(
            'تفاصيل السلة',
            (new BundleResource($model))->resolve(),
        );
    }
}
