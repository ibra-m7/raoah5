@php
    $coupon = $coupon ?? new \App\Models\Coupon();
    $selectedProducts = old('product_ids', $coupon->products?->pluck('id')->all() ?? []);
    $selectedCategories = old('category_ids', $coupon->categories?->pluck('id')->all() ?? []);
    $applies = old('applies_to', $coupon->applies_to?->value ?? 'all');
    $type = old('type', $coupon->type?->value ?? 'percent');
@endphp

<div class="mb-3">
    <label class="form-label">كود الكوبون</label>
    <input type="text" name="code" value="{{ old('code', $coupon->code) }}" class="form-control @error('code') is-invalid @enderror" required placeholder="WELCOME10" dir="ltr" style="text-transform: uppercase">
    @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
    <div class="form-hint">أحرف إنجليزية كبيرة وأرقام وشرطة. يظهر للعميل كما هو.</div>
</div>

<div class="mb-3">
    <label class="form-label">عنوان داخلي</label>
    <input type="text" name="title" value="{{ old('title', $coupon->title) }}" class="form-control @error('title') is-invalid @enderror" placeholder="خصم الترحيب 10٪">
    @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label class="form-label">وصف للفريق</label>
    <textarea name="description" rows="2" class="form-control @error('description') is-invalid @enderror">{{ old('description', $coupon->description) }}</textarea>
    @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="row">
    <div class="col-md-4 mb-3">
        <label class="form-label">نوع الخصم</label>
        <select name="type" id="coupon_type" class="form-select @error('type') is-invalid @enderror">
            @foreach ($types as $item)
                <option value="{{ $item->value }}" @selected($type === $item->value)>{{ $item->label() }}</option>
            @endforeach
        </select>
        @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4 mb-3" id="value_wrap">
        <label class="form-label">القيمة</label>
        <input type="number" step="0.01" min="0" name="value" value="{{ old('value', $coupon->value) }}" class="form-control @error('value') is-invalid @enderror">
        @error('value') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <div class="form-hint">٪ للنسبة أو {{ $strings::CURRENCY }} للمبلغ الثابت.</div>
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">سقف الخصم ({{ $strings::CURRENCY }})</label>
        <input type="number" step="0.01" min="0" name="max_discount" value="{{ old('max_discount', $coupon->max_discount) }}" class="form-control @error('max_discount') is-invalid @enderror" placeholder="اختياري">
        @error('max_discount') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="row">
    <div class="col-md-4 mb-3">
        <label class="form-label">الحد الأدنى للمنتجات المشمولة</label>
        <input type="number" step="0.01" min="0" name="min_subtotal" value="{{ old('min_subtotal', $coupon->min_subtotal ?? 0) }}" class="form-control">
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">حد الاستخدام الكلي</label>
        <input type="number" min="1" name="usage_limit" value="{{ old('usage_limit', $coupon->usage_limit) }}" class="form-control" placeholder="مفتوح">
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">لكل عميل</label>
        <input type="number" min="1" name="usage_limit_per_user" value="{{ old('usage_limit_per_user', $coupon->usage_limit_per_user ?? 1) }}" class="form-control" required>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">يبدأ</label>
        <input type="datetime-local" name="starts_at" value="{{ old('starts_at', optional($coupon->starts_at)?->format('Y-m-d\TH:i')) }}" class="form-control">
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">ينتهي</label>
        <input type="datetime-local" name="ends_at" value="{{ old('ends_at', optional($coupon->ends_at)?->format('Y-m-d\TH:i')) }}" class="form-control @error('ends_at') is-invalid @enderror">
        @error('ends_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="mb-3">
    <label class="form-label">نطاق التطبيق</label>
    <select name="applies_to" id="applies_to" class="form-select @error('applies_to') is-invalid @enderror">
        @foreach ($scopes as $item)
            <option value="{{ $item->value }}" @selected($applies === $item->value)>{{ $item->label() }}</option>
        @endforeach
    </select>
    @error('applies_to') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3" id="products_wrap">
    <label class="form-label">المنتجات المشمولة</label>
    <select name="product_ids[]" class="form-select @error('product_ids') is-invalid @enderror" multiple size="8">
        @foreach ($products as $product)
            <option value="{{ $product->id }}" @selected(in_array($product->id, $selectedProducts))>{{ $product->name }}</option>
        @endforeach
    </select>
    @error('product_ids') <div class="invalid-feedback">{{ $message }}</div> @enderror
    <div class="form-hint">اضغط Ctrl لاختيار أكثر من منتج.</div>
</div>

<div class="mb-3" id="categories_wrap">
    <label class="form-label">الأقسام المشمولة</label>
    <select name="category_ids[]" class="form-select @error('category_ids') is-invalid @enderror" multiple size="8">
        @foreach ($categories as $category)
            <option value="{{ $category->id }}" @selected(in_array($category->id, $selectedCategories))>{{ $category->name }}</option>
        @endforeach
    </select>
    @error('category_ids') <div class="invalid-feedback">{{ $message }}</div> @enderror
    <div class="form-hint">يشمل القسم والأقسام الفرعية التابعة له.</div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="first_order_only" value="1" id="first_order_only" @checked(old('first_order_only', $coupon->first_order_only ?? false))>
            <label class="form-check-label" for="first_order_only">للطلب الأول فقط</label>
        </div>
    </div>
    <div class="col-md-6 mb-3">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" @checked(old('is_active', $coupon->is_active ?? true))>
            <label class="form-check-label" for="is_active">نشط في التطبيق</label>
        </div>
    </div>
</div>

<script>
    (function () {
        const type = document.getElementById('coupon_type');
        const valueWrap = document.getElementById('value_wrap');
        const applies = document.getElementById('applies_to');
        const products = document.getElementById('products_wrap');
        const categories = document.getElementById('categories_wrap');

        function sync() {
            valueWrap.style.display = type.value === 'free_shipping' ? 'none' : '';
            products.style.display = applies.value === 'products' ? '' : 'none';
            categories.style.display = applies.value === 'categories' ? '' : 'none';
        }
        type.addEventListener('change', sync);
        applies.addEventListener('change', sync);
        sync();
    })();
</script>
