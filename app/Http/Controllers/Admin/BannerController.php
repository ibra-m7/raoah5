<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BannerLinkType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BannerRequest;
use App\Models\Banner;
use App\Services\Admin\BannerService;
use App\Support\AppStrings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BannerController extends Controller
{
    public function __construct(private readonly BannerService $banners) {}

    public function index(Request $request): View
    {
        $filters = $request->only(['q', 'status']);

        return view('admin.banners.index', [
            'title' => AppStrings::NAV_BANNERS,
            'banners' => $this->banners->paginate($filters),
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        return view('admin.banners.create', [
            'title' => AppStrings::ADD_BANNER,
            'banner' => new Banner(['is_active' => true, 'sort_order' => 0, 'link_type' => BannerLinkType::None]),
            'products' => $this->banners->productOptions(),
            'categories' => $this->banners->categoryOptions(),
            'pages' => $this->banners->pageOptions(),
        ]);
    }

    public function store(BannerRequest $request): RedirectResponse
    {
        $this->banners->create($request->validated());

        return redirect()
            ->route('admin.banners.index')
            ->with('success', AppStrings::BANNER_CREATED);
    }

    public function edit(Banner $banner): View
    {
        return view('admin.banners.edit', [
            'title' => AppStrings::EDIT_BANNER,
            'banner' => $banner,
            'products' => $this->banners->productOptions(),
            'categories' => $this->banners->categoryOptions(),
            'pages' => $this->banners->pageOptions(),
        ]);
    }

    public function update(BannerRequest $request, Banner $banner): RedirectResponse
    {
        $this->banners->update($banner, $request->validated());

        return redirect()
            ->route('admin.banners.index')
            ->with('success', AppStrings::BANNER_UPDATED);
    }

    public function destroy(Banner $banner): RedirectResponse
    {
        $this->banners->delete($banner);

        return redirect()
            ->route('admin.banners.index')
            ->with('success', AppStrings::BANNER_DELETED);
    }
}
