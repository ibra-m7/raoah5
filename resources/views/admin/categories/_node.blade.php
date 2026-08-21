@php
    $depth = $depth ?? 0;
    $type = match ($depth) {
        0 => 'قسم رئيسي',
        1 => 'قسم فرعي',
        default => 'تصنيف',
    };
    $addChild = match ($depth) {
        0 => 'إضافة قسم فرعي',
        1 => 'إضافة تصنيف',
        default => null,
    };
    $thumb = $category->image_src ?: $category->icon_src;
@endphp

<div class="category-tree-node">
    <div @class(['category-tree-row', 'is-main' => $depth === 0, 'is-leaf' => $depth >= 2])>
        <span class="table-thumb-wrap">
            @if ($thumb)
                <img src="{{ $thumb }}" alt="" class="table-thumb">
            @else
                <i class="bi {{ $depth === 0 ? 'bi-circle' : ($depth === 1 ? 'bi-grid' : 'bi-tag') }}"></i>
            @endif
        </span>
        <div class="category-tree-copy">
            <strong>
                <span class="color-dot" style="background: {{ $category->color ?: '#88D498' }}"></span>
                {{ $category->name }}
            </strong>
            <small>
                {{ $type }}
                @if ($depth < 2)
                    · {{ $category->children_count }} {{ $depth === 0 ? 'فرعي' : 'تصنيف' }}
                @else
                    · {{ $category->products_count }} منتج
                @endif
                · {{ $category->is_active ? $strings::ACTIVE : $strings::INACTIVE }}
            </small>
        </div>
        <div class="category-tree-actions">
            @if ($addChild)
                <a href="{{ route('admin.categories.create', ['parent_id' => $category->id]) }}" class="btn btn-sm btn-outline-success rounded-pill">{{ $addChild }}</a>
            @endif
            <a href="{{ route('admin.categories.edit', $category) }}" class="btn btn-sm btn-outline-success rounded-pill">{{ $strings::EDIT }}</a>
            <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" onsubmit="return confirm(@js($strings::CONFIRM_DELETE))">
                @csrf
                @method('DELETE')
                <button class="btn btn-sm btn-outline-danger rounded-pill">{{ $strings::DELETE }}</button>
            </form>
        </div>
    </div>
    @if ($category->children->isNotEmpty())
        <div class="category-tree-kids">
            @foreach ($category->children as $child)
                @include('admin.categories._node', ['category' => $child, 'depth' => $depth + 1])
            @endforeach
        </div>
    @endif
</div>
