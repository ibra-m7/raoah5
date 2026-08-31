@php
    use App\Support\Media;

    $bundle = $bundle ?? new \App\Models\ProductBundle([
        'is_active' => true,
        'sort_order' => 0,
        'discount_percent' => 0,
        'bundle_price' => 0,
    ]);
    $selectedItems = $selectedItems ?? [];
    $coverSrc = Media::url($bundle->image_url);
@endphp

<div
    class="product-ai-copy"
    data-bundle-ai-copy
    data-endpoint="{{ route('admin.bundles.generate-copy') }}"
    data-section-title="{{ $section->title ?? '' }}"
>
    <div class="mb-3">
        <label class="form-label">اسم السلة</label>
        <div class="product-ai-name">
            <input type="text" name="name" value="{{ old('name', $bundle->name) }}" class="form-control @error('name') is-invalid @enderror" required>
            <button
                type="button"
                class="btn btn-success rounded-pill"
                data-ai-generate
                title="توليد الملخص والوصف ونسبة الخصم المقترحة"
            >
                <i class="bi bi-stars ms-1"></i>
                {{ $strings::GENERATE_PRODUCT_COPY }}
            </button>
        </div>
        @error('name') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
        <div class="form-hint">اكتب اسم السلة (ويُفضَّل إضافة المنتجات أولاً) ثم اضغط «توليد المحتوى» لملء الملخص والوصف.</div>
        <div class="product-ai-copy-status text-muted small mt-1" data-ai-status hidden></div>
    </div>

    <div class="mb-3">
        <label class="form-label">الملخص</label>
        <input type="text" name="summary" value="{{ old('summary', $bundle->summary) }}" class="form-control @error('summary') is-invalid @enderror" maxlength="500" data-ai-field="summary">
        @error('summary') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <div class="form-hint">يظهر كعنوان فرعي على بطاقة السلة في التطبيق.</div>
    </div>

    <div class="mb-3">
        <label class="form-label">الوصف (اختياري)</label>
        <textarea name="description" rows="3" class="form-control @error('description') is-invalid @enderror" data-ai-field="description">{{ old('description', $bundle->description) }}</textarea>
        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="mb-3">
        <label class="form-label">صورة الغلاف (اختياري)</label>
        <input type="file" name="image" accept="image/*" class="form-control @error('image') is-invalid @enderror" data-image-preview="#bundle-cover-preview">
        @error('image') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <img id="bundle-cover-preview" src="{{ $coverSrc }}" alt="" class="upload-preview mt-2" style="width: 100%; max-width: 280px; height: 140px" @if(! $coverSrc) hidden @endif>
        <input type="url" name="image_url" value="{{ old('image_url', str_starts_with((string) $bundle->image_url, 'http') ? $bundle->image_url : '') }}" class="form-control mt-2 @error('image_url') is-invalid @enderror" placeholder="أو رابط صورة خارجي">
        @error('image_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <div class="form-hint">إن تُرك فارغاً، يُكوَّن الغلاف تلقائياً من صور المنتجات في التطبيق.</div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">نسبة الخصم %</label>
            <input type="number" step="0.01" min="0" max="99.99" name="discount_percent" id="bundle_discount_percent" value="{{ old('discount_percent', $bundle->discount_percent) }}" class="form-control @error('discount_percent') is-invalid @enderror" data-ai-field="discount_percent">
            @error('discount_percent') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">سعر السلة بعد الخصم</label>
            <input type="number" step="0.01" min="0" name="bundle_price" id="bundle_price" value="{{ old('bundle_price', $bundle->bundle_price) }}" class="form-control @error('bundle_price') is-invalid @enderror">
            @error('bundle_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
            <div class="form-hint">يُحدَّث تلقائياً من نسبة الخصم عند الحفظ إن لزم.</div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">{{ $strings::SORT_ORDER }}</label>
            <input type="number" min="0" name="sort_order" value="{{ old('sort_order', $bundle->sort_order ?? 0) }}" class="form-control">
        </div>
        <div class="col-md-6 mb-3 d-flex align-items-end">
            <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="bundle_is_active" @checked(old('is_active', $bundle->is_active ?? true))>
                <label class="form-check-label" for="bundle_is_active">{{ $strings::ACTIVE }}</label>
            </div>
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label">منتجات السلة</label>
        <x-admin.bundle-items-picker :selected-items="$selectedItems" />
        @error('items') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
        @error('items.*.product_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
    </div>
</div>
