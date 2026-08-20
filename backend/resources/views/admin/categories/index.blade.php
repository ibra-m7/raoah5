<x-layouts.admin :title="$title">
    <x-admin.page-head
        :title="$title"
        subtitle="القسم بدون أب يظهر كدائرة في الرئيسية. الأقسام الفرعية تظهر داخل القسم الأب."
        :create="route('admin.categories.create')"
        :create-label="$strings::ADD_CATEGORY"
    />
    <x-admin.help-note>اترك «القسم الأب» فارغاً ليصبح قسماً رئيسياً. عند حذف قسم وفيه منتجات، تُنقل المنتجات تلقائياً إلى القسم الرئيسي (الأب) ثم يُحذف القسم.</x-admin.help-note>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <form method="GET" class="d-flex flex-wrap gap-2">
            <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control" style="max-width: 220px" placeholder="{{ $strings::SEARCH }}">
            <select name="parent_id" class="form-select" style="max-width: 180px">
                <option value="">{{ $strings::ALL }}</option>
                <option value="root" @selected(($filters['parent_id'] ?? '') === 'root')">{{ $strings::ROOT_CATEGORY }}</option>
                @foreach ($parents as $parent)
                    <option value="{{ $parent->id }}" @selected((string) ($filters['parent_id'] ?? '') === (string) $parent->id)>
                        {{ $parent->name }}
                    </option>
                @endforeach
            </select>
            <select name="status" class="form-select" style="max-width: 140px">
                <option value="">{{ $strings::STATUS }}</option>
                <option value="active" @selected(($filters['status'] ?? '') === 'active')">{{ $strings::ACTIVE }}</option>
                <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')">{{ $strings::INACTIVE }}</option>
            </select>
            <button class="btn btn-outline-success rounded-pill">{{ $strings::FILTER }}</button>
        </form>
    </div>

    <div class="page-card p-4">
        @if ($categories->isEmpty())
            <x-admin.empty-state icon="bi-grid" :action="route('admin.categories.create')" :action-label="$strings::ADD_CATEGORY" />
        @else
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>القسم</th>
                            <th>القسم الأب</th>
                            <th>المنتجات</th>
                            <th>{{ $strings::STATUS }}</th>
                            <th>{{ $strings::ACTIONS }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($categories as $category)
                            @php
                                $categoryDetail = [
                                    'title' => $category->name,
                                    'image' => $category->image_src ?: $category->icon_src,
                                    'badges' => array_values(array_filter([
                                        $category->is_active ? $strings::ACTIVE : $strings::INACTIVE,
                                        $category->parent_id ? null : $strings::ROOT_CATEGORY,
                                    ])),
                                    'fields' => array_values(array_filter([
                                        ['label' => $strings::PARENT_CATEGORY, 'value' => $category->parent?->name ?? $strings::ROOT_CATEGORY],
                                        ['label' => 'عدد المنتجات', 'value' => (string) $category->products_count],
                                        ['label' => 'الأقسام الفرعية', 'value' => (string) $category->children_count],
                                        ['label' => $strings::COLOR, 'value' => $category->color],
                                        ['label' => $strings::SORT_ORDER, 'value' => (string) $category->sort_order],
                                        ['label' => 'المعرف', 'value' => $category->slug],
                                    ], fn ($row) => filled($row['value'] ?? null))),
                                    'blocks' => [],
                                    'edit_url' => route('admin.categories.edit', $category),
                                    'color' => $category->color,
                                    'icon' => $category->icon_src,
                                ];
                            @endphp
                            <tr>
                                <td>
                                    <button type="button" class="entity-open" data-detail='@json($categoryDetail)'>
                                        <span class="table-thumb-wrap">
                                            @if ($category->icon_src)
                                                <img src="{{ $category->icon_src }}" alt="" class="table-thumb">
                                            @else
                                                <i class="bi bi-grid"></i>
                                            @endif
                                        </span>
                                        <span class="entity-open-text">
                                            <strong>
                                                <span class="color-dot" style="background: {{ $category->color ?: '#88D498' }}"></span>
                                                {{ $category->name }}
                                            </strong>
                                            <small>عرض التفاصيل</small>
                                        </span>
                                    </button>
                                </td>
                                <td>{{ $category->parent?->name ?? '—' }}</td>
                                <td>{{ $category->products_count }}</td>
                                <td>
                                    <span class="badge badge-soft">{{ $category->is_active ? $strings::ACTIVE : $strings::INACTIVE }}</span>
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('admin.categories.edit', $category) }}" class="btn btn-sm btn-outline-success rounded-pill">{{ $strings::EDIT }}</a>
                                        <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" onsubmit="return confirm(@js($category->products_count > 0 ? ($category->parent ? 'سيتم نقل المنتجات إلى «'.$category->parent->name.'» ثم حذف القسم. هل أنت متأكد؟' : 'سيتم نقل المنتجات إلى قسم آخر ثم حذف هذا القسم. هل أنت متأكد؟') : $strings::CONFIRM_DELETE))">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger rounded-pill">{{ $strings::DELETE }}</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $categories->links() }}
        @endif
    </div>
</x-layouts.admin>
