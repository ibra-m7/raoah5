@php
    use App\Enums\DynamicPagePlacement;
    use App\Support\Media;

    $page = $page ?? new \App\Models\DynamicPage([
        'is_active' => true,
        'sort_order' => 0,
        'placement' => DynamicPagePlacement::None,
    ]);
    $placement = old('placement', $page->placement?->value ?? DynamicPagePlacement::None->value);
    $selected = old('product_ids', $selectedIds ?? []);
    $bannerSrc = Media::url($page->banner_image_url);
    $appbarSrc = Media::url($page->appbar_image_url);
@endphp

<div class="mb-3">
    <label class="form-label">العنوان</label>
    <input type="text" name="title" value="{{ old('title', $page->title) }}" class="form-control @error('title') is-invalid @enderror" required>
    @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">صورة البنر في الرئيسية</label>
        <input type="file" name="banner_image" accept="image/*" class="form-control @error('banner_image') is-invalid @enderror" data-image-preview="#page-banner-preview">
        @error('banner_image') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <img id="page-banner-preview" src="{{ $bannerSrc }}" alt="" class="upload-preview mt-2" style="width: 100%; max-width: 360px; height: 140px" @if(! $bannerSrc) hidden @endif>
        <input type="url" name="banner_image_url" value="{{ old('banner_image_url', str_starts_with((string) $page->banner_image_url, 'http') ? $page->banner_image_url : '') }}" class="form-control mt-2 @error('banner_image_url') is-invalid @enderror">
        @error('banner_image_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">صورة رأس الصفحة الداخلية</label>
        <input type="file" name="appbar_image" accept="image/*" class="form-control @error('appbar_image') is-invalid @enderror" data-image-preview="#page-appbar-preview">
        @error('appbar_image') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <img id="page-appbar-preview" src="{{ $appbarSrc }}" alt="" class="upload-preview mt-2" style="width: 100%; max-width: 360px; height: 140px" @if(! $appbarSrc) hidden @endif>
        <input type="url" name="appbar_image_url" value="{{ old('appbar_image_url', str_starts_with((string) $page->appbar_image_url, 'http') ? $page->appbar_image_url : '') }}" class="form-control mt-2 @error('appbar_image_url') is-invalid @enderror">
        @error('appbar_image_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="mb-3">
    <label class="form-label">مكان الظهور في التطبيق</label>
    <select name="placement" class="form-select @error('placement') is-invalid @enderror">
        @foreach (DynamicPagePlacement::cases() as $place)
            <option value="{{ $place->value }}" @selected($placement === $place->value)>{{ $place->label() }}</option>
        @endforeach
    </select>
    @error('placement') <div class="invalid-feedback">{{ $message }}</div> @enderror
    <div class="form-hint">يحدد أين يظهر عنوان الصفحة في التطبيق.</div>
</div>

<div class="row">
    <div class="col-md-4 mb-3">
        <label class="form-label">{{ $strings::SORT_ORDER }}</label>
        <input type="number" min="0" name="sort_order" value="{{ old('sort_order', $page->sort_order ?? 0) }}" class="form-control">
    </div>
    <div class="col-md-4 mb-3 d-flex align-items-end">
        <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" @checked(old('is_active', $page->is_active ?? true))>
            <label class="form-check-label" for="is_active">{{ $strings::ACTIVE }}</label>
        </div>
    </div>
</div>

<div class="mb-3">
    <label class="form-label">منتجات هذه الصفحة</label>
    <input type="search" class="form-control mb-2" placeholder="ابحث داخل المنتجات..." data-picker-search="#dynamic-page-products">
    <div class="picker-grid" id="dynamic-page-products">
        @foreach ($products as $product)
            <label class="picker-item" data-picker-text="{{ $product->name }}">
                <input type="checkbox" name="product_ids[]" value="{{ $product->id }}" @checked(in_array($product->id, $selected, false) || in_array((string) $product->id, $selected, true))>
                <span>
                    <strong>{{ $product->name }}</strong>
                    <small class="d-block text-muted">{{ number_format((float) $product->price, 2) }} {{ $strings::CURRENCY }}</small>
                </span>
            </label>
        @endforeach
    </div>
    @error('product_ids') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
</div>
