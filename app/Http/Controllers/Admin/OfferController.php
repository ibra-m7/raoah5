<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PromoType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\OfferRequest;
use App\Models\Product;
use App\Services\Admin\OfferService;
use App\Support\AppStrings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OfferController extends Controller
{
    public function __construct(private readonly OfferService $offers) {}

    public function index(Request $request): View
    {
        $type = PromoType::fromRequest($request->query('type'));
        $filters = $request->only(['q']);

        return view('admin.offers.index', [
            'title' => AppStrings::NAV_OFFERS,
            'type' => $type,
            'offers' => $this->offers->paginate($type, $filters),
            'counts' => $this->offers->counts(),
            'filters' => $filters,
        ]);
    }

    public function create(Request $request): View
    {
        $type = PromoType::fromRequest($request->query('type'));

        return view('admin.offers.create', [
            'title' => $type->addLabel(),
            'type' => $type,
            'products' => $this->offers->availablePayload(),
        ]);
    }

    public function available(Request $request): JsonResponse
    {
        $except = $request->integer('except') ?: null;

        return response()->json([
            'products' => $this->offers->availablePayload($request->query('q'), $except),
        ]);
    }

    public function store(OfferRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $type = PromoType::fromRequest($data['promo_type']);
        $count = $this->offers->applyMany($type, $data['product_ids'], $data);

        return redirect()
            ->route('admin.offers.index', ['type' => $type->value])
            ->with('success', $type === PromoType::Offer
                ? "تم تطبيق العرض على {$count} منتج."
                : "تم تطبيق الخصم على {$count} منتج.");
    }

    public function edit(Product $product): View
    {
        $type = $product->promo_type ?? PromoType::Discount;

        return view('admin.offers.edit', [
            'title' => $type === PromoType::Offer ? 'تعديل العرض' : AppStrings::EDIT_OFFER,
            'type' => $type,
            'product' => $product,
        ]);
    }

    public function update(OfferRequest $request, Product $product): RedirectResponse
    {
        $data = $request->validated();
        $type = PromoType::fromRequest($data['promo_type']);
        $this->offers->applyMany($type, [$product->id], $data);

        return redirect()
            ->route('admin.offers.index', ['type' => $type->value])
            ->with('success', $type === PromoType::Offer ? 'تم تحديث العرض.' : AppStrings::OFFER_UPDATED);
    }

    public function destroy(Product $product): RedirectResponse
    {
        $type = $product->promo_type ?? PromoType::Discount;
        $this->offers->clear($product);

        return redirect()
            ->route('admin.offers.index', ['type' => $type->value])
            ->with('success', $type === PromoType::Offer ? 'تم إلغاء العرض عن المنتج.' : AppStrings::OFFER_DELETED);
    }
}
