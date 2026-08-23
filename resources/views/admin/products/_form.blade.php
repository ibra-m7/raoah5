@php
    $product = $product ?? new \App\Models\Product(['is_active' => true, 'stock' => 0, 'sort_order' => 0]);
    $imageUrl = old('image_url', $product->primaryImage?->url);
    $imageSrc = \App\Support\Media::url($product->primaryImage?->url);
    $benefits = old('benefits', implode("\n", $product->benefits ?? []));
    $keywords = old('keywords', implode(', ', $product->keywords ?? []));
@endphp

<div class="row">
    <div class="col-md-8 mb-3">
        <label class="form-label">اسم المنتج</label>
        <input type="text" name="name" value="{{ old('name', $product->name) }}" class="form-control @error('name') is-invalid @enderror" required>
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">رمز المنتج</label>
        <input type="text" name="sku" value="{{ old('sku', $product->sku) }}" class="form-control @error('sku') is-invalid @enderror" placeholder="يُولَّد تلقائياً">
        @error('sku') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="mb-3">
    <label class="form-label">التصنيف</label>
    <select name="category_id" class="form-select @error('category_id') is-invalid @enderror" required>
        <option value="">اختر التصنيف</option>
        @foreach ($categories as $category)
            <option value="{{ $category->id }}" @selected(old('category_id', $product->category_id) == $category->id)>
                {{ $category->path_label ?? $category->name }}
            </option>
        @endforeach
    </select>
    @error('category_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label class="form-label">الوصف</label>
    <textarea name="description" rows="3" class="form-control @error('description') is-invalid @enderror">{{ old('description', $product->description) }}</textarea>
    @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="row">
    <div class="col-md-4 mb-3">
        <label class="form-label">السعر</label>
        <input type="number" step="0.01" min="0" name="price" value="{{ old('price', $product->price) }}" class="form-control @error('price') is-invalid @enderror" required>
        @error('price') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">سعر العرض</label>
        <input type="number" step="0.01" min="0" name="discount_price" value="{{ old('discount_price', $product->discount_price) }}" class="form-control @error('discount_price') is-invalid @enderror">
        @error('discount_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <div class="form-hint">إذا كان أقل من السعر الأصلي يظهر المنتج في العروض داخل التطبيق.</div>
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">المخزون</label>
        <input type="number" min="0" name="stock" value="{{ old('stock', $product->stock ?? 0) }}" class="form-control @error('stock') is-invalid @enderror" required>
        @error('stock') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="row">
    <div class="col-md-4 mb-3">
        <label class="form-label">عدد الحبات في العبوة الواحدة</label>
        <input type="number" min="1" name="piece_count" value="{{ old('piece_count', $product->piece_count) }}" class="form-control @error('piece_count') is-invalid @enderror" placeholder="مثال: 4">
        @error('piece_count') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <div class="form-hint">السعر والمخزون للعبوة كاملة. مثال: تونة تُباع كعبوة واحدة فيها 4 حبات — يظهر في التطبيق «4 حبات».</div>
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">الوزن</label>
        <input type="text" name="weight_label" value="{{ old('weight_label', $product->weight_label) }}" class="form-control @error('weight_label') is-invalid @enderror" placeholder="مثال: 10 كجم">
        @error('weight_label') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <div class="form-hint">اكتب الوزن مع الوحدة كما سيراه العميل.</div>
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">وصف الكمية الظاهر</label>
        <input type="text" name="quantity_label" value="{{ old('quantity_label', $product->quantity_label) }}" class="form-control @error('quantity_label') is-invalid @enderror" placeholder="مثال: 5 × 80 جم">
        @error('quantity_label') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <div class="form-hint">اختياري. نص مخصص للعرض مثل: 4 حبات × 80 جم. إن تركته فارغاً يُولَّد من عدد الحبات والوزن.</div>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">صورة المنتج</label>
        <input type="file" name="image" accept="image/*" class="form-control @error('image') is-invalid @enderror" data-image-preview="#product-preview">
        @error('image') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <img id="product-preview" src="{{ $imageSrc }}" alt="" class="upload-preview mt-2" @if(! $imageSrc) hidden @endif>
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">أو رابط صورة</label>
        <input type="url" name="image_url" value="{{ $imageUrl }}" class="form-control @error('image_url') is-invalid @enderror" placeholder="https://...">
        @error('image_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="mb-3">
    <label class="form-label">صور إضافية (اختياري)</label>
    <input type="file" name="gallery[]" accept="image/*" multiple class="form-control @error('gallery') is-invalid @enderror">
    @error('gallery') <div class="invalid-feedback">{{ $message }}</div> @enderror
    @error('gallery.*') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
    <textarea name="gallery_urls" rows="2" class="form-control mt-2 @error('gallery_urls') is-invalid @enderror" placeholder="أو روابط صور إضافية، سطر لكل رابط">{{ old('gallery_urls') }}</textarea>
    @error('gallery_urls') <div class="invalid-feedback">{{ $message }}</div> @enderror
    <div class="form-hint">يمكن إضافة أكثر من صورة للمنتج. تظهر في صفحة التفاصيل داخل التطبيق.</div>
    @if ($product->relationLoaded('images') && $product->images->where('is_primary', false)->isNotEmpty())
        <div class="d-flex flex-wrap gap-3 mt-3">
            @foreach ($product->images->where('is_primary', false) as $extra)
                @php $extraSrc = \App\Support\Media::url($extra->url); @endphp
                <label class="text-center" style="width: 96px">
                    <img src="{{ $extraSrc }}" alt="" class="upload-preview mb-1" style="width: 96px; height: 96px; object-fit: cover">
                    <span class="form-check justify-content-center">
                        <input class="form-check-input" type="checkbox" name="delete_image_ids[]" value="{{ $extra->id }}">
                        <span class="form-check-label small">حذف</span>
                    </span>
                </label>
            @endforeach
        </div>
    @endif
</div>

<div class="mb-3">
    <label class="form-label">الفوائد (سطر لكل فائدة)</label>
    <textarea name="benefits" rows="4" class="form-control @error('benefits') is-invalid @enderror">{{ $benefits }}</textarea>
    @error('benefits') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label class="form-label">كلمات البحث (مفصولة بفاصلة)</label>
    <input type="text" name="keywords" value="{{ $keywords }}" class="form-control @error('keywords') is-invalid @enderror">
    @error('keywords') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label class="form-label">طريقة الاستخدام</label>
    <textarea name="usage_instructions" rows="3" class="form-control @error('usage_instructions') is-invalid @enderror">{{ old('usage_instructions', $product->usage_instructions) }}</textarea>
    @error('usage_instructions') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="row">
    <div class="col-md-4 mb-3">
        <label class="form-label">{{ $strings::SORT_ORDER }}</label>
        <input type="number" min="0" name="sort_order" value="{{ old('sort_order', $product->sort_order ?? 0) }}" class="form-control">
    </div>
    <div class="col-md-4 mb-3 d-flex align-items-end">
        <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" @checked(old('is_active', $product->is_active ?? true))>
            <label class="form-check-label" for="is_active">{{ $strings::ACTIVE }}</label>
        </div>
    </div>
    <div class="col-md-4 mb-3 d-flex align-items-end">
        <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" name="is_featured" value="1" id="is_featured" @checked(old('is_featured', $product->is_featured))>
            <label class="form-check-label" for="is_featured">مميز</label>
        </div>
    </div>
</div>
