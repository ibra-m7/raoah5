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
    <div class="form-hint">بهذا الرقم يدخل الموصل إلى تطبيق التوصيل.</div>
</div>

<div class="mb-3">
    <label class="form-label">كلمة المرور</label>
    <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" {{ $courier->exists ? '' : 'required' }} autocomplete="new-password">
    @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
    @if ($courier->exists)
        <div class="form-hint">اتركها فارغة إن لم ترد تغييرها.</div>
    @endif
</div>

<div class="form-check mb-4">
    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="courier_is_active" @checked(old('is_active', $courier->is_active ?? true))>
    <label class="form-check-label" for="courier_is_active">مفعّل في تطبيق الموصل</label>
</div>
