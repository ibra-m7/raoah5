@php
    $placeholder = $placeholder ?? new \App\Models\SearchPlaceholder(['is_active' => true, 'sort_order' => 0]);
    $useOld = $useOld ?? false;
@endphp

<div class="mb-3">
    <label class="form-label">العبارة</label>
    <input type="text" name="phrase" value="{{ $useOld ? old('phrase', $placeholder->phrase) : ($placeholder->phrase ?? '') }}" class="form-control @error('phrase') is-invalid @enderror" required maxlength="160" placeholder="مثال: ابحث عن عروض اليوم...">
    @error('phrase') <div class="invalid-feedback">{{ $message }}</div> @enderror
    <div class="form-hint">تظهر متحركة داخل حقل البحث في التطبيق.</div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">{{ $strings::SORT_ORDER }}</label>
        <input type="number" min="0" name="sort_order" value="{{ $useOld ? old('sort_order', $placeholder->sort_order ?? 0) : ($placeholder->sort_order ?? 0) }}" class="form-control">
    </div>
    <div class="col-md-6 mb-3 d-flex align-items-end">
        <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="phrase_is_active" @checked($useOld ? old('is_active', $placeholder->is_active ?? true) : ($placeholder->is_active ?? true))>
            <label class="form-check-label" for="phrase_is_active">ظاهرة في التطبيق</label>
        </div>
    </div>
</div>
