<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Services\Catalog\CatalogService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(private readonly CatalogService $catalog) {}

    public function index(Request $request): JsonResponse
    {
        $page = $this->catalog->paginateProducts($request->only([
            'q', 'search', 'category_id', 'offers', 'featured', 'per_page',
        ]));

        return ApiResponse::paginated(
            'المنتجات',
            ProductResource::collection($page->getCollection())->resolve(),
            $page,
        );
    }

    public function show(string $product): JsonResponse
    {
        $model = $this->catalog->findProduct($product);
        if ($model === null) {
            return ApiResponse::error('المنتج غير موجود.', 404);
        }

        return ApiResponse::success('تفاصيل المنتج', (new ProductResource($model))->resolve());
    }
}
