@php
    $product = $product ?? new \App\Models\Product(['is_featured' => true]);
    $locked = $product->exists;
@endphp

<div class="mb-3">
    <label class="form-label">المنتج</label>
    @if ($locked)
        <input type="hidden" name="product_id" value="{{ $product->id }}">
        <div class="locked-product">
            <strong>{{ $product->name }}</strong>
            <span class="text-muted">السعر الأصلي {{ number_format((float) $product->price, 2) }} {{ $strings::CURRENCY }}</span>
        </div>
    @else
        <select name="product_id" class="form-select @error('product_id') is-invalid @enderror" required data-price-source>
            <option value="">اختر منتجاً</option>
            @foreach ($products as $option)
                <option value="{{ $option->id }}" data-price="{{ $option->price }}" data-discount="{{ $option->discount_price }}" @selected(old('product_id', $product->id) == $option->id)>
                    {{ $option->name }} — {{ number_format((float) $option->price, 2) }} {{ $strings::CURRENCY }}
                </option>
            @endforeach
        </select>
        @error('product_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    @endif
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">سعر العرض</label>
        <input type="number" step="0.01" min="0.01" name="discount_price" value="{{ old('discount_price', $product->discount_price) }}" class="form-control @error('discount_price') is-invalid @enderror" required>
        @error('discount_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <div class="form-hint">يجب أن يكون أقل من السعر الأصلي حتى يظهر في العروض.</div>
    </div>
    <div class="col-md-6 mb-3 d-flex align-items-end">
        <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" name="is_featured" value="1" id="is_featured" @checked(old('is_featured', $product->is_featured ?? true))>
            <label class="form-check-label" for="is_featured">إبراز المنتج في الرئيسية</label>
        </div>
    </div>
</div>
