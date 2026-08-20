<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DisplaySectionRequest;
use App\Models\DisplaySection;
use App\Services\Admin\DisplaySectionService;
use App\Support\AppStrings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DisplaySectionController extends Controller
{
    public function __construct(private readonly DisplaySectionService $sections) {}

    public function index(Request $request): View
    {
        $filters = $request->only(['q']);

        return view('admin.display-sections.index', [
            'title' => AppStrings::NAV_DISPLAY_SECTIONS,
            'sections' => $this->sections->paginate($filters),
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        return view('admin.display-sections.create', [
            'title' => AppStrings::ADD_DISPLAY_SECTION,
            'section' => new DisplaySection(['is_active' => true, 'sort_order' => 0]),
            'categories' => $this->sections->categoryOptions(),
            'selectedIds' => [],
        ]);
    }

    public function store(DisplaySectionRequest $request): RedirectResponse
    {
        $this->sections->create($request->validated());

        return redirect()
            ->route('admin.display-sections.index')
            ->with('success', AppStrings::DISPLAY_SECTION_CREATED);
    }

    public function edit(DisplaySection $display_section): View
    {
        $display_section->load('categories');

        return view('admin.display-sections.edit', [
            'title' => AppStrings::EDIT_DISPLAY_SECTION,
            'section' => $display_section,
            'categories' => $this->sections->categoryOptions(),
            'selectedIds' => $display_section->categories->pluck('id')->all(),
        ]);
    }

    public function update(DisplaySectionRequest $request, DisplaySection $display_section): RedirectResponse
    {
        $this->sections->update($display_section, $request->validated());

        return redirect()
            ->route('admin.display-sections.index')
            ->with('success', AppStrings::DISPLAY_SECTION_UPDATED);
    }

    public function destroy(DisplaySection $display_section): RedirectResponse
    {
        $this->sections->delete($display_section);

        return redirect()
            ->route('admin.display-sections.index')
            ->with('success', AppStrings::DISPLAY_SECTION_DELETED);
    }
}
