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

    public function index(Request $request): View|RedirectResponse
    {
        $parent = $this->parentFromRequest($request);
        if ($parent && $this->categories->depthOf($parent) >= 2) {
            return redirect()->route('admin.categories.index', array_filter([
                'parent' => $parent->parent_id,
            ]));
        }

        $depth = $parent ? $this->categories->depthOf($parent) + 1 : 0;
        $items = Category::query()
            ->withCount(['products', 'children'])
            ->when($parent, fn ($query) => $query->where('parent_id', $parent->id), fn ($query) => $query->whereNull('parent_id'))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('admin.categories.index', [
            'title' => $parent?->name ?? 'التبويبات',
            'parent' => $parent,
            'ancestors' => $this->categories->ancestorChain($parent),
            'depth' => $depth,
            'items' => $items,
            'createUrl' => route('admin.categories.create', array_filter([
                'parent_id' => $parent?->id,
            ])),
            'createLabel' => $this->addLabel($depth),
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
                ->route('admin.categories.index', array_filter(['parent' => $parent?->id]))
                ->with('error', 'لا يمكن إضافة مستوى تحت التصنيف.');
        }

        $level = $this->levelForDepth($depth);
        $parents = $this->parentsForLevel($level);

        if ($level !== 'root' && $parents->isEmpty()) {
            return redirect()
                ->route('admin.categories.index')
                ->with('error', $level === 'sub' ? 'أضف قسماً أولاً.' : 'أضف تبويباً أولاً.');
        }

        return view('admin.categories.create', [
            'title' => $this->addLabel($depth),
            'level' => $level,
            'parents' => $parents,
            'category' => new Category([
                'is_active' => true,
                'sort_order' => 0,
                'parent_id' => $parent?->id,
            ]),
            'cancelUrl' => $this->listUrl($parent?->id),
        ]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        $this->categories->create($request->validated());

        return redirect()
            ->to($this->listUrl($request->validated('parent_id')))
            ->with('success', AppStrings::CATEGORY_CREATED);
    }

    public function edit(Category $category): View
    {
        $depth = $this->categories->depthOf($category);
        $level = $this->levelForDepth($depth);

        return view('admin.categories.edit', [
            'title' => $this->editLabel($depth),
            'level' => $level,
            'parents' => $this->parentsForLevel($level, $category->id),
            'category' => $category,
            'cancelUrl' => $this->listUrl($category->parent_id),
        ]);
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $this->categories->update($category, $request->validated());
        $parentId = $request->validated('parent_id');

        return redirect()
            ->to($this->listUrl($parentId))
            ->with('success', AppStrings::CATEGORY_UPDATED);
    }

    public function destroy(Category $category): RedirectResponse
    {
        $parentId = $category->parent_id;
        $movedTo = $this->categories->delete($category);

        return redirect()
            ->to($this->listUrl($parentId))
            ->with('success', $movedTo
                ? sprintf(AppStrings::CATEGORY_DELETED_MOVED, $movedTo)
                : AppStrings::CATEGORY_DELETED);
    }

    private function parentFromRequest(Request $request): ?Category
    {
        if (! $request->filled('parent')) {
            return null;
        }

        return Category::query()->with('parent')->find($request->integer('parent'));
    }

    private function listUrl(int|string|null $parentId): string
    {
        return route('admin.categories.index', array_filter([
            'parent' => $parentId ?: null,
        ]));
    }

    private function levelForDepth(int $depth): string
    {
        return match (true) {
            $depth <= 0 => 'root',
            $depth === 1 => 'category',
            default => 'sub',
        };
    }

    private function addLabel(int $depth): string
    {
        return match ($depth) {
            1 => 'إضافة قسم',
            2 => 'إضافة تصنيف',
            default => 'إضافة تبويب',
        };
    }

    private function editLabel(int $depth): string
    {
        return match ($depth) {
            1 => 'تعديل القسم',
            2 => 'تعديل التصنيف',
            default => 'تعديل التبويب',
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
