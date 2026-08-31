<x-layouts.admin :title="$title">
    @if ($q === '')
        <div class="page-card p-5 text-center text-muted">اكتب في البحث أعلى الصفحة للوصول لأي منتج أو قسم أو طلب خلال ثانية.</div>
    @else
        <div class="row g-4">
            <div class="col-lg-6">
                <div class="page-card p-4 h-100">
                    <h2 class="h5 fw-bold mb-3">المنتجات</h2>
                    @forelse ($products as $product)
                        <a href="{{ route('admin.products.edit', $product) }}" class="d-flex justify-content-between text-decoration-none text-reset py-2 border-bottom">
                            <span class="fw-bold">{{ $product->name }}</span>
                            <span class="text-muted">{{ $product->category?->name }}</span>
                        </a>
                    @empty
                        <div class="text-muted">لا نتائج</div>
                    @endforelse
                </div>
            </div>
            <div class="col-lg-6">
                <div class="page-card p-4 h-100">
                    <h2 class="h5 fw-bold mb-3">الأقسام</h2>
                    @forelse ($categories as $category)
                        <a href="{{ route('admin.categories.edit', $category) }}" class="d-flex justify-content-between text-decoration-none text-reset py-2 border-bottom">
                            <span class="fw-bold">{{ $category->name }}</span>
                            <span class="text-muted">{{ $category->parent?->name ?? 'رئيسي' }}</span>
                        </a>
                    @empty
                        <div class="text-muted">لا نتائج</div>
                    @endforelse
                </div>
            </div>
            <div class="col-lg-6">
                <div class="page-card p-4 h-100">
                    <h2 class="h5 fw-bold mb-3">الطلبات</h2>
                    @forelse ($orders as $order)
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span class="fw-bold">{{ $order->order_number }}</span>
                            <span class="text-muted">{{ $order->user?->name }} — {{ $order->status?->label() }}</span>
                        </div>
                    @empty
                        <div class="text-muted">لا نتائج</div>
                    @endforelse
                </div>
            </div>
            <div class="col-lg-6">
                <div class="page-card p-4 h-100">
                    <h2 class="h5 fw-bold mb-3">الإعلانات</h2>
                    @forelse ($banners as $banner)
                        <a href="{{ route('admin.banners.edit', $banner) }}" class="d-flex justify-content-between text-decoration-none text-reset py-2 border-bottom">
                            <span class="fw-bold">{{ $banner->title }}</span>
                            <span class="text-muted">{{ $banner->link_type?->label() }}</span>
                        </a>
                    @empty
                        <div class="text-muted">لا نتائج</div>
                    @endforelse
                </div>
            </div>
            <div class="col-lg-6">
                <div class="page-card p-4 h-100">
                    <h2 class="h5 fw-bold mb-3">أقسام الرئيسية</h2>
                    @forelse ($homeSections as $section)
                        <a href="{{ route('admin.home-sections.edit', $section) }}" class="d-flex justify-content-between text-decoration-none text-reset py-2 border-bottom">
                            <span class="fw-bold">{{ $section->title }}</span>
                            <span class="text-muted">{{ $section->contentTypeLabel() }}</span>
                        </a>
                    @empty
                        <div class="text-muted">لا نتائج</div>
                    @endforelse
                </div>
            </div>
            <div class="col-lg-6">
                <div class="page-card p-4 h-100">
                    <h2 class="h5 fw-bold mb-3">سلات التوفير</h2>
                    @forelse ($bundles as $bundle)
                        @php
                            $bundleSection = $bundle->homeSections()->first();
                        @endphp
                        @if ($bundleSection)
                            <a href="{{ route('admin.home-sections.bundles.edit', [$bundleSection, $bundle]) }}" class="d-flex justify-content-between text-decoration-none text-reset py-2 border-bottom">
                                <span class="fw-bold">{{ $bundle->name }}</span>
                                <span class="text-muted">{{ number_format((float) $bundle->bundle_price, 2) }} {{ $strings::CURRENCY }}</span>
                            </a>
                        @else
                            <div class="d-flex justify-content-between py-2 border-bottom">
                                <span class="fw-bold">{{ $bundle->name }}</span>
                                <span class="text-muted">{{ number_format((float) $bundle->bundle_price, 2) }} {{ $strings::CURRENCY }}</span>
                            </div>
                        @endif
                    @empty
                        <div class="text-muted">لا نتائج</div>
                    @endforelse
                </div>
            </div>
            <div class="col-lg-6">
                <div class="page-card p-4 h-100">
                    <h2 class="h5 fw-bold mb-3">العملاء</h2>
                    @forelse ($customers as $customer)
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span class="fw-bold">{{ $customer->name }}</span>
                            <span class="text-muted">{{ $customer->phone }}</span>
                        </div>
                    @empty
                        <div class="text-muted">لا نتائج</div>
                    @endforelse
                </div>
            </div>
        </div>
    @endif
</x-layouts.admin>
