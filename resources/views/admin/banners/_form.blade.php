@php
    use App\Enums\BannerLinkType;
    use App\Support\Media;

    $banner = $banner ?? new \App\Models\Banner(['is_active' => true, 'sort_order' => 0, 'link_type' => BannerLinkType::None]);
    $linkType = old('link_type', $banner->link_type?->value ?? BannerLinkType::None->value);
    $imageSrc = Media::url($banner->image_url);
    $productId = old('link_product_id', $banner->link_type === BannerLinkType::Product ? $banner->link_id : null);
    $categoryId = old('link_category_id', $banner->link_type === BannerLinkType::Category ? $banner->link_id : null);
@endphp

<div class="mb-3">
    <label class="form-label">العنوان</label>
    <input type="text" name="title" value="{{ old('title', $banner->title) }}" class="form-control @error('title') is-invalid @enderror" required>
    @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label class="form-label">العنوان الفرعي</label>
    <input type="text" name="subtitle" value="{{ old('subtitle', $banner->subtitle) }}" class="form-control @error('subtitle') is-invalid @enderror">
    @error('subtitle') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">صورة الإعلان</label>
        <input type="file" name="image" accept="image/*" class="form-control @error('image') is-invalid @enderror" data-image-preview="#banner-preview">
        @error('image') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <img id="banner-preview" src="{{ $imageSrc }}" alt="" class="upload-preview mt-2" style="width: 100%; max-width: 360px; height: 140px" @if(! $imageSrc) hidden @endif>
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">أو رابط صورة</label>
        <input type="url" name="image_url" value="{{ old('image_url', str_starts_with((string) $banner->image_url, 'http') ? $banner->image_url : '') }}" class="form-control @error('image_url') is-invalid @enderror" placeholder="https://...">
        @error('image_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <div class="form-hint">يفضّل صورة عريضة تظهر في شريط الرئيسية.</div>
    </div>
</div>

<div class="mb-3">
    <label class="form-label">عند الضغط يفتح</label>
    <select name="link_type" class="form-select @error('link_type') is-invalid @enderror" data-link-type>
        <option value="{{ BannerLinkType::None->value }}" @selected($linkType === BannerLinkType::None->value)>بدون رابط</option>
        <option value="{{ BannerLinkType::Product->value }}" @selected($linkType === BannerLinkType::Product->value)>منتج</option>
        <option value="{{ BannerLinkType::Category->value }}" @selected($linkType === BannerLinkType::Category->value)>قسم</option>
        <option value="{{ BannerLinkType::Url->value }}" @selected($linkType === BannerLinkType::Url->value)>رابط خارجي</option>
    </select>
    @error('link_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div data-link-panel="product" @if($linkType !== BannerLinkType::Product->value) hidden @endif>
    <div class="mb-3">
        <label class="form-label">المنتج</label>
        <select name="link_product_id" class="form-select @error('link_id') is-invalid @enderror">
            <option value="">اختر منتجاً</option>
            @foreach ($products as $product)
                <option value="{{ $product->id }}" @selected((string) $productId === (string) $product->id)>{{ $product->name }}</option>
            @endforeach
        </select>
        @error('link_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div data-link-panel="category" @if($linkType !== BannerLinkType::Category->value) hidden @endif>
    <div class="mb-3">
        <label class="form-label">القسم</label>
        <select name="link_category_id" class="form-select @error('link_id') is-invalid @enderror">
            <option value="">اختر قسماً</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected((string) $categoryId === (string) $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
    </div>
</div>

<div data-link-panel="url" @if($linkType !== BannerLinkType::Url->value) hidden @endif>
    <div class="mb-3">
        <label class="form-label">الرابط</label>
        <input type="url" name="link_url" value="{{ old('link_url', $banner->link_url) }}" class="form-control @error('link_url') is-invalid @enderror" placeholder="https://...">
        @error('link_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="row">
    <div class="col-md-4 mb-3">
        <label class="form-label">يبدأ من</label>
        <input type="datetime-local" name="starts_at" value="{{ old('starts_at', $banner->starts_at?->format('Y-m-d\TH:i')) }}" class="form-control @error('starts_at') is-invalid @enderror">
        @error('starts_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">ينتهي في</label>
        <input type="datetime-local" name="ends_at" value="{{ old('ends_at', $banner->ends_at?->format('Y-m-d\TH:i')) }}" class="form-control @error('ends_at') is-invalid @enderror">
        @error('ends_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-2 mb-3">
        <label class="form-label">{{ $strings::SORT_ORDER }}</label>
        <input type="number" min="0" name="sort_order" value="{{ old('sort_order', $banner->sort_order ?? 0) }}" class="form-control">
    </div>
    <div class="col-md-2 mb-3 d-flex align-items-end">
        <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" @checked(old('is_active', $banner->is_active ?? true))>
            <label class="form-check-label" for="is_active">{{ $strings::ACTIVE }}</label>
        </div>
    </div>
</div>
