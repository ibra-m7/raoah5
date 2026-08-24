@php
    $method = $method ?? new \App\Models\StorePaymentMethod(['is_active' => true, 'sort_order' => 0, 'icon' => 'bi-credit-card']);
    $icons = $icons ?? [];
@endphp

<div class="mb-3">
    <label class="form-label">الاسم الظاهر للعميل</label>
    <input type="text" name="label" value="{{ old('label', $method->label) }}" class="form-control @error('label') is-invalid @enderror" required>
    @error('label') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label class="form-label">المعرّف</label>
    <input type="text" name="slug" value="{{ old('slug', $method->slug) }}" class="form-control @error('slug') is-invalid @enderror" required dir="ltr">
    @error('slug') <div class="invalid-feedback">{{ $message }}</div> @enderror
    <div class="form-hint">أحرف إنجليزية صغيرة وأرقام وشرطة سفلية.</div>
</div>

<div class="mb-3">
    <label class="form-label">وصف قصير</label>
    <input type="text" name="hint" value="{{ old('hint', $method->hint) }}" class="form-control @error('hint') is-invalid @enderror">
    @error('hint') <div class="invalid-feedback">{{ $message }}</div> @enderror
    <div class="form-hint">يظهر تحت الاسم عند الدفع.</div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">الأيقونة</label>
        <select name="icon" class="form-select @error('icon') is-invalid @enderror">
            @foreach ($icons as $icon)
                <option value="{{ $icon['value'] }}" @selected(old('icon', $method->icon) === $icon['value'])>
                    {{ $icon['label'] }}
                </option>
            @endforeach
        </select>
        @error('icon') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">{{ $strings::SORT_ORDER }}</label>
        <input type="number" min="0" name="sort_order" value="{{ old('sort_order', $method->sort_order ?? 0) }}" class="form-control">
    </div>
    <div class="col-md-3 mb-3 d-flex align-items-end">
        <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" @checked(old('is_active', $method->is_active ?? true))>
            <label class="form-check-label" for="is_active">ظاهرة في التطبيق</label>
        </div>
    </div>
</div>
