<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\OfferRequest;
use App\Models\Product;
use App\Services\Admin\OfferService;
use App\Support\AppStrings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OfferController extends Controller
{
    public function __construct(private readonly OfferService $offers) {}

    public function index(Request $request): View
    {
        $filters = $request->only(['q']);

        return view('admin.offers.index', [
            'title' => AppStrings::NAV_OFFERS,
            'offers' => $this->offers->paginate($filters),
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        return view('admin.offers.create', [
            'title' => AppStrings::ADD_OFFER,
            'product' => new Product(['is_featured' => true]),
            'products' => $this->offers->productOptions(),
        ]);
    }

    public function store(OfferRequest $request): RedirectResponse
    {
        $this->offers->apply($request->validated());

        return redirect()
            ->route('admin.offers.index')
            ->with('success', AppStrings::OFFER_CREATED);
    }

    public function edit(Product $product): View
    {
        return view('admin.offers.edit', [
            'title' => AppStrings::EDIT_OFFER,
            'product' => $product,
            'products' => $this->offers->productOptions(),
        ]);
    }

    public function update(OfferRequest $request, Product $product): RedirectResponse
    {
        $data = $request->validated();
        $data['product_id'] = $product->id;
        $this->offers->apply($data);

        return redirect()
            ->route('admin.offers.index')
            ->with('success', AppStrings::OFFER_UPDATED);
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->offers->clear($product);

        return redirect()
            ->route('admin.offers.index')
            ->with('success', AppStrings::OFFER_DELETED);
    }
}
