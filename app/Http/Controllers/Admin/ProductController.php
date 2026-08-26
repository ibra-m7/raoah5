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
            'complementaryOptions' => $this->products->complementaryOptions(),
            'selectedComplementaryIds' => old('complementary_product_ids', []),
        ]);
    }

    public function generateCopy(
        ProductCopyRequest $request,
        ProductCopyGenerator $generator,
    ): JsonResponse {
        set_time_limit(90);

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
            'description' => $copy['description'],
            'category_id' => $copy['category_id'],
            'price' => $copy['price'],
            'stock' => $copy['stock'],
            'piece_count' => $copy['piece_count'],
            'weight_label' => $copy['weight_label'],
            'quantity_label' => $copy['quantity_label'],
        ]);
    }

    public function generateAllContent(ProductCopyGenerator $generator): RedirectResponse
    {
        set_time_limit(0);

        $ok = 0;
        $fail = 0;

        try {
            Product::query()
                ->orderBy('id')
                ->chunkById(25, function ($products) use ($generator, &$ok, &$fail) {
                    foreach ($products as $product) {
                        try {
                            $copy = $generator->generate([
                                'name' => $product->name,
                                'category_id' => $product->category_id,
                                'description' => $product->description,
                                'weight_label' => $product->weight_label,
                                'quantity_label' => $product->quantity_label,
                                'piece_count' => $product->piece_count,
                            ]);

                            $product->update([
                                'description' => $copy['description'] !== ''
                                    ? $copy['description']
                                    : $product->description,
                                'category_id' => $copy['category_id'] ?? $product->category_id,
                                'benefits' => $copy['benefits'] !== []
                                    ? $copy['benefits']
                                    : $product->benefits,
                                'keywords' => $copy['keywords'] !== []
                                    ? $copy['keywords']
                                    : $product->keywords,
                                'usage_instructions' => $copy['usage_instructions'] !== ''
                                    ? $copy['usage_instructions']
                                    : $product->usage_instructions,
                            ]);
                            $ok++;
                        } catch (RuntimeException $e) {
                            $fail++;
                            Log::warning('product.copy.bulk_item_failed', [
                                'product_id' => $product->id,
                                'reason' => mb_substr($e->getMessage(), 0, 180),
                            ]);

                            if ($e->getMessage() === 'missing_key') {
                                throw $e;
                            }
                        }
                    }
                });
        } catch (RuntimeException $e) {
            if ($e->getMessage() === 'missing_key') {
                return back()->with('error', 'مفتاح الذكاء الاصطناعي غير جاهز.');
            }

            return back()->with(
                'error',
                $ok > 0
                    ? "تم التوليد لـ {$ok} منتج، ثم توقف بسبب خطأ في الخدمة."
                    : 'تعذّر التوليد الآن. حاول مرة أخرى.'
            );
        }

        if ($ok === 0 && $fail === 0) {
            return back()->with('error', 'لا توجد منتجات للتوليد.');
        }

        if ($ok === 0) {
            return back()->with('error', 'تعذّر التوليد لجميع المنتجات. حاول مرة أخرى.');
        }

        $message = $fail > 0
            ? "تم توليد المحتوى لـ {$ok} منتج، وفشل {$fail}."
            : "تم توليد المحتوى لجميع المنتجات ({$ok}).";

        return back()->with('success', $message);
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
            $result = $this->importer->import(
                $request->file('file'),
                $request->file('images_zip'),
            );
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        $message = sprintf(
            'تم الاستيراد: %d منتج جديد، %d محدَّث، %d صورة.',
            $result['created'],
            $result['updated'],
            $result['images'] ?? 0,
        );

        if ($result['errors'] !== []) {
            $message .= ' بعض الصفوف أو الصور لم تُستورد.';
        }

        return redirect()
            ->route('admin.products.index')
            ->with('success', $message)
            ->with('import_errors', $result['errors']);
    }

    public function edit(Product $product): View
    {
        $product->load('primaryImage', 'images', 'category');

        $manualComplementaryIds = $product->productRelations()
            ->where('type', \App\Enums\ProductRelationType::Complementary)
            ->where('source', 'manual')
            ->orderBy('sort_order')
            ->pluck('related_product_id')
            ->all();

        return view('admin.products.edit', [
            'title' => AppStrings::EDIT_PRODUCT,
            'categories' => $this->products->productFormCategoryOptions($product->category_id),
            'product' => $product,
            'complementaryOptions' => $this->products->complementaryOptions($product->id),
            'selectedComplementaryIds' => old(
                'complementary_product_ids',
                $manualComplementaryIds,
            ),
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
