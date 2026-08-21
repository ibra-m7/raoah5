@php
    $tabs = [
        'mains' => [
            'label' => 'الأقسام الرئيسية',
            'icon' => 'bi-circle',
            'create' => 'إضافة قسم رئيسي',
            'empty' => 'لا توجد أقسام رئيسية بعد.',
            'childTab' => 'branches',
            'openLabel' => 'فتح الأقسام الفرعية',
            'addChild' => 'إضافة قسم فرعي',
            'countOne' => 'قسم فرعي',
            'countMany' => 'أقسام فرعية',
        ],
        'branches' => [
            'label' => 'الأقسام الفرعية',
            'icon' => 'bi-grid',
            'create' => 'إضافة قسم فرعي',
            'empty' => $scope ? 'لا توجد أقسام فرعية هنا بعد.' : 'لا توجد أقسام فرعية بعد.',
            'childTab' => 'classes',
            'openLabel' => 'فتح التصنيفات',
            'addChild' => 'إضافة تصنيف',
            'countOne' => 'تصنيف',
            'countMany' => 'تصنيفات',
        ],
        'classes' => [
            'label' => 'التصنيفات',
            'icon' => 'bi-tags',
            'create' => 'إضافة تصنيف',
            'empty' => $scope ? 'لا توجد تصنيفات هنا بعد.' : 'لا توجد تصنيفات بعد.',
            'childTab' => null,
            'openLabel' => null,
            'addChild' => null,
            'countOne' => 'منتج',
            'countMany' => 'منتجات',
        ],
    ];
    $current = $tabs[$tab] ?? $tabs['mains'];
    $filterLabel = $tab === 'branches' ? 'القسم الرئيسي' : 'القسم الفرعي';
@endphp

<x-layouts.admin :title="$title">
    <x-admin.page-head
        :title="$title"
        :create="$createUrl"
        :create-label="$current['create']"
    />

    <div class="page-card p-0 overflow-hidden">
        <ul class="nav settings-tabs" role="tablist">
            @foreach ($tabs as $key => $meta)
                <li class="nav-item" role="presentation">
                    <a class="nav-link {{ $tab === $key ? 'active' : '' }}" href="{{ route('admin.categories.index', ['tab' => $key]) }}">
                        <i class="bi {{ $meta['icon'] }}"></i>
                        {{ $meta['label'] }}
                        <span class="badge badge-soft">{{ $counts[$key] ?? 0 }}</span>
                    </a>
                </li>
            @endforeach
        </ul>

        <div class="p-4">
            <nav class="catalog-crumb" aria-label="مسار الأقسام">
                <a href="{{ route('admin.categories.index', ['tab' => 'mains']) }}">الأقسام</a>
                @foreach ($ancestors as $crumb)
                    @php
                        $crumbTab = $loop->first ? 'branches' : 'classes';
                    @endphp
                    <span class="catalog-crumb-sep"><i class="bi bi-chevron-left"></i></span>
                    <a href="{{ route('admin.categories.index', ['tab' => $crumbTab, 'parent' => $crumb->id]) }}" @class(['is-current' => $loop->last])>{{ $crumb->name }}</a>
                @endforeach
            </nav>

            @if ($tab !== 'mains')
                <form method="GET" action="{{ route('admin.categories.index') }}" class="catalog-toolbar">
                    <input type="hidden" name="tab" value="{{ $tab }}">
                    <label class="catalog-filter">
                        <span>{{ $filterLabel }}</span>
                        <select name="parent" class="form-select" onchange="this.form.submit()">
                            <option value="">الكل</option>
                            @foreach ($filters as $option)
                                <option value="{{ $option->id }}" @selected((int) optional($scope)->id === (int) $option->id)>
                                    {{ $option->path_label ?? $option->name }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                </form>
            @endif

            @if ($items->isEmpty())
                <x-admin.empty-state :icon="$current['icon']" :message="$current['empty']" :action="$createUrl" :action-label="$current['create']" />
            @else
                <div class="table-responsive">
                    <table class="table mb-0 catalog-table">
                        <thead>
                            <tr>
                                <th>الاسم</th>
                                @if ($tab !== 'mains')
                                    <th>يقع تحت</th>
                                @endif
                                <th>{{ $tab === 'classes' ? 'المنتجات' : 'المحتوى' }}</th>
                                <th>{{ $strings::STATUS }}</th>
                                <th>{{ $strings::ACTIONS }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($items as $category)
                                @php
                                    $thumb = $category->image_src ?: $category->icon_src;
                                    $parentLabel = $category->path_label
                                        ? trim(str_replace(' ← '.$category->name, '', $category->path_label))
                                        : '—';
                                    $openUrl = $current['childTab']
                                        ? route('admin.categories.index', ['tab' => $current['childTab'], 'parent' => $category->id])
                                        : null;
                                    $childCount = (int) $category->children_count;
                                    $contentLabel = $tab === 'classes'
                                        ? $category->products_count
                                        : $childCount.' '.($childCount === 1 ? $current['countOne'] : $current['countMany']);
                                @endphp
                                <tr>
                                    <td>
                                        <span class="d-inline-flex align-items-center gap-2">
                                            <span class="table-thumb-wrap">
                                                @if ($thumb)
                                                    <img src="{{ $thumb }}" alt="" class="table-thumb">
                                                @else
                                                    <i class="bi {{ $current['icon'] }}"></i>
                                                @endif
                                            </span>
                                            <span>
                                                @if ($openUrl)
                                                    <a href="{{ $openUrl }}" class="catalog-name-link">
                                                        <span class="color-dot" style="background: {{ $category->color ?: '#88D498' }}"></span>
                                                        {{ $category->name }}
                                                        <i class="bi bi-chevron-left catalog-name-chevron"></i>
                                                    </a>
                                                @else
                                                    <strong>
                                                        <span class="color-dot" style="background: {{ $category->color ?: '#88D498' }}"></span>
                                                        {{ $category->name }}
                                                    </strong>
                                                @endif
                                            </span>
                                        </span>
                                    </td>
                                    @if ($tab !== 'mains')
                                        <td>
                                            @if ($category->parent_id)
                                                <a class="catalog-parent-link" href="{{ route('admin.categories.index', ['tab' => $tab === 'classes' ? 'branches' : 'mains', 'parent' => $tab === 'classes' ? $category->parent_id : null]) }}">
                                                    {{ $parentLabel !== $category->name ? $parentLabel : '—' }}
                                                </a>
                                            @else
                                                —
                                            @endif
                                        </td>
                                    @endif
                                    <td>{{ $contentLabel }}</td>
                                    <td>
                                        <span class="badge badge-soft">{{ $category->is_active ? $strings::ACTIVE : $strings::INACTIVE }}</span>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-1">
                                            @if ($openUrl)
                                                <a href="{{ $openUrl }}" class="btn btn-sm btn-outline-success rounded-pill">{{ $current['openLabel'] }}</a>
                                                <a href="{{ route('admin.categories.create', ['parent_id' => $category->id, 'tab' => $current['childTab']]) }}" class="btn btn-sm btn-outline-success rounded-pill">{{ $current['addChild'] }}</a>
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
