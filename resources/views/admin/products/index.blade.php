<x-layouts.admin :title="$title">
    <x-admin.page-head
        :title="$title"
        :create="route('admin.products.create')"
        :create-label="$strings::ADD_PRODUCT"
    />
    <div class="d-flex flex-wrap gap-2 mb-3">
        <a href="{{ route('admin.products.import.template') }}" class="btn btn-outline-success rounded-pill">
            <i class="bi bi-download ms-1"></i>
            {{ $strings::DOWNLOAD_PRODUCT_TEMPLATE }}
        </a>
        <a href="{{ route('admin.products.import') }}" class="btn btn-outline-success rounded-pill">
            <i class="bi bi-file-earmark-excel ms-1"></i>
            {{ $strings::IMPORT_PRODUCTS }}
        </a>
        <form method="POST" action="{{ route('admin.products.generate-all-copy') }}" class="d-inline">
            @csrf
            <button
                type="submit"
                class="btn btn-success rounded-pill"
                title="توليد الوصف والتصنيف والفوائد وكلمات البحث وطريقة الاستخدام لجميع المنتجات"
                onclick="if (!confirm(@json($strings::CONFIRM_GENERATE_ALL_PRODUCT_COPY))) return false; this.disabled = true; this.innerHTML = '<span class=&quot;spinner-border spinner-border-sm ms-1&quot;></span> جاري التوليد...'; this.form.submit();"
            >
                <i class="bi bi-stars ms-1"></i>
                {{ $strings::GENERATE_PRODUCT_COPY }}
            </button>
        </form>
    </div>
    @if (session('import_errors'))
        <div class="page-card p-3 mb-3 border border-danger-subtle">
            <div class="fw-bold text-danger mb-2">صفوف لم تُستورد</div>
            <ul class="mb-0 small">
                @foreach (session('import_errors') as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <div class="d-flex justify-content-between align-items-center mb-3">
        <form method="GET" class="d-flex flex-wrap gap-2">
            <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control" style="max-width: 260px" placeholder="ابحث عن منتج أو قسم أو كلمة...">
            <select name="category_id" class="form-select" style="max-width: 180px">
                <option value="">كل الأقسام</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) ($filters['category_id'] ?? '') === (string) $category->id)>
                        {{ $category->name }}
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
        @if ($products->isEmpty())
            <x-admin.empty-state icon="bi-box-seam" :action="route('admin.products.create')" :action-label="$strings::ADD_PRODUCT" />
        @else
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>المنتج</th>
                            <th>القسم</th>
                            <th>السعر</th>
                            <th>المخزون</th>
                            <th>{{ $strings::STATUS }}</th>
                            <th>{{ $strings::ACTIONS }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($products as $product)
                            @php
                                $image = \App\Support\Media::url($product->primaryImage?->url);
                                $productDetail = [
                                    'title' => $product->name,
                                    'image' => $image,
                                    'badges' => array_values(array_filter([
                                        $product->is_active ? $strings::ACTIVE : $strings::INACTIVE,
                                        $product->is_featured ? 'مميز' : null,
                                        $product->has_discount ? 'خصم '.$product->discount_percent.'%' : null,
                                    ])),
                                    'fields' => array_values(array_filter([
                                        ['label' => 'رمز المنتج', 'value' => $product->sku],
                                        ['label' => 'الباركود', 'value' => $product->barcode],
                                        ['label' => 'القسم', 'value' => $product->category?->name],
                                        ['label' => 'السعر', 'value' => number_format((float) $product->price, 2).' '.$strings::CURRENCY],
                                        ['label' => 'سعر العرض', 'value' => $product->has_discount ? number_format((float) $product->discount_price, 2).' '.$strings::CURRENCY : null],
                                        ['label' => 'المخزون', 'value' => (string) $product->stock],
                                        ['label' => 'التقييم', 'value' => $product->rating ? $product->rating.' ('.$product->review_count.')' : null],
                                    ], fn ($row) => filled($row['value'] ?? null))),
                                    'blocks' => array_values(array_filter([
                                        ['label' => 'الوصف', 'text' => $product->description],
                                        ['label' => 'الفوائد', 'list' => $product->benefits ?? []],
                                        ['label' => 'طريقة الاستخدام', 'text' => $product->usage_instructions],
                                        ['label' => 'كلمات البحث', 'text' => implode('، ', $product->keywords ?? [])],
                                    ], fn ($block) => filled($block['text'] ?? null) || ! empty($block['list'] ?? []))),
                                    'edit_url' => route('admin.products.edit', $product),
                                ];
                            @endphp
                            <tr>
                                <td>
                                    <button type="button" class="entity-open" data-detail='@json($productDetail)'>
                                        <span class="table-thumb-wrap">
                                            @if ($image)
                                                <img src="{{ $image }}" alt="" class="table-thumb">
                                            @else
                                                <i class="bi bi-box-seam"></i>
                                            @endif
                                        </span>
                                        <span class="entity-open-text">
                                            <strong>{{ $product->name }}</strong>
                                            <small>{{ $product->sku }}@if($product->barcode) · {{ $product->barcode }}@endif</small>
                                        </span>
                                    </button>
                                </td>
                                <td>{{ $product->category?->name }}</td>
                                <td>
                                    {{ number_format((float) $product->effective_price, 2) }} {{ $strings::CURRENCY }}
                                    @if ($product->has_discount)
                                        <span class="price-old">{{ number_format((float) $product->price, 2) }}</span>
                                        <span class="badge badge-sale">{{ $product->discount_percent }}%</span>
                                    @endif
                                </td>
                                <td>{{ $product->stock }}</td>
                                <td>
                                    <span class="badge badge-soft">{{ $product->is_active ? 'نشط' : 'مخفي' }}</span>
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-sm btn-outline-success rounded-pill">{{ $strings::EDIT }}</a>
                                        <form method="POST" action="{{ route('admin.products.destroy', $product) }}" onsubmit="return confirm('{{ $strings::CONFIRM_DELETE }}')">
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
            {{ $products->links() }}
        @endif
    </div>
</x-layouts.admin>
