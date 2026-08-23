<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DynamicPageResource;
use App\Services\Catalog\CatalogService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class DynamicPageController extends Controller
{
    public function __construct(private readonly CatalogService $catalog) {}

    public function show(string $page): JsonResponse
    {
        $model = $this->catalog->findDynamicPage($page);
        if ($model === null) {
            return ApiResponse::error('الصفحة غير موجودة.', 404);
        }

        return ApiResponse::success(
            'تفاصيل الصفحة',
            (new DynamicPageResource($model))->resolve(),
        );
    }
}
