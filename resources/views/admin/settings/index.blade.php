@php
    $tab = old('active_tab', $tab ?? 'app');
    $scope = old('marketing_sold_scope', $settings['marketing_sold_scope']);
    $selected = collect(old('marketing_sold_product_ids', $selectedProductIds ?? []))->map(fn ($id) => (int) $id);
    $selectAllByDefault = $selectedProductIds === null && ! old('marketing_sold_product_ids');
@endphp

<x-layouts.admin :title="$title">
    <x-admin.page-head
        :title="$title"
        subtitle="إدارة إعدادات التطبيق والمتجر والعروض التسويقية من مكان واحد"
    />

    <div class="page-card p-0 overflow-hidden" style="max-width: 920px">
        <form method="POST" action="{{ route('admin.settings.update') }}" data-settings-form enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <input type="hidden" name="active_tab" value="{{ $tab }}" data-settings-active-tab>

            <ul class="nav settings-tabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button type="button" class="nav-link {{ $tab === 'app' ? 'active' : '' }}" id="tab-app" data-bs-toggle="tab" data-bs-target="#pane-app" data-settings-tab="app" role="tab" aria-controls="pane-app" aria-selected="{{ $tab === 'app' ? 'true' : 'false' }}">
                        <i class="bi bi-phone"></i>
                        التطبيق
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button type="button" class="nav-link {{ $tab === 'store' ? 'active' : '' }}" id="tab-store" data-bs-toggle="tab" data-bs-target="#pane-store" data-settings-tab="store" role="tab" aria-controls="pane-store" aria-selected="{{ $tab === 'store' ? 'true' : 'false' }}">
                        <i class="bi bi-shop"></i>
                        المتجر
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button type="button" class="nav-link {{ $tab === 'marketing' ? 'active' : '' }}" id="tab-marketing" data-bs-toggle="tab" data-bs-target="#pane-marketing" data-settings-tab="marketing" role="tab" aria-controls="pane-marketing" aria-selected="{{ $tab === 'marketing' ? 'true' : 'false' }}">
                        <i class="bi bi-megaphone"></i>
                        العروض والتسويق
                    </button>
                </li>
            </ul>

            <div class="tab-content p-4 p-md-5">
                <div class="tab-pane fade {{ $tab === 'app' ? 'show active' : '' }}" id="pane-app" role="tabpanel" aria-labelledby="tab-app" tabindex="0">
                    <h2 class="settings-pane-title">إعدادات التطبيق</h2>
                    <p class="settings-pane-lead">الاسم والعملة كما يظهران للعميل داخل التطبيق.</p>

                    <div class="mb-3">
                        <label class="form-label">اسم المتجر في التطبيق</label>
                        <input type="text" name="store_name" value="{{ old('store_name', $settings['store_name']) }}" class="form-control @error('store_name') is-invalid @enderror" required>
                        @error('store_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-4">
                        <label class="form-label">العملة</label>
                        <div class="form-control-plaintext fs-4">{{ $strings::CURRENCY }} ريال سعودي</div>
                        <div class="form-hint">يُعرض الرمز الرسمي للريال السعودي في التطبيق ولوحة التحكم.</div>
                        <input type="hidden" name="currency" value="SAR">
                    </div>

                    @php $fallbackSrc = \App\Support\Media::url($settings['fallback_product_image'] ?? ''); @endphp
                    <div class="mb-3">
                        <label class="form-label">صورة المنتج الافتراضية</label>
                        <input type="file" name="fallback_product_image" accept="image/*" class="form-control @error('fallback_product_image') is-invalid @enderror" data-image-preview="#fallback-product-preview">
                        @error('fallback_product_image') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <img id="fallback-product-preview" src="{{ $fallbackSrc }}" alt="" class="upload-preview mt-2" style="width: 120px; height: 120px; object-fit: cover" @if(! $fallbackSrc) hidden @endif>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">أو رابط الصورة الافتراضية</label>
                        <input type="url" name="fallback_product_image_url" value="{{ old('fallback_product_image_url', str_starts_with((string) ($settings['fallback_product_image'] ?? ''), 'http') ? $settings['fallback_product_image'] : '') }}" class="form-control @error('fallback_product_image_url') is-invalid @enderror" placeholder="https://...">
                        @error('fallback_product_image_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-hint">تظهر هذه الصورة بدل أي منتج بلا صورة داخل التطبيق.</div>
                    </div>

                    <div class="settings-links">
                        <a href="{{ route('admin.ai.index') }}" class="settings-link">
                            <i class="bi bi-stars"></i>
                            <span>المساعد الذكي</span>
                        </a>
                        <a href="{{ route('admin.onboarding.index') }}" class="settings-link">
                            <i class="bi bi-collection"></i>
                            <span>شرائح التعريف</span>
                        </a>
                    </div>
                </div>

                <div class="tab-pane fade {{ $tab === 'store' ? 'show active' : '' }}" id="pane-store" role="tabpanel" aria-labelledby="tab-store" tabindex="0">
                    <h2 class="settings-pane-title">إعدادات المتجر</h2>
                    <p class="settings-pane-lead">الشحن والتحويل البنكي داخل السعودية.</p>

                    <div class="mb-3">
                        <label class="form-label">{{ $strings::SETTINGS_SHIPPING_FEE }}</label>
                        <input type="number" step="0.01" name="shipping_fee" value="{{ old('shipping_fee', $settings['shipping_fee']) }}" class="form-control @error('shipping_fee') is-invalid @enderror" required>
                        @error('shipping_fee') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-hint">تُستخدم كرسوم احتياطية فقط. سياسة المسافة تُدار من <a href="{{ route('admin.delivery.index') }}">التوصيل</a>.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ $strings::SETTINGS_FREE_SHIPPING }}</label>
                        <input type="number" step="0.01" name="free_shipping_threshold" value="{{ old('free_shipping_threshold', $settings['free_shipping_threshold']) }}" class="form-control @error('free_shipping_threshold') is-invalid @enderror" required>
                        @error('free_shipping_threshold') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-hint">يمكن تعديل هذا الحد أيضاً من صفحة التوصيل. ضع 0 لتعطيله.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">اسم البنك (للتحويل داخل السعودية)</label>
                        <input type="text" name="bank_name" value="{{ old('bank_name', $settings['bank_name']) }}" class="form-control @error('bank_name') is-invalid @enderror">
                        @error('bank_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-4">
                        <label class="form-label">آيبان المتجر</label>
                        <input type="text" name="bank_iban" value="{{ old('bank_iban', $settings['bank_iban']) }}" class="form-control @error('bank_iban') is-invalid @enderror" placeholder="SAxx xxxx xxxx xxxx xxxx xxxx" dir="ltr">
                        @error('bank_iban') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="settings-links">
                        <a href="{{ route('admin.delivery.index') }}" class="settings-link">
                            <i class="bi bi-truck"></i>
                            <span>مناطق ورسوم التوصيل</span>
                        </a>
                        <a href="{{ route('admin.payment-methods.index') }}" class="settings-link">
                            <i class="bi bi-credit-card"></i>
                            <span>طرق الدفع</span>
                        </a>
                    </div>
                </div>

                <div class="tab-pane fade {{ $tab === 'marketing' ? 'show active' : '' }}" id="pane-marketing" role="tabpanel" aria-labelledby="tab-marketing" tabindex="0">
                    <h2 class="settings-pane-title">العروض والتسويق</h2>
                    <p class="settings-pane-lead">رقم تسويقي يظهر على المنتجات التي تختارها، مثل: اشتراه +1K عميل.</p>

                    <div class="mb-3">
                        <label class="form-label">عدد العملاء التسويقي</label>
                        <input type="number" min="0" name="marketing_sold_count" value="{{ old('marketing_sold_count', $settings['marketing_sold_count']) }}" class="form-control @error('marketing_sold_count') is-invalid @enderror" placeholder="مثال: 1000">
                        @error('marketing_sold_count') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-hint">ضع 0 لإخفاء العدد من كل المنتجات.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label d-block">يظهر على</label>
                        <div class="settings-scope">
                            <label class="settings-scope-option">
                                <input type="radio" name="marketing_sold_scope" value="all" @checked($scope !== 'selected') data-sold-scope>
                                <span>كل المنتجات</span>
                            </label>
                            <label class="settings-scope-option">
                                <input type="radio" name="marketing_sold_scope" value="selected" @checked($scope === 'selected') data-sold-scope>
                                <span>منتجات محددة</span>
                            </label>
                        </div>
                        @error('marketing_sold_scope') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="product-picker" data-product-picker data-scope="{{ $scope === 'selected' ? 'selected' : 'all' }}">
                        <div class="product-picker-toolbar">
                            <div class="product-picker-search">
                                <i class="bi bi-search"></i>
                                <input type="search" data-product-picker-search placeholder="ابحث عن منتج..." aria-label="بحث المنتجات">
                            </div>
                            <div class="product-picker-actions">
                                <button type="button" class="btn btn-sm btn-outline-success rounded-pill" data-product-picker-all>تحديد الكل</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill" data-product-picker-none>إلغاء الكل</button>
                            </div>
                        </div>
                        <div class="product-picker-meta">
                            محدد: <strong data-product-picker-count>0</strong> من {{ $products->count() }}
                        </div>
                        <div class="product-picker-list">
                            @forelse ($products as $product)
                                @php
                                    $checked = $selectAllByDefault || $selected->contains((int) $product->id);
                                @endphp
                                <label class="product-picker-item" data-product-picker-item data-name="{{ $product->name }} {{ $product->sku }}">
                                    <input type="checkbox" name="marketing_sold_product_ids[]" value="{{ $product->id }}" @checked($checked)>
                                    <span class="product-picker-copy">
                                        <strong>{{ $product->name }}</strong>
                                        <small>{{ $product->category?->name ?: 'بدون قسم' }}@if($product->sku) · {{ $product->sku }}@endif</small>
                                    </span>
                                    @unless ($product->is_active)
                                        <span class="badge badge-soft">مخفي</span>
                                    @endunless
                                </label>
                            @empty
                                <div class="form-hint mb-0">لا توجد منتجات بعد. أضف منتجات من الكتالوج أولاً.</div>
                            @endforelse
                        </div>
                    </div>

                    <div class="settings-links mt-4">
                        <a href="{{ route('admin.offers.index') }}" class="settings-link">
                            <i class="bi bi-percent"></i>
                            <span>عروض الأسعار</span>
                        </a>
                        <a href="{{ route('admin.coupons.index') }}" class="settings-link">
                            <i class="bi bi-ticket-perforated"></i>
                            <span>الكوبونات</span>
                        </a>
                        <a href="{{ route('admin.banners.index') }}" class="settings-link">
                            <i class="bi bi-image"></i>
                            <span>البنرات</span>
                        </a>
                    </div>
                </div>

                <div class="pt-2">
                    <button class="btn btn-brand">{{ $strings::SAVE }}</button>
                </div>
            </div>
        </form>
    </div>
</x-layouts.admin>
