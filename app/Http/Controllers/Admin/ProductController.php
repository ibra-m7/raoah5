<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductCopyRequest;
use App\Http\Requests\Admin\ProductImportRequest;
use App\Http\Requests\Admin\ProductRequest;
use App\Http\Requests\Admin\QuickGiftProductRequest;
use App\Models\Product;
use App\Services\Admin\ProductCopyBulkService;
use App\Services\Admin\ProductExportService;
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
        private readonly ProductExportService $exporter,
        private readonly ProductCopyBulkService $copyBulk,
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
        return view('admin.products.create', $this->formPayload(
            AppStrings::ADD_PRODUCT,
            new Product(['is_active' => true, 'stock' => 0, 'sort_order' => 0]),
        ));
    }

    public function lookup(Request $request): JsonResponse
    {
        $except = $request->filled('except') ? $request->integer('except') : null;
        $exclude = collect(explode(',', (string) $request->query('exclude', '')))
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->all();

        return response()->json([
            'items' => $this->products->searchPicker(
                (string) $request->query('q', ''),
                $except,
                $exclude,
                20,
                $request->boolean('gift_only') ? true : null,
                $request->boolean('exclude_gifts'),
            ),
        ]);
    }

    public function generateCopy(
        ProductCopyRequest $request,
        ProductCopyGenerator $generator,
    ): JsonResponse {
        set_time_limit(90);

        try {
            $copy = $generator->generate($request->validated(), fast: true);
        } catch (RuntimeException $e) {
            $reason = $e->getMessage();
            Log::warning('product.copy.failed', ['reason' => mb_substr($reason, 0, 180)]);

            $status = $reason === 'missing_name' ? 422 : 502;
            $reasonLower = mb_strtolower($reason);
            $message = match (true) {
                $reason === 'missing_key' => 'مفتاح الذكاء الاصطناعي غير جاهز.',
                $reason === 'missing_name' => 'أدخل اسم المنتج أولاً.',
                $reason === 'connection' => 'تعذّر الاتصال بخدمة الذكاء الاصطناعي.',
                str_contains($reasonLower, 'quota')
                    || str_contains($reasonLower, 'rate limit')
                    || str_contains($reasonLower, 'resource exhausted') => 'تم تجاوز حد التوليد. جرّب بعد دقيقة أو غيّر نموذج الذكاء الاصطناعي من الإعدادات.',
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

    public function generateAllContent(): RedirectResponse
    {
        try {
            $status = $this->copyBulk->start();
        } catch (RuntimeException $e) {
            return match ($e->getMessage()) {
                'missing_key' => back()->with('error', 'مفتاح الذكاء الاصطناعي غير جاهز.'),
                'already_running' => back()->with('error', 'توليد المحتوى قيد التنفيذ بالفعل.'),
                'no_products' => back()->with('error', 'لا توجد منتجات للتوليد.'),
                default => back()->with('error', 'تعذّر بدء التوليد. حاول مرة أخرى.'),
            };
        }

        return back()->with(
            'success',
            "بدأ توليد المحتوى في الخلفية لـ {$status['total']} منتج. يمكنك متابعة التقدم من هذه الصفحة."
        );
    }

    public function copyGenerationStatus(): JsonResponse
    {
        $status = $this->copyBulk->status();
        $total = max(1, (int) $status['total']);
        $processed = (int) $status['processed'];

        return response()->json([
            ...$status,
            'percent' => $status['running']
                ? (int) round(($processed / $total) * 100)
                : ($status['finished_at'] ? 100 : 0),
        ]);
    }

    public function cancelContentGeneration(): JsonResponse
    {
        try {
            $status = $this->copyBulk->cancel();
        } catch (RuntimeException $e) {
            return response()->json([
                'message' => match ($e->getMessage()) {
                    'not_running' => 'لا يوجد توليد محتوى قيد التنفيذ.',
                    default => 'تعذّر إلغاء التوليد. حاول مرة أخرى.',
                },
            ], 422);
        }

        $total = max(1, (int) $status['total']);
        $processed = (int) $status['processed'];

        return response()->json([
            ...$status,
            'percent' => (int) round(($processed / $total) * 100),
        ]);
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $this->products->create($request->validated());

        return redirect()
            ->route('admin.products.index')
            ->with('success', AppStrings::PRODUCT_CREATED);
    }

    public function storeGiftQuick(QuickGiftProductRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $gift = $this->products->createQuickGift([
            'name' => $validated['name'],
            'category_id' => $validated['category_id'] ?? null,
            'price' => $validated['price'] ?? 0,
            'stock' => (int) $validated['stock'],
            'image' => $request->file('image'),
        ]);

        $linkedMainIds = $this->products->linkGiftToMainProducts(
            (int) $gift->id,
            $request->mainProductIds(),
        );

        $item = $this->products->formatPickerItem($gift->fresh());

        return response()->json([
            ...$item,
            'linked_main_ids' => $linkedMainIds,
        ]);
    }

    public function importForm(): View
    {
        return view('admin.products.import', [
            'title' => AppStrings::IMPORT_PRODUCTS,
            'columns' => ProductImportService::columns(),
            'categories' => $this->products->categoryOptions(),
            'templateImageCount' => $this->importer->templateImageCount(),
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

    public function export(Request $request): Response
    {
        $filters = $request->only(['q', 'status', 'category_id']);
        $xml = $this->exporter->xml($filters);
        $filename = $this->exporter->filename();

        return response($xml, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="products-export.xls"; filename*=UTF-8\'\''.rawurlencode($filename),
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

        if (($result['images'] ?? 0) === 0 && ! $request->hasFile('images_zip')) {
            $message .= ' لم تُرفَع صور ZIP — ارفع Excel مع ملف الصور لربط كل صورة بباركودها.';
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

        $selectedGiftProductId = $product->productRelations()
            ->where('type', \App\Enums\ProductRelationType::Gift)
            ->value('related_product_id');

        return view('admin.products.edit', $this->formPayload(
            AppStrings::EDIT_PRODUCT,
            $product,
            $manualComplementaryIds,
            $selectedGiftProductId,
        ));
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

    /**
     * @param  list<int|string>  $complementaryIds
     * @return array<string, mixed>
     */
    private function formPayload(
        string $title,
        Product $product,
        array $complementaryIds = [],
        mixed $giftProductId = null,
    ): array {
        return [
            'title' => $title,
            'categories' => $this->products->productFormCategoryOptions($product->exists ? $product->category_id : null),
            'product' => $product,
            'giftProducts' => $this->products->pickerItems(old('gift_product_id', $giftProductId)),
            'complementaryProducts' => $this->products->pickerItems(old('complementary_product_ids', $complementaryIds)),
        ];
    }
}
