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
        $filters = $request->only(['q', 'parent_id', 'status']);

        return view('admin.categories.index', [
            'title' => AppStrings::NAV_CATEGORIES,
            'categories' => $this->categories->paginate($filters),
            'parents' => $this->categories->parentOptions(),
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        return view('admin.categories.create', [
            'title' => AppStrings::ADD_CATEGORY,
            'parents' => $this->categories->parentOptions(),
            'category' => new Category(['is_active' => true, 'sort_order' => 0]),
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
        return view('admin.categories.edit', [
            'title' => AppStrings::EDIT_CATEGORY,
            'parents' => $this->categories->parentOptions($category->id),
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
