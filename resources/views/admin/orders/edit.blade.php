<x-layouts.admin :title="$title">
    <x-admin.page-head
        :title="$title"
        subtitle="أضف أو احذف منتجات وحدّث الكميات. الإجمالي يُحسب تلقائياً عند الحفظ."
    />

    <div class="d-flex flex-wrap gap-2 mb-3">
        <a href="{{ route('admin.orders.index') }}" class="btn btn-outline-secondary rounded-pill">
            <i class="bi bi-arrow-right ms-1"></i>
            {{ $strings::BACK }}
        </a>
        <span class="badge badge-soft align-self-center">{{ $order->status?->label() }}</span>
        <span class="badge badge-soft align-self-center">{{ $order->shipping_name ?: $order->user?->name }}</span>
        <span class="badge badge-soft align-self-center">
            {{ $strings::COURIER }}:
            {{ $order->courier?->name ?: ($order->status === \App\Enums\OrderStatus::Preparing ? $strings::WAITING_COURIER : '—') }}
        </span>
    </div>

    @if (! $canEdit)
        <div class="page-card p-4">
            <p class="mb-0 text-muted">لا يمكن تعديل طلب ملغي.</p>
        </div>
    @else
        @error('items')
            <div class="alert alert-danger rounded-4">{{ $message }}</div>
        @enderror
        @error('order')
            <div class="alert alert-danger rounded-4">{{ $message }}</div>
        @enderror

        <form method="POST" action="{{ route('admin.orders.items.update', $order) }}" data-order-editor>
            @csrf
            @method('PUT')

            <div class="page-card p-4 mb-4">
                <h2 class="h5 mb-3">منتجات الطلب</h2>
                <div class="table-responsive">
                    <table class="table" data-order-items>
                        <thead>
                            <tr>
                                <th>المنتج</th>
                                <th>السعر</th>
                                <th style="width: 140px">الكمية</th>
                                <th>الإجمالي</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($order->items as $item)
                                @php
                                    $pid = (int) $item->product_id;
                                    $available = (int) ($item->product?->stock ?? 0) + (int) $item->quantity;
                                @endphp
                                <tr data-product-id="{{ $pid }}" data-price="{{ $item->unit_price }}">
                                    <td>
                                        <strong>{{ $item->product_name }}</strong>
                                        <input type="hidden" name="items[{{ $pid }}][product_id]" value="{{ $pid }}">
                                    </td>
                                    <td data-unit>{{ number_format((float) $item->unit_price, 2) }} {{ $strings::CURRENCY }}</td>
                                    <td>
                                        <input type="number" name="items[{{ $pid }}][quantity]" class="form-control" min="1" max="{{ max(1, $available) }}" value="{{ $item->quantity }}" data-order-qty-input required>
                                    </td>
                                    <td data-line-total class="fw-bold">{{ number_format((float) $item->line_total, 2) }} {{ $strings::CURRENCY }}</td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-danger rounded-pill" data-order-remove-item>{{ $strings::DELETE }}</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="small text-muted mb-0" data-order-empty @if ($order->items->isNotEmpty()) hidden @endif>لا توجد منتجات. أضف منتجاً من القائمة بالأسفل.</p>
            </div>

            <div class="page-card p-4 mb-4">
                <h2 class="h5 mb-3">إضافة منتج</h2>
                <div class="order-catalog" data-order-catalog>
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <input type="search" class="form-control" style="max-width: 260px" placeholder="ابحث عن منتج..." data-order-product-search>
                    </div>
                    <div class="order-cat-row mb-2" data-order-roots>
                        <button type="button" class="btn btn-sm btn-brand rounded-pill" data-order-cat="" data-order-cat-root="1">الكل</button>
                        @foreach ($categories as $category)
                            <button type="button" class="btn btn-sm btn-outline-success rounded-pill" data-order-cat="{{ $category->id }}" data-order-cat-root="1">
                                {{ $category->name }}
                            </button>
                        @endforeach
                    </div>
                    <div class="order-cat-row mb-3" data-order-children hidden>
                        @foreach ($categories as $category)
                            @foreach ($category->children as $child)
                                <button type="button" class="btn btn-sm btn-outline-success rounded-pill" data-order-cat="{{ $child->id }}" data-order-parent="{{ $category->id }}" hidden>
                                    {{ $child->name }}
                                </button>
                            @endforeach
                        @endforeach
                    </div>
                    <div class="order-product-grid" data-order-product-grid>
                        @foreach ($products as $product)
                            @php
                                $onOrder = (int) $order->items->where('product_id', $product->id)->sum('quantity');
                                $available = (int) $product->stock + $onOrder;
                                $image = \App\Support\Media::url($product->primaryImage?->url ?? $product->images->first()?->url);
                                $rootId = $product->category?->parent_id ?: $product->category_id;
                                $categoryLabel = $product->category?->parent
                                    ? $product->category->parent->name.' — '.$product->category->name
                                    : $product->category?->name;
                            @endphp
                            <article
                                class="order-product-card{{ $available < 1 ? ' is-out' : '' }}{{ $onOrder > 0 ? ' is-in-order' : '' }}"
                                data-order-product-card
                                data-id="{{ $product->id }}"
                                data-name="{{ $product->name }}"
                                data-sku="{{ $product->sku }}"
                                data-price="{{ $product->effective_price }}"
                                data-original-price="{{ $product->price }}"
                                data-stock="{{ $available }}"
                                data-image="{{ $image }}"
                                data-description="{{ \Illuminate\Support\Str::limit(strip_tags((string) $product->description), 1200) }}"
                                data-category-name="{{ $categoryLabel }}"
                                data-category-id="{{ $product->category_id }}"
                                data-category-root="{{ $rootId }}"
                            >
                                <button type="button" class="order-product-card-main" data-order-product-open>
                                    <span class="order-product-card-image">
                                        @if ($image)
                                            <img src="{{ $image }}" alt="{{ $product->name }}">
                                        @else
                                            <i class="bi bi-box-seam"></i>
                                        @endif
                                    </span>
                                    <span class="order-product-card-body">
                                        <strong>{{ $product->name }}</strong>
                                        <small>{{ number_format((float) $product->effective_price, 2) }} {{ $strings::CURRENCY }}</small>
                                        @if ($available < 1)
                                            <em>نفد المخزون</em>
                                        @endif
                                    </span>
                                </button>
                                <div class="order-product-stepper">
                                    <button type="button" class="order-stepper-btn" data-order-card-minus aria-label="إنقاص" @disabled($onOrder < 1)>
                                        <i class="bi bi-dash-lg"></i>
                                    </button>
                                    <span data-order-card-qty>{{ $onOrder }}</span>
                                    <button type="button" class="order-stepper-btn is-plus" data-order-card-plus aria-label="إضافة" @disabled($available < 1 || $onOrder >= $available)>
                                        <i class="bi bi-plus-lg"></i>
                                    </button>
                                </div>
                            </article>
                        @endforeach
                    </div>
                    <p class="small text-muted mb-0 mt-3" data-order-catalog-empty hidden>لا توجد منتجات مطابقة.</p>
                </div>
            </div>

            <div class="page-card p-4 mb-4" style="max-width: 640px">
                <h2 class="h5 mb-3">العنوان والتوصيل</h2>
                <div class="mb-3">
                    <div class="fw-bold">{{ $order->address?->label ?: 'عنوان التوصيل' }}</div>
                    <div class="text-muted">{{ $order->address?->displayLine() ?: $order->shipping_details }}</div>
                    @if ($order->delivery_label)
                        <div class="small mt-1">الحساب التلقائي: {{ $order->delivery_label }}</div>
                    @endif
                    @if ($order->shipping_manual)
                        <div class="small text-warning">السعر معدّل يدوياً ولن يُعاد حسابه حتى تضغط «حساب تلقائي».</div>
                    @endif
                </div>
                <label class="form-label">رسوم التوصيل</label>
                <div class="input-group" style="max-width: 280px">
                    <input type="number" step="0.01" min="0" name="shipping_fee" value="{{ old('shipping_fee', $order->shipping_fee) }}" class="form-control @error('shipping_fee') is-invalid @enderror">
                    <span class="input-group-text">{{ $strings::CURRENCY }}</span>
                </div>
                @error('shipping_fee') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                <button type="submit" name="recalc_shipping" value="1" class="btn btn-outline-success rounded-pill btn-sm mt-3">
                    حساب تلقائي من العنوان
                </button>
            </div>

            <div class="page-card p-4 mb-4" style="max-width: 640px">
                <h2 class="h5 mb-3">ملاحظات الطلب</h2>
                <textarea name="notes" rows="3" class="form-control" maxlength="500" placeholder="ملاحظة للمندوب أو للمطبخ">{{ old('notes', $order->notes) }}</textarea>
                <div class="mt-3 small text-muted">
                    المنتجات: <strong>{{ number_format((float) $order->subtotal, 2) }}</strong> {{ $strings::CURRENCY }}
                    · التوصيل: <strong>{{ $order->has_free_shipping ? 'مجاني' : number_format((float) $order->shipping_fee, 2).' '.$strings::CURRENCY }}</strong>
                    @if ((float) $order->discount_amount > 0)
                        · الخصم: <strong>{{ number_format((float) $order->discount_amount, 2) }} {{ $strings::CURRENCY }}</strong>
                    @endif
                    · الإجمالي الحالي: <strong>{{ number_format((float) $order->total, 2) }} {{ $strings::CURRENCY }}</strong>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button class="btn btn-brand">{{ $strings::SAVE }}</button>
                <a href="{{ route('admin.orders.index') }}" class="btn btn-outline-secondary rounded-pill">{{ $strings::CANCEL }}</a>
            </div>
        </form>
    @endif

    <template id="order-item-row">
        <tr data-product-id="" data-price="0">
            <td>
                <strong data-name></strong>
                <input type="hidden" data-id-input>
            </td>
            <td data-unit></td>
            <td>
                <input type="number" class="form-control" min="1" value="1" data-qty-input required>
            </td>
            <td data-line-total class="fw-bold"></td>
            <td>
                <button type="button" class="btn btn-sm btn-outline-danger rounded-pill" data-order-remove-item>{{ $strings::DELETE }}</button>
            </td>
        </tr>
    </template>
    <div class="modal fade" id="orderProductModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content detail-modal">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" data-pick-title>تفاصيل المنتج</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ $strings::CLOSE }}"></button>
                </div>
                <div class="modal-body">
                    <div class="detail-hero" data-pick-hero hidden>
                        <img src="" alt="" data-pick-image>
                    </div>
                    <div class="detail-badges mb-3" data-pick-badges></div>
                    <p class="mb-3" data-pick-description></p>
                    <dl class="detail-grid">
                        <dt>السعر</dt>
                        <dd data-pick-price></dd>
                        <dt>المتوفر</dt>
                        <dd data-pick-stock></dd>
                    </dl>
                    <label class="form-label">الكمية</label>
                    <input type="number" class="form-control" min="1" value="1" style="max-width: 140px" data-pick-qty>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-brand" data-pick-add>
                        <i class="bi bi-plus-lg ms-1"></i>
                        إضافة للطلب
                    </button>
                    <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">{{ $strings::CLOSE }}</button>
                </div>
            </div>
        </div>
    </div>
</x-layouts.admin>
