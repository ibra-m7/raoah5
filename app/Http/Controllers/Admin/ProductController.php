<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductCopyRequest;
use App\Http\Requests\Admin\ProductImportRequest;
use App\Http\Requests\Admin\ProductRequest;
use App\Models\Product;
use App\Services\Admin\ProductImportService;
use App\Services\Admin\ProductService;
use App\Services\Ai\ProductCopyGenerator;
use App\Support\AppStrings;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use RuntimeException;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $products,
        private readonly ProductImportService $importer,
    ) {}

    public function index(Request $request): View
    {
        $filters = $request->only(['q', 'status', 'category_id']);

        return view('admin.products.index', [
            'title' => AppStrings::NAV_PRODUCTS,
            'products' => $this->products->paginate($filters),
            'categories' => $this->products->categoryOptions(),
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        return view('admin.products.create', [
            'title' => AppStrings::ADD_PRODUCT,
            'categories' => $this->products->productFormCategoryOptions(),
            'product' => new Product(['is_active' => true, 'stock' => 0, 'sort_order' => 0]),
        ]);
    }

    public function generateCopy(ProductCopyRequest $request, ProductCopyGenerator $generator): JsonResponse
    {
        try {
            $copy = $generator->generate($request->validated());
        } catch (RuntimeException $e) {
            $reason = $e->getMessage();
            Log::warning('product.copy.failed', ['reason' => mb_substr($reason, 0, 180)]);

            $status = $reason === 'missing_name' ? 422 : 502;
            $message = match ($reason) {
                'missing_key' => 'مفتاح الذكاء الاصطناعي غير جاهز.',
                'missing_name' => 'أدخل اسم المنتج أولاً.',
                'connection' => 'تعذّر الاتصال بخدمة الذكاء الاصطناعي.',
                default => 'تعذّر التوليد الآن. حاول مرة أخرى.',
            };

            return response()->json(['message' => $message], $status);
        }

        return response()->json([
            'benefits' => implode("\n", $copy['benefits']),
            'keywords' => implode(', ', $copy['keywords']),
            'usage_instructions' => $copy['usage_instructions'],
        ]);
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $this->products->create($request->validated());

        return redirect()
            ->route('admin.products.index')
            ->with('success', AppStrings::PRODUCT_CREATED);
    }

    public function importForm(): View
    {
        return view('admin.products.import', [
            'title' => AppStrings::IMPORT_PRODUCTS,
            'columns' => ProductImportService::columns(),
            'categories' => $this->products->categoryOptions(),
        ]);
    }

    public function template(): Response
    {
        $xml = $this->importer->templateXml();

        $filename = $this->importer->templateFilename();

        return response($xml, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="products-import-template.xls"; filename*=UTF-8\'\''.rawurlencode($filename),
        ]);
    }

    public function import(ProductImportRequest $request): RedirectResponse
    {
        try {
            $result = $this->importer->import($request->file('file'));
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        $message = sprintf(
            'تم الاستيراد: %d منتج جديد، %d محدَّث.',
            $result['created'],
            $result['updated'],
        );

        if ($result['errors'] !== []) {
            $message .= ' بعض الصفوف لم تُستورد.';
        }

        return redirect()
            ->route('admin.products.index')
            ->with('success', $message)
            ->with('import_errors', $result['errors']);
    }

    public function edit(Product $product): View
    {
        $product->load('primaryImage', 'images', 'category');

        return view('admin.products.edit', [
            'title' => AppStrings::EDIT_PRODUCT,
            'categories' => $this->products->productFormCategoryOptions($product->category_id),
            'product' => $product,
        ]);
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $this->products->update($product, $request->validated());

        return redirect()
            ->route('admin.products.index')
            ->with('success', AppStrings::PRODUCT_UPDATED);
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->products->delete($product);

        return redirect()
            ->route('admin.products.index')
            ->with('success', AppStrings::PRODUCT_DELETED);
    }
}
