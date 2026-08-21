<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CategoryRequest;
use App\Models\Category;
use App\Services\Admin\CategoryService;
use App\Support\AppStrings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function __construct(private readonly CategoryService $categories) {}

    public function index(Request $request): View
    {
        return view('admin.categories.index', [
            'title' => AppStrings::NAV_CATEGORIES,
            'tree' => $this->categories->tree(),
        ]);
    }

    public function create(Request $request): View
    {
        $parentId = $request->filled('parent_id') ? $request->integer('parent_id') : null;

        return view('admin.categories.create', [
            'title' => AppStrings::ADD_CATEGORY,
            'parents' => $this->categories->parentOptions(),
            'displaySections' => $this->categories->displaySectionOptions(),
            'selectedSectionIds' => [],
            'depth' => $this->categories->depthFor($parentId),
            'category' => new Category([
                'is_active' => true,
                'sort_order' => 0,
                'parent_id' => $parentId,
            ]),
        ]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        $this->categories->create($request->validated());

        return redirect()
            ->route('admin.categories.index')
            ->with('success', AppStrings::CATEGORY_CREATED);
    }

    public function edit(Category $category): View
    {
        $category->load('displaySections');

        return view('admin.categories.edit', [
            'title' => AppStrings::EDIT_CATEGORY,
            'parents' => $this->categories->parentOptions($category->id),
            'displaySections' => $this->categories->displaySectionOptions(),
            'selectedSectionIds' => $category->displaySections->pluck('id')->all(),
            'depth' => $this->categories->depthFor($category->parent_id),
            'category' => $category,
        ]);
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $this->categories->update($category, $request->validated());

        return redirect()
            ->route('admin.categories.index')
            ->with('success', AppStrings::CATEGORY_UPDATED);
    }

    public function destroy(Category $category): RedirectResponse
    {
        $movedTo = $this->categories->delete($category);

        return redirect()
            ->route('admin.categories.index')
            ->with('success', $movedTo
                ? sprintf(AppStrings::CATEGORY_DELETED_MOVED, $movedTo)
                : AppStrings::CATEGORY_DELETED);
    }
}
