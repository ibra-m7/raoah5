@php
    $slide = $slide ?? new \App\Models\OnboardingSlide(['is_active' => true, 'sort_order' => 0]);
    $imageSrc = \App\Support\Media::url($slide->image_url);
@endphp

<div class="mb-3">
    <label class="form-label">العنوان</label>
    <input type="text" name="title" value="{{ old('title', $slide->title) }}" class="form-control @error('title') is-invalid @enderror" required>
    @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label class="form-label">العنوان الفرعي</label>
    <input type="text" name="subtitle" value="{{ old('subtitle', $slide->subtitle) }}" class="form-control @error('subtitle') is-invalid @enderror">
    @error('subtitle') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label class="form-label">الوصف</label>
    <textarea name="description" rows="4" class="form-control @error('description') is-invalid @enderror">{{ old('description', $slide->description) }}</textarea>
    @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">صورة الشريحة</label>
        <input type="file" name="image" accept="image/*" class="form-control @error('image') is-invalid @enderror" data-image-preview="#onboarding-preview">
        @error('image') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <img id="onboarding-preview" src="{{ $imageSrc }}" alt="" class="upload-preview mt-2" style="width:100%;max-width:280px;height:160px;object-fit:cover" @if(! $imageSrc) hidden @endif>
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">أو رابط صورة</label>
        <input type="url" name="image_url" value="{{ old('image_url', str_starts_with((string) $slide->image_url, 'http') ? $slide->image_url : '') }}" class="form-control @error('image_url') is-invalid @enderror" placeholder="https://..." dir="ltr">
        @error('image_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="row">
    <div class="col-md-4 mb-3">
        <label class="form-label">{{ $strings::SORT_ORDER }}</label>
        <input type="number" min="0" name="sort_order" value="{{ old('sort_order', $slide->sort_order ?? 0) }}" class="form-control">
    </div>
    <div class="col-md-4 mb-3 d-flex align-items-end">
        <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" @checked(old('is_active', $slide->is_active ?? true))>
            <label class="form-check-label" for="is_active">ظاهرة في التطبيق</label>
        </div>
    </div>
</div>
