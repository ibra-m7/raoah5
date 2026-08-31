@php
    $currentProductId = ($product ?? null)?->exists ? (int) $product->id : 0;
@endphp

<div
    class="modal fade"
    id="giftProductModal"
    tabindex="-1"
    aria-hidden="true"
    data-gift-quick-endpoint="{{ route('admin.products.gift-quick') }}"
    data-current-product-id="{{ $currentProductId }}"
>
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content detail-modal">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">هدية جديدة</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ $strings::CLOSE }}"></button>
            </div>
            <form id="giftProductForm" class="modal-scroll-form" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <x-admin.help-note>
                        أنشئ هدية جديدة غير موجودة في قائمة المنتجات. لن تظهر في قسم المنتجات ولن تُباع بشكل مستقل.
                        @if ($currentProductId > 0)
                            سيُربط تلقائياً بالمنتج الذي تعدّله الآن. يمكنك أيضاً ربطه بمنتجات رئيسية إضافية.
                        @else
                            سيُربط تلقائياً بالمنتج الذي تضيفه الآن عند الحفظ. يمكنك أيضاً ربطه بمنتجات رئيسية موجودة.
                        @endif
                    </x-admin.help-note>

                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label">اسم الهدية *</label>
                            <input type="text" name="name" class="form-control" required maxlength="255" data-gift-field="name">
                            <div class="invalid-feedback d-block" data-gift-error="name"></div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">المخزون *</label>
                            <input type="number" min="0" name="stock" value="10" class="form-control" required data-gift-field="stock">
                            <div class="invalid-feedback d-block" data-gift-error="stock"></div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">السعر</label>
                            <input type="number" step="0.01" min="0" name="price" value="0" class="form-control" data-gift-field="price">
                            <div class="form-hint">0 = هدية مجانية للعميل.</div>
                            <div class="invalid-feedback d-block" data-gift-error="price"></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">صورة الهدية</label>
                        <input type="file" name="image" accept="image/*" class="form-control" data-gift-field="image">
                        <div class="invalid-feedback d-block" data-gift-error="image"></div>
                    </div>

                    <div class="gift-modal-section mb-0">
                        <label class="form-label">منتجات رئيسية إضافية (اختياري)</label>
                        <div class="form-hint mb-2">اختر منتجات موجودة يُضاف معها هذا الهدية مجاناً.</div>
                        <div data-gift-main-picker>
                            <x-admin.product-picker
                                name="main_product_ids[]"
                                :selected="[]"
                                :except="$currentProductId > 0 ? $currentProductId : null"
                                :exclude-gifts="true"
                                hint="ابحث وأضف منتجاً رئيسياً أو أكثر."
                            />
                        </div>
                        <div class="invalid-feedback d-block" data-gift-error="main_product_ids"></div>
                        <div class="invalid-feedback d-block" data-gift-error="main_product_ids.*"></div>
                    </div>

                    <div class="alert alert-danger mt-3 mb-0" data-gift-form-error hidden></div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-brand" data-gift-submit>
                        <i class="bi bi-gift ms-1"></i>
                        حفظ الهدية
                    </button>
                    <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">{{ $strings::CANCEL }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
