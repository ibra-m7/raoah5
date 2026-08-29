<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Enums\DynamicPagePlacement;
use App\Http\Requests\Admin\DynamicPageRequest;
use App\Models\DynamicPage;
use App\Services\Admin\DynamicPageService;
use App\Services\Admin\ProductService;
use App\Support\AppStrings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DynamicPageController extends Controller
{
    public function __construct(
        private readonly DynamicPageService $pages,
        private readonly ProductService $products,
    ) {}

    public function index(Request $request): View
    {
        $filters = $request->only(['q']);

        return view('admin.dynamic-pages.index', [
            'title' => AppStrings::NAV_DYNAMIC_PAGES,
            'pages' => $this->pages->paginate($filters),
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        return view('admin.dynamic-pages.create', [
            'title' => AppStrings::ADD_DYNAMIC_PAGE,
            'page' => new DynamicPage([
                'is_active' => true,
                'sort_order' => 0,
                'placement' => DynamicPagePlacement::None,
            ]),
            'selectedProducts' => $this->products->pickerItems(old('product_ids', [])),
        ]);
    }

    public function store(DynamicPageRequest $request): RedirectResponse
    {
        $this->pages->create($request->validated());

        return redirect()
            ->route('admin.dynamic-pages.index')
            ->with('success', AppStrings::DYNAMIC_PAGE_CREATED);
    }

    public function edit(DynamicPage $dynamic_page): View
    {
        return view('admin.dynamic-pages.edit', [
            'title' => AppStrings::EDIT_DYNAMIC_PAGE,
            'page' => $dynamic_page,
            'selectedProducts' => $this->products->pickerItems(
                old('product_ids', $dynamic_page->products()->allRelatedIds()),
            ),
        ]);
    }

    public function update(DynamicPageRequest $request, DynamicPage $dynamic_page): RedirectResponse
    {
        $this->pages->update($dynamic_page, $request->validated());

        return redirect()
            ->route('admin.dynamic-pages.index')
            ->with('success', AppStrings::DYNAMIC_PAGE_UPDATED);
    }

    public function destroy(DynamicPage $dynamic_page): RedirectResponse
    {
        $this->pages->delete($dynamic_page);

        return redirect()
            ->route('admin.dynamic-pages.index')
            ->with('success', AppStrings::DYNAMIC_PAGE_DELETED);
    }
}
