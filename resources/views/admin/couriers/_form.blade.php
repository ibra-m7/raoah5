@php
    $courier = $courier ?? new \App\Models\Courier(['is_active' => true]);
@endphp

<div class="mb-3">
    <label class="form-label">الاسم</label>
    <input type="text" name="name" value="{{ old('name', $courier->name) }}" class="form-control @error('name') is-invalid @enderror" required>
    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label class="form-label">رقم الجوال</label>
    <input type="text" name="phone" value="{{ old('phone', $courier->exists ? $courier->phoneDisplay() : $courier->phone) }}" class="form-control @error('phone') is-invalid @enderror" required dir="ltr" placeholder="05xxxxxxxx">
    @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
    <div class="form-hint">بهذا الرقم يدخل الموصل إلى تطبيق التوصيل. استخدم 05xxxxxxxx أو 07xxxxxxxx.</div>
</div>

<div class="mb-3">
    <label class="form-label">كلمة المرور</label>
    <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" {{ $courier->exists ? '' : 'required' }} autocomplete="new-password">
    @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
    @if ($courier->exists)
        <div class="form-hint">اتركها فارغة إن لم ترد تغييرها.</div>
    @endif
</div>

<div class="form-check mb-3">
    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="courier_is_active" @checked(old('is_active', $courier->is_active ?? true))>
    <label class="form-check-label" for="courier_is_active">مفعّل في تطبيق الموصل</label>
</div>

<div class="mb-4">
    <label class="form-label d-block">نوع الطلبات</label>
    <div class="form-check">
        <input class="form-check-input" type="checkbox" name="handles_delivery" value="1" id="courier_handles_delivery" @checked(old('handles_delivery', $courier->handles_delivery ?? true))>
        <label class="form-check-label" for="courier_handles_delivery">توصيل للمنازل</label>
    </div>
    <div class="form-check">
        <input class="form-check-input" type="checkbox" name="handles_pickup" value="1" id="courier_handles_pickup" @checked(old('handles_pickup', $courier->handles_pickup ?? false))>
        <label class="form-check-label" for="courier_handles_pickup">استلام من المركز</label>
    </div>
    @error('handles_delivery') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
</div>
