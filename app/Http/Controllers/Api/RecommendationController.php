<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CartRecommendationRequest;
use App\Services\Catalog\CatalogService;
use App\Services\Catalog\ProductRecommendationService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class RecommendationController extends Controller
{
    public function __construct(
        private readonly CatalogService $catalog,
        private readonly ProductRecommendationService $recommendations,
    ) {}

    public function forProduct(string $product): JsonResponse
    {
        $model = $this->catalog->findProduct($product);
        if ($model === null) {
            return ApiResponse::error('المنتج غير موجود.', 404);
        }

        return ApiResponse::success(
            'توصيات المنتج',
            $this->recommendations->forProduct($model)
        );
    }

    public function forCart(CartRecommendationRequest $request): JsonResponse
    {
        return ApiResponse::success(
            'توصيات السلة',
            $this->recommendations->forCart(
                $request->productIds(),
                Auth::guard('sanctum')->user()
            )
        );
    }
}
