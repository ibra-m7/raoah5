@php
    $tabs = [
        'roots' => ['label' => 'الأقسام الرئيسية', 'icon' => 'bi-circle', 'items' => $groups['roots'], 'create' => 'إضافة قسم رئيسي'],
        'categories' => ['label' => 'التصنيفات', 'icon' => 'bi-grid', 'items' => $groups['categories'], 'create' => 'إضافة تصنيف'],
        'subs' => ['label' => 'التصنيفات الفرعية', 'icon' => 'bi-tags', 'items' => $groups['subs'], 'create' => 'إضافة تصنيف فرعي'],
    ];
    $current = $tabs[$tab] ?? $tabs['roots'];
@endphp

<x-layouts.admin :title="$title">
    <x-admin.page-head
        :title="$title"
        :create="route('admin.categories.create', ['tab' => $tab])"
        :create-label="$current['create']"
    />

    <div class="page-card p-0 overflow-hidden">
        <ul class="nav settings-tabs" role="tablist">
            @foreach ($tabs as $key => $meta)
                <li class="nav-item" role="presentation">
                    <a class="nav-link {{ $tab === $key ? 'active' : '' }}" href="{{ route('admin.categories.index', ['tab' => $key]) }}">
                        <i class="bi {{ $meta['icon'] }}"></i>
                        {{ $meta['label'] }}
                        <span class="badge badge-soft">{{ $meta['items']->count() }}</span>
                    </a>
                </li>
            @endforeach
        </ul>

        <div class="p-4">
            @if ($current['items']->isEmpty())
                <x-admin.empty-state icon="bi-grid" :action="route('admin.categories.create', ['tab' => $tab])" :action-label="$current['create']" />
            @else
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>الاسم</th>
                                @if ($tab !== 'roots')
                                    <th>يقع تحت</th>
                                @endif
                                @if ($tab === 'categories')
                                    <th>التبويب</th>
                                @endif
                                <th>المنتجات</th>
                                <th>{{ $strings::STATUS }}</th>
                                <th>{{ $strings::ACTIONS }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($current['items'] as $category)
                                @php
                                    $thumb = $category->image_src ?: $category->icon_src;
                                    $parentLabel = $category->path_label
                                        ? trim(str_replace(' ← '.$category->name, '', $category->path_label))
                                        : '—';
                                    $tabNames = $category->displaySections
                                        ->map(fn ($section) => trim(($section->emoji ? $section->emoji.' ' : '').$section->name))
                                        ->filter()
                                        ->values();
                                    $childTab = $tab === 'roots' ? 'categories' : 'subs';
                                @endphp
                                <tr>
                                    <td>
                                        <span class="d-inline-flex align-items-center gap-2">
                                            <span class="table-thumb-wrap">
                                                @if ($thumb)
                                                    <img src="{{ $thumb }}" alt="" class="table-thumb">
                                                @else
                                                    <i class="bi bi-grid"></i>
                                                @endif
                                            </span>
                                            <strong>
                                                <span class="color-dot" style="background: {{ $category->color ?: '#88D498' }}"></span>
                                                {{ $category->name }}
                                            </strong>
                                        </span>
                                    </td>
                                    @if ($tab !== 'roots')
                                        <td>{{ $parentLabel !== $category->name ? $parentLabel : '—' }}</td>
                                    @endif
                                    @if ($tab === 'categories')
                                        <td>{{ $tabNames->isNotEmpty() ? $tabNames->implode('، ') : '—' }}</td>
                                    @endif
                                    <td>{{ $category->products_count }}</td>
                                    <td>
                                        <span class="badge badge-soft">{{ $category->is_active ? $strings::ACTIVE : $strings::INACTIVE }}</span>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-1">
                                            @if ($tab !== 'subs')
                                                <a href="{{ route('admin.categories.create', ['parent_id' => $category->id, 'tab' => $childTab]) }}" class="btn btn-sm btn-outline-success rounded-pill">إضافة فرعي</a>
                                            @endif
                                            <a href="{{ route('admin.categories.edit', $category) }}" class="btn btn-sm btn-outline-success rounded-pill">{{ $strings::EDIT }}</a>
                                            <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" onsubmit="return confirm(@js($strings::CONFIRM_DELETE))">
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
            @endif
        </div>
    </div>
</x-layouts.admin>
