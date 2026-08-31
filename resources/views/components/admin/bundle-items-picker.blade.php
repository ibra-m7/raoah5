@props([
    'selectedItems' => [],
    'endpoint' => null,
])

@php
    use App\Support\Media;

    $selectedItems = collect($selectedItems);
    $endpoint = $endpoint ?: route('admin.products.lookup');
@endphp

<div
    class="bundle-items-editor"
    data-bundle-items
    data-endpoint="{{ $endpoint }}"
>
    <div class="bundle-items-toolbar">
        <div class="product-lookup-search flex-grow-1">
            <i class="bi bi-search"></i>
            <input
                type="search"
                autocomplete="off"
                placeholder="ابحث بالاسم أو الرمز أو الباركود..."
                data-bundle-items-q
                aria-label="بحث المنتجات لإضافتها للسلة"
            >
        </div>
        <div class="bundle-items-summary" data-bundle-items-summary hidden>
            <span data-bundle-items-count>0</span> منتج ·
            <span data-bundle-items-total>0.00</span> {{ $strings::CURRENCY }}
        </div>
    </div>

    <div class="product-lookup-results" data-bundle-items-results hidden></div>

    <div class="bundle-items-empty" data-bundle-items-empty @if($selectedItems->isNotEmpty()) hidden @endif>
        <i class="bi bi-basket"></i>
        <p>ابحث عن منتج وأضفه للسلة. يمكنك تعديل الكمية لكل منتج.</p>
    </div>

    <div class="bundle-items-list" data-bundle-items-list>
        @foreach ($selectedItems as $index => $row)
            @php
                $product = $row['product'];
                $image = Media::url($product->primaryImage?->url ?? $product->images->first()?->url ?? '');
                $price = number_format((float) $product->price, 2);
            @endphp
            <article
                class="bundle-item-row"
                data-product-id="{{ $product->id }}"
                data-price="{{ (float) $product->price }}"
            >
                <input type="hidden" name="items[{{ $index }}][product_id]" value="{{ $product->id }}">
                <div class="bundle-item-thumb">
                    @if ($image)
                        <img src="{{ $image }}" alt="">
                    @else
                        <i class="bi bi-box-seam"></i>
                    @endif
                </div>
                <div class="bundle-item-meta">
                    <strong>{{ $product->name }}</strong>
                    <small>
                        @if ($product->sku) {{ $product->sku }} · @endif
                        {{ $price }} {{ $strings::CURRENCY }}
                    </small>
                </div>
                <div class="bundle-item-qty" data-bundle-qty>
                    <button type="button" class="bundle-qty-btn" data-bundle-qty-minus aria-label="تقليل">−</button>
                    <input
                        type="number"
                        min="1"
                        max="99"
                        name="items[{{ $index }}][quantity]"
                        value="{{ $row['quantity'] }}"
                        class="bundle-qty-input"
                        data-bundle-qty-input
                        aria-label="الكمية"
                    >
                    <button type="button" class="bundle-qty-btn" data-bundle-qty-plus aria-label="زيادة">+</button>
                </div>
                <button type="button" class="bundle-item-remove" data-bundle-items-remove aria-label="إزالة">
                    <i class="bi bi-trash3"></i>
                </button>
            </article>
        @endforeach
    </div>

    <div class="form-hint mb-0">اكتب حرفاً واحداً على الأقل ثم اختر من النتائج. المنتجات المضافة تظهر أسفل مع إمكانية تعديل الكمية.</div>
</div>
