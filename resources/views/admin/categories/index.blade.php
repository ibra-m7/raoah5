@php
    $openLabel = match ($depth) {
        0 => 'الأقسام',
        1 => 'التصنيفات',
        default => null,
    };
    $countOne = match ($depth) {
        0 => 'قسم',
        1 => 'تصنيف',
        default => 'منتج',
    };
    $countMany = match ($depth) {
        0 => 'أقسام',
        1 => 'تصنيفات',
        default => 'منتجات',
    };
@endphp

<x-layouts.admin :title="$title">
    <x-admin.page-head
        :title="$title"
        :create="$createUrl"
        :create-label="$createLabel"
    />

    <div class="page-card p-0 overflow-hidden">
        <div class="p-4">
            <nav class="catalog-crumb" aria-label="مسار الأقسام">
                <a href="{{ route('admin.categories.index') }}" @class(['is-current' => ! $parent])>التبويبات</a>
                @foreach ($ancestors as $crumb)
                    <span class="catalog-crumb-sep"><i class="bi bi-chevron-left"></i></span>
                    <a href="{{ route('admin.categories.index', ['parent' => $crumb->id]) }}" @class(['is-current' => $loop->last])>{{ $crumb->name }}</a>
                @endforeach
            </nav>

            @if ($items->isEmpty())
                <x-admin.empty-state icon="bi-grid" :action="$createUrl" :action-label="$createLabel" />
            @else
                <div class="table-responsive">
                    <table class="table mb-0 catalog-table">
                        <thead>
                            <tr>
                                <th>الاسم</th>
                                <th>{{ $depth >= 2 ? 'المنتجات' : 'المحتوى' }}</th>
                                <th>{{ $strings::STATUS }}</th>
                                <th>{{ $strings::ACTIONS }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($items as $category)
                                @php
                                    $thumb = $category->image_src ?: $category->icon_src;
                                    $openUrl = $openLabel
                                        ? route('admin.categories.index', ['parent' => $category->id])
                                        : null;
                                    $childCount = $depth >= 2
                                        ? (int) $category->products_count
                                        : (int) $category->children_count;
                                    $contentLabel = $childCount.' '.($childCount === 1 ? $countOne : $countMany);
                                @endphp
                                <tr>
                                    <td>
                                        <span class="d-inline-flex align-items-center gap-2">
                                            <span class="table-thumb-wrap">
                                                @if ($thumb)
                                                    <img src="{{ $thumb }}" alt="" class="table-thumb">
                                                @else
                                                    <i class="bi {{ $depth === 0 ? 'bi-folder' : ($depth === 1 ? 'bi-grid' : 'bi-tag') }}"></i>
                                                @endif
                                            </span>
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
                                    </td>
                                    <td>{{ $contentLabel }}</td>
                                    <td>
                                        <span class="badge badge-soft">{{ $category->is_active ? $strings::ACTIVE : $strings::INACTIVE }}</span>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-1">
                                            @if ($openUrl)
                                                <a href="{{ $openUrl }}" class="btn btn-sm btn-outline-success rounded-pill">{{ $openLabel }}</a>
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
