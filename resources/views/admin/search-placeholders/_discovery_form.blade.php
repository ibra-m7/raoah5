@php
    $item = $item ?? null;
    $useOld = $useOld ?? false;
    $hint = $hint ?? 'تظهر في التطبيق.';
@endphp

<div class="mb-3">
    <label class="form-label">النص</label>
    <input type="text" name="phrase" value="{{ $useOld ? old('phrase', $item->phrase ?? '') : ($item->phrase ?? '') }}" class="form-control @error('phrase') is-invalid @enderror" required maxlength="80" placeholder="مثال: قهوة">
    @error('phrase') <div class="invalid-feedback">{{ $message }}</div> @enderror
    <div class="form-hint">{{ $hint }}</div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">{{ $strings::SORT_ORDER }}</label>
        <input type="number" min="0" name="sort_order" value="{{ $useOld ? old('sort_order', $item->sort_order ?? 0) : ($item->sort_order ?? 0) }}" class="form-control">
    </div>
    <div class="col-md-6 mb-3 d-flex align-items-end">
        <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="{{ $checkboxId ?? 'discovery_is_active' }}" @checked($useOld ? old('is_active', $item->is_active ?? true) : ($item->is_active ?? true))>
            <label class="form-check-label" for="{{ $checkboxId ?? 'discovery_is_active' }}">ظاهرة في التطبيق</label>
        </div>
    </div>
</div>
