<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\HomeSectionRequest;
use App\Models\HomeSection;
use App\Services\Admin\BundleService;
use App\Services\Admin\HomeSectionService;
use App\Services\Admin\ProductService;
use App\Support\AppStrings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeSectionController extends Controller
{
    public function __construct(
        private readonly HomeSectionService $sections,
        private readonly ProductService $products,
        private readonly BundleService $bundles,
    ) {}

    public function index(Request $request): View
    {
        $filters = $request->only(['q']);

        return view('admin.home-sections.index', [
            'title' => AppStrings::NAV_HOME_SECTIONS,
            'sections' => $this->sections->paginate($filters),
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        return view('admin.home-sections.create', [
            'title' => AppStrings::ADD_HOME_SECTION,
            'section' => new HomeSection(['is_active' => true, 'sort_order' => 0]),
            'selectedProducts' => $this->products->pickerItems(old('product_ids', [])),
        ]);
    }

    public function store(HomeSectionRequest $request): RedirectResponse
    {
        $this->sections->create($request->validated());

        return redirect()
            ->route('admin.home-sections.index')
            ->with('success', AppStrings::HOME_SECTION_CREATED);
    }

    public function edit(HomeSection $home_section): View
    {
        $sectionBundles = $home_section->showsBundles()
            ? $this->bundles->forSection($home_section)
            : collect();

        return view('admin.home-sections.edit', [
            'title' => AppStrings::EDIT_HOME_SECTION,
            'section' => $home_section,
            'selectedProducts' => $this->products->pickerItems(
                old('product_ids', $home_section->showsBundles()
                    ? []
                    : $home_section->products()->allRelatedIds()),
            ),
            'sectionBundles' => $sectionBundles,
        ]);
    }

    public function update(HomeSectionRequest $request, HomeSection $home_section): RedirectResponse
    {
        $this->sections->update($home_section, $request->validated());

        return redirect()
            ->route('admin.home-sections.index')
            ->with('success', AppStrings::HOME_SECTION_UPDATED);
    }

    public function destroy(HomeSection $home_section): RedirectResponse
    {
        $this->sections->delete($home_section);

        return redirect()
            ->route('admin.home-sections.index')
            ->with('success', AppStrings::HOME_SECTION_DELETED);
    }
}
