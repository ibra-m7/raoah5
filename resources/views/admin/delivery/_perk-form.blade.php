@php
    $useOld = old('form') !== 'rule';
    $rewardType = $useOld
        ? old('reward_type', $perk->reward_type?->value ?? 'free')
        : ($perk->reward_type?->value ?? 'free');
@endphp

<div class="mb-3">
    <label class="form-label">اسم العرض</label>
    <input type="text" name="name" value="{{ $useOld ? old('name', $perk->name ?? '') : ($perk->name ?? '') }}" class="form-control {{ $useOld && $errors->has('name') ? 'is-invalid' : '' }}" required>
    @if ($useOld)
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    @endif
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">شرط التفعيل</label>
        <select name="trigger_type" class="form-select {{ $useOld && $errors->has('trigger_type') ? 'is-invalid' : '' }}">
            @foreach (\App\Enums\DeliveryPerkTrigger::cases() as $case)
                <option value="{{ $case->value }}" @selected(($useOld ? old('trigger_type', $perk->trigger_type?->value ?? 'min_orders') : ($perk->trigger_type?->value ?? 'min_orders')) === $case->value)>{{ $case->label() }}</option>
            @endforeach
        </select>
        @if ($useOld)
            @error('trigger_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
        @endif
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">عدد الطلبات</label>
        <input type="number" min="1" name="min_orders" value="{{ $useOld ? old('min_orders', $perk->min_orders ?? 4) : ($perk->min_orders ?? 4) }}" class="form-control {{ $useOld && $errors->has('min_orders') ? 'is-invalid' : '' }}" required>
        @if ($useOld)
            @error('min_orders') <div class="invalid-feedback">{{ $message }}</div> @enderror
        @endif
        <div class="form-hint">حسب شرط التفعيل المختار أعلاه.</div>
    </div>
</div>

<div class="mb-3">
    <label class="form-label">نوع الخصم</label>
    <select name="reward_type" class="form-select {{ $useOld && $errors->has('reward_type') ? 'is-invalid' : '' }}" data-perk-reward>
        @foreach (\App\Enums\DeliveryPerkReward::cases() as $case)
            <option value="{{ $case->value }}" @selected($rewardType === $case->value)>{{ $case->label() }}</option>
        @endforeach
    </select>
    @if ($useOld)
        @error('reward_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
    @endif
</div>

<div class="mb-3" data-perk-value-wrap @if ($rewardType === 'free') hidden @endif>
    <label class="form-label">قيمة الخصم</label>
    <input type="number" step="0.01" min="0" name="reward_value" value="{{ $useOld ? old('reward_value', $perk->reward_value ?? 0) : ($perk->reward_value ?? 0) }}" class="form-control {{ $useOld && $errors->has('reward_value') ? 'is-invalid' : '' }}">
    @if ($useOld)
            @error('reward_value') <div class="invalid-feedback">{{ $message }}</div> @enderror
    @endif
    <div class="form-hint">للنسبة: 0–100. للمبلغ: قيمة بالريال تُخصم من التوصيل.</div>
</div>

<div class="row">
    <div class="col-md-4 mb-3">
        <label class="form-label">{{ $strings::SORT_ORDER }}</label>
        <input type="number" min="0" name="sort_order" value="{{ $useOld ? old('sort_order', $perk->sort_order ?? 0) : ($perk->sort_order ?? 0) }}" class="form-control">
    </div>
    <div class="col-md-8 mb-3 d-flex align-items-end">
        <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="perk_is_active" @checked($useOld ? old('is_active', $perk->is_active ?? true) : ($perk->is_active ?? true))>
            <label class="form-check-label" for="perk_is_active">مفعّل في التطبيق</label>
        </div>
    </div>
</div>
