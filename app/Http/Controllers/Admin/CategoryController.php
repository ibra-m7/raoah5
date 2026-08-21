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

    public function index(): View
    {
        return view('admin.categories.index', [
            'title' => AppStrings::NAV_CATEGORIES,
            'tree' => $this->categories->tree(),
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $parentId = $request->filled('parent_id') ? $request->integer('parent_id') : null;
        $parent = $parentId ? Category::query()->find($parentId) : null;

        if ($parentId && ! $parent) {
            return redirect()->route('admin.categories.index');
        }

        $depth = $parent ? $this->categories->depthOf($parent) + 1 : 0;
        if ($depth > 2) {
            return redirect()
                ->route('admin.categories.index')
                ->with('error', 'لا يمكن إضافة مستوى تحت التصنيف.');
        }

        $level = $this->levelForDepth($depth);
        $parents = $this->parentsForLevel($level);

        if ($level !== 'root' && $parents->isEmpty()) {
            return redirect()
                ->route('admin.categories.index')
                ->with('error', $level === 'sub' ? 'أضف قسماً فرعياً أولاً.' : 'أضف قسماً رئيسياً أولاً.');
        }

        return view('admin.categories.create', [
            'title' => $this->titleForLevel($level),
            'level' => $level,
            'parents' => $parents,
            'category' => new Category([
                'is_active' => true,
                'sort_order' => 0,
                'parent_id' => $parent?->id,
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
        $level = $this->levelForDepth($this->categories->depthOf($category));

        return view('admin.categories.edit', [
            'title' => $this->editTitleForLevel($level),
            'level' => $level,
            'parents' => $this->parentsForLevel($level, $category->id),
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

    private function levelForDepth(int $depth): string
    {
        return match (true) {
            $depth <= 0 => 'root',
            $depth === 1 => 'category',
            default => 'sub',
        };
    }

    private function titleForLevel(string $level): string
    {
        return match ($level) {
            'category' => 'إضافة قسم فرعي',
            'sub' => 'إضافة تصنيف',
            default => 'إضافة قسم رئيسي',
        };
    }

    private function editTitleForLevel(string $level): string
    {
        return match ($level) {
            'category' => 'تعديل القسم الفرعي',
            'sub' => 'تعديل التصنيف',
            default => 'تعديل القسم الرئيسي',
        };
    }

    private function parentsForLevel(string $level, ?int $exceptId = null): \Illuminate\Support\Collection
    {
        $options = $this->categories->indentedOptions($exceptId);

        return match ($level) {
            'category' => $options->where('depth', 0)->values(),
            'sub' => $options->where('depth', 1)->values(),
            default => collect(),
        };
    }
}
