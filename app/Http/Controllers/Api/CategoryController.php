<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductResource;
use App\Models\Category;
use App\Services\Catalog\CatalogService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function __construct(private readonly CatalogService $catalog) {}

    public function index(): JsonResponse
    {
        return ApiResponse::success('الأقسام', $this->catalog->categories());
    }

    public function show(Request $request, Category $category): JsonResponse
    {
        if (! $category->is_active) {
            return ApiResponse::error('القسم غير متاح.', 404);
        }

        $category->loadCount(['products as products_count' => fn ($q) => $q->active()]);
        $category->load(['children' => fn ($q) => $q->active()]);

        $products = $this->catalog->paginateProducts([
            'category_id' => $category->id,
            'q' => $request->query('q'),
            'per_page' => $request->query('per_page', 24),
        ]);

        return ApiResponse::success('القسم', [
            'category' => (new CategoryResource($category))->resolve(),
            'products' => ProductResource::collection($products->getCollection())->resolve(),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ]);
    }
}
