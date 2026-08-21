<x-layouts.admin :title="$title">
    <x-admin.page-head
        :title="$title"
        :create="route('admin.categories.create')"
        create-label="إضافة قسم رئيسي"
    />

    <div class="page-card p-4">
        @if ($tree->isEmpty())
            <x-admin.empty-state icon="bi-grid" :action="route('admin.categories.create')" action-label="إضافة قسم رئيسي" />
        @else
            <div class="category-tree">
                @foreach ($tree as $category)
                    @include('admin.categories._node', ['category' => $category, 'depth' => 0])
                @endforeach
            </div>
        @endif
    </div>
</x-layouts.admin>
