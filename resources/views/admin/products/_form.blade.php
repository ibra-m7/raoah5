@php
    $product = $product ?? new \App\Models\Product(['is_active' => true, 'stock' => 0, 'sort_order' => 0]);
    $imageUrl = old('image_url', $product->primaryImage?->url);
    $imageSrc = \App\Support\Media::url($product->primaryImage?->url);
    $benefits = old('benefits', implode("\n", $product->benefits ?? []));
    $keywords = old('keywords', implode(', ', $product->keywords ?? []));
@endphp

<div class="product-ai-copy product-form-shell" data-product-ai-copy data-ai-auto="0" data-endpoint="{{ route('admin.products.generate-copy') }}">
    <section class="product-form-section">
        <div class="product-form-section__header">
            <div>
                <span class="product-form-section__badge">الأساسية</span>
                <h3>معلومات المنتج</h3>
            </div>
        </div>
        <div class="row g-3">
            <div class="col-md-8">
                <label class="form-label">اسم المنتج</label>
                <div class="product-ai-name">
                    <input type="text" name="name" value="{{ old('name', $product->name) }}" class="form-control @error('name') is-invalid @enderror" required>
                    <button type="button" class="btn btn-success rounded-pill" data-ai-generate title="توليد الوصف والتصنيف والفوائد وكلمات البحث وطريقة الاستخدام">
                        <i class="bi bi-stars ms-1"></i>
                        {{ $strings::GENERATE_PRODUCT_COPY }}
                    </button>
                </div>
                @error('name') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                <div class="form-hint">اكتب الاسم ثم اضغط «توليد المحتوى» لملء التصنيف والوصف والفوائد وكلمات البحث وطريقة الاستخدام.</div>
                <div class="product-ai-copy-status text-muted small mt-1" data-ai-status hidden></div>
            </div>
            <div class="col-md-4">
                <label class="form-label">رمز المنتج</label>
                <input type="text" name="sku" value="{{ old('sku', $product->sku) }}" class="form-control @error('sku') is-invalid @enderror">
                @error('sku') <div class="invalid-feedback">{{ $message }}</div> @enderror
                <div class="form-hint">يُولَّد تلقائياً إذا تُرك فارغاً.</div>
            </div>
            <div class="col-12">
                <label class="form-label">الباركود</label>
                <input type="text" name="barcode" value="{{ old('barcode', $product->barcode) }}" class="form-control @error('barcode') is-invalid @enderror" dir="ltr">
                @error('barcode') <div class="invalid-feedback">{{ $message }}</div> @enderror
                <div class="form-hint">يُستخدم لربط صورة الاستيراد مثل 3.png بالمنتج ذي الباركود 3.</div>
            </div>
            <div class="col-12">
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
            <div class="col-12">
                <label class="form-label">الوصف</label>
                <textarea name="description" rows="4" class="form-control @error('description') is-invalid @enderror" data-ai-field="description">{{ old('description', $product->description) }}</textarea>
                @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>
    </section>

    <section class="product-form-section">
        <div class="product-form-section__header">
            <div>
                <span class="product-form-section__badge">السعر والتوفر</span>
                <h3>الأسعار والمخزون</h3>
            </div>
        </div>
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">السعر</label>
                <input type="number" step="0.01" min="0" name="price" value="{{ old('price', $product->price) }}" class="form-control @error('price') is-invalid @enderror" required>
                @error('price') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4">
                <label class="form-label">سعر العرض</label>
                <input type="number" step="0.01" min="0" name="discount_price" value="{{ old('discount_price', $product->discount_price) }}" class="form-control @error('discount_price') is-invalid @enderror">
                @error('discount_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                <div class="form-hint">فارغ أو 0 = السعر الأصلي بدون خصم.</div>
            </div>
            <div class="col-md-4">
                <label class="form-label">المخزون</label>
                <input type="number" min="0" name="stock" value="{{ old('stock', $product->stock ?? 0) }}" class="form-control @error('stock') is-invalid @enderror" required>
                @error('stock') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4">
                <label class="form-label">عدد الحبات في العبوة الواحدة</label>
                <input type="number" min="1" name="piece_count" value="{{ old('piece_count', $product->piece_count) }}" class="form-control @error('piece_count') is-invalid @enderror">
                @error('piece_count') <div class="invalid-feedback">{{ $message }}</div> @enderror
                <div class="form-hint">يظهر للعميل إذا كان أكبر من 1.</div>
            </div>
            <div class="col-md-4">
                <label class="form-label">الوزن</label>
                <input type="text" name="weight_label" value="{{ old('weight_label', $product->weight_label) }}" class="form-control @error('weight_label') is-invalid @enderror">
                @error('weight_label') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4">
                <label class="form-label">وصف الكمية الظاهر</label>
                <input type="text" name="quantity_label" value="{{ old('quantity_label', $product->quantity_label) }}" class="form-control @error('quantity_label') is-invalid @enderror">
                @error('quantity_label') <div class="invalid-feedback">{{ $message }}</div> @enderror
                <div class="form-hint">اختياري. يُولَّد من الحبات والوزن إن تُرك فارغاً.</div>
            </div>
        </div>
    </section>

    <section class="product-form-section">
        <div class="product-form-section__header">
            <div>
                <span class="product-form-section__badge">المحتوى المرئي</span>
                <h3>الصور والوصف البصري</h3>
            </div>
        </div>
        <div class="row g-3 align-items-end">
            <div class="col-md-6">
                <label class="form-label">صورة المنتج</label>
                <input type="file" name="image" accept="image/*" class="form-control @error('image') is-invalid @enderror" data-image-preview="#product-preview">
                @error('image') <div class="invalid-feedback">{{ $message }}</div> @enderror
                <img id="product-preview" src="{{ $imageSrc }}" alt="" class="upload-preview mt-2" @if(! $imageSrc) hidden @endif>
            </div>
            <div class="col-md-6">
                <label class="form-label">أو رابط صورة</label>
                <input type="text" name="image_url" value="{{ $imageUrl }}" class="form-control @error('image_url') is-invalid @enderror" placeholder="https://... أو أي نص/رابط" inputmode="url">
                @error('image_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="mt-3">
            <label class="form-label">صور إضافية (اختياري)</label>
            <input type="file" name="gallery[]" accept="image/*" multiple class="form-control @error('gallery') is-invalid @enderror">
            @error('gallery') <div class="invalid-feedback">{{ $message }}</div> @enderror
            @error('gallery.*') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            <textarea name="gallery_urls" rows="2" class="form-control mt-2 @error('gallery_urls') is-invalid @enderror">{{ old('gallery_urls') }}</textarea>
            @error('gallery_urls') <div class="invalid-feedback">{{ $message }}</div> @enderror
            <div class="form-hint">رابط واحد في كل سطر.</div>
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
    </section>

    <section class="product-form-section">
        <div class="product-form-section__header">
            <div>
                <span class="product-form-section__badge">SEO وAI</span>
                <h3>محتوى المنتج</h3>
            </div>
        </div>
        <div class="row g-3">
            <div class="col-12">
                <label class="form-label">الفوائد</label>
                <textarea name="benefits" rows="4" class="form-control @error('benefits') is-invalid @enderror" data-ai-field="benefits">{{ $benefits }}</textarea>
                @error('benefits') <div class="invalid-feedback">{{ $message }}</div> @enderror
                <div class="form-hint">سطر لكل فائدة. تُملأ مع زر توليد.</div>
            </div>
            <div class="col-md-6">
                <label class="form-label">كلمات البحث</label>
                <input type="text" name="keywords" value="{{ $keywords }}" class="form-control @error('keywords') is-invalid @enderror" data-ai-field="keywords">
                @error('keywords') <div class="invalid-feedback">{{ $message }}</div> @enderror
                <div class="form-hint">افصل الكلمات بفاصلة.</div>
            </div>
            <div class="col-md-6">
                <label class="form-label">طريقة الاستخدام</label>
                <textarea name="usage_instructions" rows="3" class="form-control @error('usage_instructions') is-invalid @enderror" data-ai-field="usage_instructions">{{ old('usage_instructions', $product->usage_instructions) }}</textarea>
                @error('usage_instructions') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>
    </section>

    <div class="product-relations mb-4">
        <div class="product-relations__intro">
            <h3 class="product-relations__title">المنتجات المرتبطة</h3>
            <p class="product-relations__lead">اربط منتجات تظهر للعميل مع هذا المنتج في التطبيق.</p>
        </div>

        <div class="row g-3">
            <div class="col-lg-6">
                <section class="product-relation-card product-relation-card--gift">
                    <header class="product-relation-card__head">
                        <div class="product-relation-card__meta">
                            <span class="product-relation-card__icon" aria-hidden="true">
                                <i class="bi bi-gift"></i>
                            </span>
                            <div>
                                <h4 class="product-relation-card__title">منتج هدية</h4>
                                <p class="product-relation-card__desc">يُضاف مجاناً عند الشراء ويظهر على كارد المنتج.</p>
                            </div>
                        </div>
                        <button
                            type="button"
                            class="btn btn-sm btn-outline-success rounded-pill product-relation-card__action"
                            data-bs-toggle="modal"
                            data-bs-target="#giftProductModal"
                        >
                            <i class="bi bi-plus-lg ms-1"></i>
                            هدية جديدة
                        </button>
                    </header>

                    <div class="product-relation-card__body" data-gift-product-picker>
                        <x-admin.product-picker
                            name="gift_product_id"
                            :selected="$giftProducts ?? []"
                            :multiple="false"
                            :except="$product->exists ? $product->id : null"
                            :allow-empty="true"
                            :gift-only="true"
                            :embedded="true"
                            empty-label="لم تُحدَّد هدية بعد"
                            placeholder="ابحث عن منتج هدية..."
                            hint=""
                        />
                    </div>
                    @error('gift_product_id') <div class="text-danger small px-1 pt-2">{{ $message }}</div> @enderror
                </section>
            </div>

            <div class="col-lg-6">
                <section class="product-relation-card product-relation-card--bundle">
                    <header class="product-relation-card__head">
                        <div class="product-relation-card__meta">
                            <span class="product-relation-card__icon" aria-hidden="true">
                                <i class="bi bi-bag-plus"></i>
                            </span>
                            <div>
                                <h4 class="product-relation-card__title">يُشترى معه</h4>
                                <p class="product-relation-card__desc">منتجات مقترحة في تفاصيل المنتج لزيادة المبيعات.</p>
                            </div>
                        </div>
                    </header>

                    <div class="product-relation-card__body">
                        <x-admin.product-picker
                            name="complementary_product_ids[]"
                            :selected="$complementaryProducts ?? []"
                            :except="$product->exists ? $product->id : null"
                            :exclude-gifts="true"
                            :embedded="true"
                            placeholder="ابحث وأضف منتجات مكمّلة..."
                            hint=""
                        />
                    </div>
                    @error('complementary_product_ids') <div class="text-danger small px-1 pt-2">{{ $message }}</div> @enderror
                    @error('complementary_product_ids.*') <div class="text-danger small px-1 pt-2">{{ $message }}</div> @enderror
                </section>
            </div>
        </div>
    </div>

    <section class="product-form-section product-form-section--footer">
        <div class="product-inline-status-row">
            <div class="product-inline-field">
                <label class="form-label">{{ $strings::SORT_ORDER }}</label>
                <input type="number" min="0" name="sort_order" value="{{ old('sort_order', $product->sort_order ?? 0) }}" class="form-control">
            </div>

            <div class="product-inline-toggle">
                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" @checked(old('is_active', $product->is_active ?? true))>
                <label class="form-check-label" for="is_active">{{ $strings::ACTIVE }}</label>
            </div>

            <div class="product-inline-toggle">
                <input class="form-check-input" type="checkbox" name="is_featured" value="1" id="is_featured" @checked(old('is_featured', $product->is_featured))>
                <label class="form-check-label" for="is_featured">مميز</label>
            </div>
        </div>
    </section>
</div>
