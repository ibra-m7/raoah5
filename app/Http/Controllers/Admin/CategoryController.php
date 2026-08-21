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
        $scope = $this->resolveScope($request);

        if ($scope && $tab === CategoryService::TAB_MAINS) {
            $tab = $this->categories->depthOf($scope) === 0
                ? CategoryService::TAB_BRANCHES
                : CategoryService::TAB_CLASSES;
        }

        $groups = $this->categories->groupedByLevel();
        $items = $tab === CategoryService::TAB_MAINS
            ? $groups[CategoryService::TAB_MAINS]
            : $this->categories->scopedItems($groups[$tab] ?? collect(), $scope);

        return view('admin.categories.index', [
            'title' => AppStrings::NAV_CATEGORIES,
            'tab' => $tab,
            'scope' => $scope,
            'ancestors' => $this->categories->ancestorChain($scope),
            'items' => $items,
            'filters' => match ($tab) {
                CategoryService::TAB_BRANCHES => $groups[CategoryService::TAB_MAINS],
                CategoryService::TAB_CLASSES => $groups[CategoryService::TAB_BRANCHES],
                default => collect(),
            },
            'counts' => collect($groups)->map->count(),
            'createUrl' => route('admin.categories.create', $this->createParams($tab, $scope)),
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $parentId = $request->filled('parent_id') ? $request->integer('parent_id') : null;
        $tab = $parentId
            ? $this->categories->tabFor($parentId)
            : $this->resolveTab($request->query('tab'));
        $parents = $this->parentsForTab($tab);

        if ($tab !== CategoryService::TAB_MAINS && $parents->isEmpty()) {
            $fallback = $tab === CategoryService::TAB_CLASSES
                ? CategoryService::TAB_BRANCHES
                : CategoryService::TAB_MAINS;

            return redirect()
                ->route('admin.categories.index', ['tab' => $fallback])
                ->with('error', $tab === CategoryService::TAB_CLASSES
                    ? 'أضف قسماً فرعياً أولاً.'
                    : 'أضف قسماً رئيسياً أولاً.');
        }

        return view('admin.categories.create', [
            'title' => $this->titleForTab($tab),
            'tab' => $tab,
            'level' => $this->levelForTab($tab),
            'parents' => $parents,
            'category' => new Category([
                'is_active' => true,
                'sort_order' => 0,
                'parent_id' => $parentId,
            ]),
            'cancelUrl' => route('admin.categories.index', array_filter([
                'tab' => $tab,
                'parent' => $parentId,
            ])),
        ]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        $this->categories->create($request->validated());
        $parentId = $request->validated('parent_id');

        return redirect()
            ->route('admin.categories.index', array_filter([
                'tab' => $this->categories->tabFor($parentId),
                'parent' => $parentId,
            ]))
            ->with('success', AppStrings::CATEGORY_CREATED);
    }

    public function edit(Category $category): View
    {
        $tab = $this->categories->tabFor($category->parent_id);

        return view('admin.categories.edit', [
            'title' => $this->editTitleForTab($tab),
            'tab' => $tab,
            'level' => $this->levelForTab($tab),
            'parents' => $this->parentsForTab($tab, $category->id),
            'category' => $category,
            'cancelUrl' => route('admin.categories.index', array_filter([
                'tab' => $tab,
                'parent' => $category->parent_id,
            ])),
        ]);
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $this->categories->update($category, $request->validated());
        $parentId = $request->validated('parent_id');

        return redirect()
            ->route('admin.categories.index', array_filter([
                'tab' => $this->categories->tabFor($parentId),
                'parent' => $parentId,
            ]))
            ->with('success', AppStrings::CATEGORY_UPDATED);
    }

    public function destroy(Category $category): RedirectResponse
    {
        $tab = $this->categories->tabFor($category->parent_id);
        $parentId = $category->parent_id;
        $movedTo = $this->categories->delete($category);

        return redirect()
            ->route('admin.categories.index', array_filter([
                'tab' => $tab,
                'parent' => $parentId,
            ]))
            ->with('success', $movedTo
                ? sprintf(AppStrings::CATEGORY_DELETED_MOVED, $movedTo)
                : AppStrings::CATEGORY_DELETED);
    }

    private function resolveTab(mixed $tab): string
    {
        return match ($tab) {
            'categories', CategoryService::TAB_BRANCHES => CategoryService::TAB_BRANCHES,
            'subs', CategoryService::TAB_CLASSES => CategoryService::TAB_CLASSES,
            default => CategoryService::TAB_MAINS,
        };
    }

    private function resolveScope(Request $request): ?Category
    {
        if (! $request->filled('parent')) {
            return null;
        }

        return Category::query()->find($request->integer('parent'));
    }

    /**
     * @return array{tab: string, parent_id?: int}
     */
    private function createParams(string $tab, ?Category $scope): array
    {
        $params = ['tab' => $tab];
        if (! $scope) {
            return $params;
        }

        $depth = $this->categories->depthOf($scope);
        if ($tab === CategoryService::TAB_BRANCHES && $depth === 0) {
            $params['parent_id'] = $scope->id;
        }
        if ($tab === CategoryService::TAB_CLASSES && $depth === 1) {
            $params['parent_id'] = $scope->id;
        }

        return $params;
    }

    private function levelForTab(string $tab): string
    {
        return match ($tab) {
            CategoryService::TAB_BRANCHES => 'category',
            CategoryService::TAB_CLASSES => 'sub',
            default => 'root',
        };
    }

    private function titleForTab(string $tab): string
    {
        return match ($tab) {
            CategoryService::TAB_BRANCHES => 'إضافة قسم فرعي',
            CategoryService::TAB_CLASSES => 'إضافة تصنيف',
            default => 'إضافة قسم رئيسي',
        };
    }

    private function editTitleForTab(string $tab): string
    {
        return match ($tab) {
            CategoryService::TAB_BRANCHES => 'تعديل القسم الفرعي',
            CategoryService::TAB_CLASSES => 'تعديل التصنيف',
            default => 'تعديل القسم الرئيسي',
        };
    }

    private function parentsForTab(string $tab, ?int $exceptId = null): \Illuminate\Support\Collection
    {
        $options = $this->categories->indentedOptions($exceptId);

        return match ($tab) {
            CategoryService::TAB_BRANCHES => $options->where('depth', 0)->values(),
            CategoryService::TAB_CLASSES => $options->where('depth', 1)->values(),
            default => collect(),
        };
    }
}
