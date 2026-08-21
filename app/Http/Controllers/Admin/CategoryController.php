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
        $tab = $this->resolveTab($request->query('tab'));

        return view('admin.categories.index', [
            'title' => AppStrings::NAV_CATEGORIES,
            'tab' => $tab,
            'groups' => $this->categories->groupedByLevel(),
        ]);
    }

    public function create(Request $request): View
    {
        $parentId = $request->filled('parent_id') ? $request->integer('parent_id') : null;
        $tab = $parentId
            ? $this->categories->tabFor($parentId)
            : $this->resolveTab($request->query('tab'));

        return view('admin.categories.create', [
            'title' => $this->titleForTab($tab),
            'tab' => $tab,
            'level' => $this->levelForTab($tab),
            'parents' => $this->parentsForTab($tab),
            'displaySections' => $this->categories->displaySectionOptions(),
            'selectedSectionIds' => [],
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
            ->route('admin.categories.index', ['tab' => $this->categories->tabFor($request->validated('parent_id'))])
            ->with('success', AppStrings::CATEGORY_CREATED);
    }

    public function edit(Category $category): View
    {
        $category->load('displaySections');

        return view('admin.categories.edit', [
            'title' => AppStrings::EDIT_CATEGORY,
            'tab' => $this->categories->tabFor($category->parent_id),
            'level' => $this->levelForTab($this->categories->tabFor($category->parent_id)),
            'parents' => $this->parentsForTab($this->categories->tabFor($category->parent_id), $category->id),
            'displaySections' => $this->categories->displaySectionOptions(),
            'selectedSectionIds' => $category->displaySections->pluck('id')->all(),
            'category' => $category,
        ]);
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $this->categories->update($category, $request->validated());

        return redirect()
            ->route('admin.categories.index', ['tab' => $this->categories->tabFor($request->validated('parent_id'))])
            ->with('success', AppStrings::CATEGORY_UPDATED);
    }

    public function destroy(Category $category): RedirectResponse
    {
        $tab = $this->categories->tabFor($category->parent_id);
        $movedTo = $this->categories->delete($category);

        return redirect()
            ->route('admin.categories.index', ['tab' => $tab])
            ->with('success', $movedTo
                ? sprintf(AppStrings::CATEGORY_DELETED_MOVED, $movedTo)
                : AppStrings::CATEGORY_DELETED);
    }

    private function resolveTab(mixed $tab): string
    {
        return in_array($tab, ['roots', 'categories', 'subs'], true) ? $tab : 'roots';
    }

    private function levelForTab(string $tab): string
    {
        return match ($tab) {
            'categories' => 'category',
            'subs' => 'sub',
            default => 'root',
        };
    }

    private function titleForTab(string $tab): string
    {
        return match ($tab) {
            'categories' => 'إضافة تصنيف',
            'subs' => 'إضافة تصنيف فرعي',
            default => 'إضافة قسم رئيسي',
        };
    }

    private function parentsForTab(string $tab, ?int $exceptId = null): \Illuminate\Support\Collection
    {
        $options = $this->categories->indentedOptions($exceptId);

        return match ($tab) {
            'categories' => $options->where('depth', 0)->values(),
            'subs' => $options->where('depth', 1)->values(),
            default => collect(),
        };
    }
}
