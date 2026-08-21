@foreach ($nodes as $category)
    @php
        $depth = $depth ?? 0;
        $level = \App\Services\Admin\CategoryService::levelLabel($depth);
        $thumb = $category->image_src ?: $category->icon_src;
        $tabs = $category->displaySections->map(fn ($section) => trim(($section->emoji ? $section->emoji.' ' : '').$section->name))->filter()->values();
    @endphp
    <div class="category-tree-node" style="margin-bottom: 8px; {{ $depth ? 'margin-right: 22px;' : '' }}">
        <div class="d-flex flex-wrap align-items-center gap-2 p-3" style="background:#fff;border:1px solid var(--color-primary-light,#c8ecd3);border-radius:16px">
            <span class="table-thumb-wrap">
                @if ($thumb)
                    <img src="{{ $thumb }}" alt="" class="table-thumb">
                @else
                    <i class="bi bi-grid"></i>
                @endif
            </span>
            <div class="flex-grow-1" style="min-width:180px">
                <strong>
                    <span class="color-dot" style="background: {{ $category->color ?: '#88D498' }}"></span>
                    {{ $category->name }}
                </strong>
                <small class="d-block text-muted">
                    {{ $level }}
                    · {{ $category->products_count }} منتج
                    @if ($category->children_count)
                        · {{ $category->children_count }} فرعي
                    @endif
                    @if ($tabs->isNotEmpty())
                        · التبويب: {{ $tabs->implode('، ') }}
                    @endif
                </small>
            </div>
            <span class="badge badge-soft">{{ $category->is_active ? $strings::ACTIVE : $strings::INACTIVE }}</span>
            <div class="d-flex flex-wrap gap-1">
                <a href="{{ route('admin.categories.create', ['parent_id' => $category->id]) }}" class="btn btn-sm btn-outline-success rounded-pill">إضافة فرعي</a>
                <a href="{{ route('admin.categories.edit', $category) }}" class="btn btn-sm btn-outline-success rounded-pill">{{ $strings::EDIT }}</a>
                <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" onsubmit="return confirm(@js($category->products_count > 0 ? ($category->parent_id ? 'سيتم نقل المنتجات إلى القسم الأب ثم حذف هذا القسم. هل أنت متأكد؟' : 'سيتم نقل المنتجات إلى قسم آخر ثم حذف هذا القسم. هل أنت متأكد؟') : $strings::CONFIRM_DELETE))">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger rounded-pill">{{ $strings::DELETE }}</button>
                </form>
            </div>
        </div>
        @if ($category->children->isNotEmpty())
            <div class="mt-2">
                @include('admin.categories._tree', ['nodes' => $category->children, 'depth' => $depth + 1])
            </div>
        @endif
    </div>
@endforeach
