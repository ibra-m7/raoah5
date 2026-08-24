@php
    $section = $section ?? new \App\Models\HomeSection(['is_active' => true, 'sort_order' => 0]);
    $selected = old('product_ids', $selectedIds ?? []);
    $style = old('display_style', $section->exists ? $section->displayStyle() : 'general');
    $styles = \App\Models\HomeSection::displayStyles();
@endphp

<div class="row">
    <div class="col-md-8 mb-3">
        <label class="form-label">اسم القسم</label>
        <input type="text" name="title" value="{{ old('title', $section->title) }}" class="form-control @error('title') is-invalid @enderror" required>
        @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">شكل العرض</label>
        <select name="display_style" class="form-select @error('display_style') is-invalid @enderror @error('key') is-invalid @enderror">
            @foreach ($styles as $value => $meta)
                <option value="{{ $value }}" @selected($style === $value)>{{ $meta['label'] }}</option>
            @endforeach
        </select>
        @error('display_style') <div class="invalid-feedback">{{ $message }}</div> @enderror
        @error('key') <div class="invalid-feedback">هذا الشكل مستخدم في قسم آخر. اختر شكلاً مختلفاً أو قسماً عادياً.</div> @enderror
        <div class="form-hint">يغيّر مظهر الشريط فقط.</div>
    </div>
</div>

<div class="mb-3">
    <label class="form-label">العنوان الفرعي</label>
    <input type="text" name="subtitle" value="{{ old('subtitle', $section->subtitle) }}" class="form-control">
    <div class="form-hint">يظهر تحت الاسم في التطبيق.</div>
</div>

<div class="row">
    <div class="col-md-4 mb-3">
        <label class="form-label">{{ $strings::SORT_ORDER }}</label>
        <input type="number" min="0" name="sort_order" value="{{ old('sort_order', $section->sort_order ?? 0) }}" class="form-control">
    </div>
    <div class="col-md-4 mb-3 d-flex align-items-end">
        <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" @checked(old('is_active', $section->is_active ?? true))>
            <label class="form-check-label" for="is_active">{{ $strings::ACTIVE }}</label>
        </div>
    </div>
</div>

<div class="mb-3">
    <label class="form-label">المنتجات</label>
    <input type="search" class="form-control mb-2" placeholder="ابحث داخل المنتجات..." data-picker-search="#home-products">
    <div class="picker-grid" id="home-products">
        @foreach ($products as $product)
            <label class="picker-item" data-picker-text="{{ $product->name }}">
                <input type="checkbox" name="product_ids[]" value="{{ $product->id }}" @checked(in_array($product->id, $selected, false) || in_array((string) $product->id, $selected, true))>
                <span>
                    <strong>{{ $product->name }}</strong>
                    <small class="d-block text-muted">{{ number_format((float) $product->price, 2) }} {{ $strings::CURRENCY }}</small>
                </span>
            </label>
        @endforeach
    </div>
    @error('product_ids') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
</div>
