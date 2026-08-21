<x-layouts.admin :title="$title">
    <x-admin.page-head
        :title="$title"
        subtitle="شجرة الأقسام كما في المتاجر العالمية: قسم رئيسي ← تصنيف ← تصنيف فرعي ← منتج."
        :create="route('admin.categories.create')"
        :create-label="$strings::ADD_CATEGORY"
    />

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="page-card p-3 h-100">
                <div class="badge badge-soft mb-2">1</div>
                <strong class="d-block mb-1">قسم رئيسي</strong>
                <p class="text-muted mb-0" style="font-size: .9rem; line-height: 1.6">بدون أب. يظهر كدائرة في الرئيسية، مثل «مواد غذائية».</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="page-card p-3 h-100">
                <div class="badge badge-soft mb-2">2</div>
                <strong class="d-block mb-1">تصنيف</strong>
                <p class="text-muted mb-0" style="font-size: .9rem; line-height: 1.6">تحت القسم الرئيسي. يظهر كبطاقة في تبويب الأقسام، مثل «الأطعمة المجمدة».</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="page-card p-3 h-100">
                <div class="badge badge-soft mb-2">3</div>
                <strong class="d-block mb-1">تصنيف فرعي</strong>
                <p class="text-muted mb-0" style="font-size: .9rem; line-height: 1.6">تحت التصنيف. يظهر كشريحة داخل البطاقة، مثل «دجاج مجمد». ضع المنتجات هنا.</p>
            </div>
        </div>
    </div>

    <div class="page-card p-4">
        @if ($tree->isEmpty())
            <x-admin.empty-state icon="bi-grid" :action="route('admin.categories.create')" :action-label="$strings::ADD_CATEGORY" />
        @else
            @include('admin.categories._tree', ['nodes' => $tree, 'depth' => 0])
        @endif
    </div>
</x-layouts.admin>
