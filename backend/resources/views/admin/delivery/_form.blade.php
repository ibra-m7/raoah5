@php
    $useOld = old('form') !== 'perk';
    $pricingType = $useOld
        ? old('pricing_type', $rule->pricing_type?->value ?? 'free')
        : ($rule->pricing_type?->value ?? 'free');
@endphp

<div class="mb-3">
    <label class="form-label">اسم الشريحة</label>
    <input type="text" name="name" value="{{ $useOld ? old('name', $rule->name ?? '') : ($rule->name ?? '') }}" class="form-control {{ $useOld && $errors->has('name') ? 'is-invalid' : '' }}" required placeholder="مثال: أقل من 10 كم">
    @if ($useOld)
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    @endif
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">من (كم)</label>
        <input type="number" step="0.1" min="0" name="min_km" value="{{ $useOld ? old('min_km', $rule->min_km ?? 0) : ($rule->min_km ?? 0) }}" class="form-control {{ $useOld && $errors->has('min_km') ? 'is-invalid' : '' }}" required>
        @if ($useOld)
            @error('min_km') <div class="invalid-feedback">{{ $message }}</div> @enderror
        @endif
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">إلى (كم)</label>
        <input type="number" step="0.1" min="0" name="max_km" value="{{ $useOld ? old('max_km', $rule->max_km ?? '') : ($rule->max_km ?? '') }}" class="form-control {{ $useOld && $errors->has('max_km') ? 'is-invalid' : '' }}" placeholder="اتركه فارغاً لـ «فأكثر»">
        @if ($useOld)
            @error('max_km') <div class="invalid-feedback">{{ $message }}</div> @enderror
        @endif
        <div class="form-hint">الحد الأعلى غير مشمول. مثال: إلى 10 يعني أقل من 10 كم.</div>
    </div>
</div>

<div class="mb-3">
    <label class="form-label">نوع التسعير</label>
    <select name="pricing_type" class="form-select {{ $useOld && $errors->has('pricing_type') ? 'is-invalid' : '' }}" data-pricing-type>
        @foreach (\App\Enums\DeliveryPricingType::cases() as $case)
            <option value="{{ $case->value }}" @selected($pricingType === $case->value)>{{ $case->label() }}</option>
        @endforeach
    </select>
    @if ($useOld)
        @error('pricing_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
    @endif
</div>

<div class="mb-3" data-amount-wrap @if ($pricingType === 'free') hidden @endif>
    <label class="form-label">السعر</label>
    <input type="number" step="0.01" min="0" name="amount" value="{{ $useOld ? old('amount', $rule->amount ?? 0) : ($rule->amount ?? 0) }}" class="form-control {{ $useOld && $errors->has('amount') ? 'is-invalid' : '' }}">
    @if ($useOld)
        @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
    @endif
    <div class="form-hint">للسعر الثابت: المبلغ بالريال. لكل كيلومتر: ريال لكل كم.</div>
</div>

<div class="mb-3" data-mode-wrap @if ($pricingType !== 'per_km') hidden @endif>
    <label class="form-label">حساب الكيلومتر</label>
    <select name="per_km_mode" class="form-select">
        @foreach (\App\Enums\DeliveryPerKmMode::cases() as $case)
            <option value="{{ $case->value }}" @selected(($useOld ? old('per_km_mode', $rule->per_km_mode?->value ?? 'entire') : ($rule->per_km_mode?->value ?? 'entire')) === $case->value)>{{ $case->label() }}</option>
        @endforeach
    </select>
</div>

<div class="row">
    <div class="col-md-4 mb-3">
        <label class="form-label">{{ $strings::SORT_ORDER }}</label>
        <input type="number" min="0" name="sort_order" value="{{ $useOld ? old('sort_order', $rule->sort_order ?? 0) : ($rule->sort_order ?? 0) }}" class="form-control">
    </div>
    <div class="col-md-8 mb-3 d-flex align-items-end">
        <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="rule_is_active" @checked($useOld ? old('is_active', $rule->is_active ?? true) : ($rule->is_active ?? true))>
            <label class="form-check-label" for="rule_is_active">مفعّلة في التطبيق</label>
        </div>
    </div>
</div>
