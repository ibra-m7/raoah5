<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BundleCopyRequest;
use App\Http\Requests\Admin\BundleRequest;
use App\Models\HomeSection;
use App\Models\ProductBundle;
use App\Services\Admin\BundleService;
use App\Services\Admin\ProductService;
use App\Services\Ai\BundleCopyGenerator;
use App\Support\AppStrings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class HomeSectionBundleController extends Controller
{
    public function __construct(
        private readonly BundleService $bundles,
        private readonly ProductService $products,
    ) {}

    public function create(HomeSection $home_section): View
    {
        $this->ensureBundleSection($home_section);

        return view('admin.home-sections.bundles.create', [
            'title' => AppStrings::ADD_BUNDLE,
            'section' => $home_section,
            'bundle' => new ProductBundle([
                'is_active' => true,
                'sort_order' => 0,
                'discount_percent' => 0,
                'bundle_price' => 0,
            ]),
            'selectedItems' => $this->resolveSelectedItems(old('items', [])),
        ]);
    }

    public function generateCopy(
        BundleCopyRequest $request,
        BundleCopyGenerator $generator,
    ): JsonResponse {
        set_time_limit(90);

        try {
            $copy = $generator->generate($request->validated(), fast: true);
        } catch (RuntimeException $e) {
            $reason = $e->getMessage();
            Log::warning('bundle.copy.failed', ['reason' => mb_substr($reason, 0, 180)]);

            $status = $reason === 'missing_name' ? 422 : 502;
            $reasonLower = mb_strtolower($reason);
            $message = match (true) {
                $reason === 'missing_key' => 'مفتاح الذكاء الاصطناعي غير جاهز.',
                $reason === 'missing_name' => 'أدخل اسم السلة أولاً.',
                $reason === 'connection' => 'تعذّر الاتصال بخدمة الذكاء الاصطناعي.',
                str_contains($reasonLower, 'quota')
                    || str_contains($reasonLower, 'rate limit')
                    || str_contains($reasonLower, 'resource exhausted') => 'تم تجاوز حد التوليد. جرّب بعد دقيقة أو غيّر نموذج الذكاء الاصطناعي من الإعدادات.',
                $reason === 'invalid_json' => 'استجابة غير صالحة من الذكاء الاصطناعي. حاول مرة أخرى.',
                default => 'تعذّر التوليد الآن. حاول مرة أخرى.',
            };

            return response()->json(['message' => $message], $status);
        }

        return response()->json([
            'summary' => $copy['summary'],
            'description' => $copy['description'],
            'discount_percent' => $copy['discount_percent'],
            'meta' => $copy['meta'] ?? ['cached' => false, 'source' => 'ai'],
        ]);
    }

    public function store(BundleRequest $request, HomeSection $home_section): RedirectResponse
    {
        $this->ensureBundleSection($home_section);
        $this->bundles->createForSection($home_section, $request->validated());

        return redirect()
            ->route('admin.home-sections.edit', $home_section)
            ->with('success', AppStrings::BUNDLE_CREATED);
    }

    public function edit(HomeSection $home_section, ProductBundle $bundle): View
    {
        $this->ensureBundleSection($home_section);
        $this->ensureBundleBelongsToSection($home_section, $bundle);

        $bundle->load(['items.product']);

        $defaultItems = $bundle->items->map(fn ($item) => [
            'product_id' => $item->product_id,
            'quantity' => $item->quantity,
        ])->all();

        return view('admin.home-sections.bundles.edit', [
            'title' => AppStrings::EDIT_BUNDLE,
            'section' => $home_section,
            'bundle' => $bundle,
            'selectedItems' => $this->resolveSelectedItems(old('items', $defaultItems)),
        ]);
    }

    public function show(HomeSection $home_section, ProductBundle $bundle): RedirectResponse
    {
        $this->ensureBundleSection($home_section);
        $this->ensureBundleBelongsToSection($home_section, $bundle);

        return redirect()->route('admin.home-sections.bundles.edit', [$home_section, $bundle]);
    }

    public function update(BundleRequest $request, HomeSection $home_section, ProductBundle $bundle): RedirectResponse
    {
        $this->ensureBundleSection($home_section);
        $this->ensureBundleBelongsToSection($home_section, $bundle);
        $this->bundles->update($bundle, $request->validated());

        return redirect()
            ->route('admin.home-sections.bundles.edit', [$home_section, $bundle])
            ->with('success', AppStrings::BUNDLE_UPDATED);
    }

    public function destroy(HomeSection $home_section, ProductBundle $bundle): RedirectResponse
    {
        $this->ensureBundleSection($home_section);
        $this->ensureBundleBelongsToSection($home_section, $bundle);
        $this->bundles->delete($bundle);

        return redirect()
            ->route('admin.home-sections.edit', $home_section)
            ->with('success', AppStrings::BUNDLE_DELETED);
    }

    public function reorder(Request $request, HomeSection $home_section): RedirectResponse
    {
        $this->ensureBundleSection($home_section);

        $request->validate([
            'bundle_ids' => ['required', 'array', 'min:1'],
            'bundle_ids.*' => ['integer', 'exists:product_bundles,id'],
        ]);

        $this->bundles->reorderInSection($home_section, $request->input('bundle_ids', []));

        return redirect()
            ->route('admin.home-sections.edit', $home_section)
            ->with('success', 'تم تحديث ترتيب السلات.');
    }

    private function ensureBundleSection(HomeSection $section): void
    {
        if (! $section->showsBundles()) {
            throw new NotFoundHttpException();
        }
    }

    private function ensureBundleBelongsToSection(HomeSection $section, ProductBundle $bundle): void
    {
        if (! $section->bundles()->whereKey($bundle->id)->exists()) {
            throw new NotFoundHttpException();
        }
    }

    /**
     * @param  list<array{product_id?: int, quantity?: int}>|mixed  $items
     * @return list<array{product: \App\Models\Product, quantity: int}>
     */
    private function resolveSelectedItems(mixed $items): array
    {
        if (! is_array($items)) {
            return [];
        }

        $ids = array_values(array_filter(array_map(
            fn ($row) => is_array($row) ? (int) ($row['product_id'] ?? 0) : 0,
            $items,
        )));

        $products = $this->products->pickerItems($ids)->keyBy('id');
        $resolved = [];

        foreach ($items as $row) {
            if (! is_array($row)) {
                continue;
            }
            $productId = (int) ($row['product_id'] ?? 0);
            $product = $products->get($productId);
            if ($product === null) {
                continue;
            }
            $resolved[] = [
                'product' => $product,
                'quantity' => max(1, min(99, (int) ($row['quantity'] ?? 1))),
            ];
        }

        return $resolved;
    }
}
