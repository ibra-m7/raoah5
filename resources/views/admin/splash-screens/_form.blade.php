@php
    $splash = $splash ?? new \App\Models\SplashScreen(['is_active' => false, 'media_type' => 'image', 'duration_ms' => 2500, 'sort_order' => 0]);
    $mediaType = old('media_type', $splash->media_type ?: 'image');
    $mediaSrc = \App\Support\Media::url($splash->media_url);
@endphp

<div class="mb-3">
    <label class="form-label">عنوان داخلي (اختياري)</label>
    <input type="text" name="title" value="{{ old('title', $splash->title) }}" class="form-control @error('title') is-invalid @enderror">
    @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label class="form-label">نوع الوسائط</label>
    <select name="media_type" class="form-select @error('media_type') is-invalid @enderror">
        <option value="image" @selected($mediaType === 'image')>صورة</option>
        <option value="video" @selected($mediaType === 'video')>فيديو</option>
    </select>
    @error('media_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">رفع ملف</label>
        <input type="file" name="media_file" accept="image/*,video/mp4,video/webm,video/quicktime" class="form-control @error('media_file') is-invalid @enderror" data-image-preview="#splash-preview">
        @error('media_file') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <div class="form-hint">صورة PNG/JPG أو فيديو MP4. الحد 50 ميجابايت.</div>
        @if ($mediaSrc && $splash->media_type !== 'video')
            <img id="splash-preview" src="{{ $mediaSrc }}" alt="" class="upload-preview mt-2" style="width:100%;max-width:280px;height:160px;object-fit:cover">
        @else
            <img id="splash-preview" src="" alt="" class="upload-preview mt-2" style="width:100%;max-width:280px;height:160px;object-fit:cover" hidden>
        @endif
        @if ($mediaSrc && $splash->media_type === 'video')
            <div class="form-hint mt-2">فيديو حالي محفوظ — ارفع ملفاً جديداً لاستبداله.</div>
        @endif
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">أو رابط مباشر</label>
        <input type="url" name="media_url" value="{{ old('media_url', str_starts_with((string) $splash->media_url, 'http') ? $splash->media_url : '') }}" class="form-control @error('media_url') is-invalid @enderror" placeholder="https://..." dir="ltr">
        @error('media_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="row">
    <div class="col-md-4 mb-3">
        <label class="form-label">مدة العرض (مللي ثانية)</label>
        <input type="number" min="800" max="30000" step="100" name="duration_ms" value="{{ old('duration_ms', $splash->duration_ms ?? 2500) }}" class="form-control @error('duration_ms') is-invalid @enderror">
        @error('duration_ms') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <div class="form-hint">مثال: 2500 = ثانيتان ونصف.</div>
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">{{ $strings::SORT_ORDER }}</label>
        <input type="number" min="0" name="sort_order" value="{{ old('sort_order', $splash->sort_order ?? 0) }}" class="form-control">
    </div>
    <div class="col-md-4 mb-3 d-flex align-items-end">
        <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" @checked(old('is_active', $splash->is_active ?? false))>
            <label class="form-check-label" for="is_active">مفعّلة في التطبيق</label>
        </div>
    </div>
</div>

<x-admin.help-note>
    شاشة واحدة فقط تكون مفعّلة في نفس الوقت. عند التفعيل تُلغى تفعيل الباقي تلقائياً. إن ألغيت التفعيل يعود التطبيق للسبلاش الافتراضي.
</x-admin.help-note>
