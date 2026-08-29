@props([
    'name',
    'selected' => [],
    'multiple' => true,
    'except' => null,
    'allowEmpty' => false,
    'emptyLabel' => 'بدون اختيار',
    'placeholder' => 'ابحث بالاسم أو الرمز أو الباركود...',
    'hint' => 'اكتب حرفين ثم اختر من النتائج. لا حاجة لتحميل كل المنتجات.',
    'giftOnly' => false,
    'excludeGifts' => false,
    'embedded' => false,
])

@php
    $selected = collect($selected)->filter();
    $except = $except ? (int) $except : '';
@endphp

<div
    class="product-lookup{{ $embedded ? ' product-lookup--embedded' : '' }}"
    data-product-lookup
    data-endpoint="{{ route('admin.products.lookup') }}"
    data-name="{{ $name }}"
    data-multiple="{{ $multiple ? '1' : '0' }}"
    data-except="{{ $except }}"
    data-allow-empty="{{ $allowEmpty ? '1' : '0' }}"
    data-empty-label="{{ $emptyLabel }}"
    data-gift-only="{{ $giftOnly ? '1' : '0' }}"
    data-exclude-gifts="{{ $excludeGifts ? '1' : '0' }}"
    {{ $attributes }}
>
    <div class="product-lookup-search">
        <i class="bi bi-search"></i>
        <input type="search" autocomplete="off" placeholder="{{ $placeholder }}" data-product-lookup-q aria-label="بحث المنتجات">
    </div>
    <div class="product-lookup-results" data-product-lookup-results hidden></div>
    <div class="product-lookup-selected" data-product-lookup-selected>
        @forelse ($selected as $product)
            <div class="product-lookup-chip" data-id="{{ $product->id }}">
                <input type="hidden" name="{{ $name }}" value="{{ $product->id }}">
                <span>
                    <strong>{{ $product->name }}</strong>
                    <small>
                        @if ($product->sku) {{ $product->sku }} · @endif
                        {{ number_format((float) $product->price, 2) }} {{ $strings::CURRENCY }}
                    </small>
                </span>
                <button type="button" class="product-lookup-remove" data-product-lookup-remove aria-label="إزالة">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
        @empty
            @if ($allowEmpty && ! $multiple)
                <input type="hidden" name="{{ $name }}" value="">
                <div class="product-lookup-empty-state">{{ $emptyLabel }}</div>
            @endif
        @endforelse
    </div>
    @if ($hint)
        <div class="form-hint mb-0">{{ $hint }}</div>
    @endif
</div>
