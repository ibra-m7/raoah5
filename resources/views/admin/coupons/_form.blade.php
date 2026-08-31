@php
    $coupon = $coupon ?? new \App\Models\Coupon();
    $selectedCategories = old('category_ids', $coupon->categories?->pluck('id')->all() ?? []);
    $applies = old('applies_to', $coupon->applies_to?->value ?? 'all');
    $type = old('type', $coupon->type?->value ?? 'percent');
@endphp

<div class="alert alert-light border mb-4">
    <strong>أين يظهر الكوبون؟</strong>
    <p class="mb-0 small text-muted">يُطبَّق عند إتمام الطلب في التطبيق. لا يظهر في الصفحة الرئيسية.</p>
</div>

<div class="mb-3">
    <label class="form-label">كود الكوبون</label>
    <input type="text" name="code" value="{{ old('code', $coupon->code) }}" class="form-control @error('code') is-invalid @enderror" required dir="ltr" style="text-transform: uppercase">
    @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
    <div class="form-hint">أحرف إنجليزية وأرقام. يظهر للعميل كما هو.</div>
</div>

<div class="mb-3">
    <label class="form-label">عنوان داخلي</label>
    <input type="text" name="title" value="{{ old('title', $coupon->title) }}" class="form-control @error('title') is-invalid @enderror">
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
        <div class="form-hint">٪ للنسبة أو مبلغ بالريال.</div>
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">سقف الخصم ({{ $strings::CURRENCY }})</label>
        <input type="number" step="0.01" min="0" name="max_discount" value="{{ old('max_discount', $coupon->max_discount) }}" class="form-control @error('max_discount') is-invalid @enderror">
        @error('max_discount') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <div class="form-hint">اختياري. حد أعلى لخصم النسبة.</div>
    </div>
</div>

<div class="row">
    <div class="col-md-4 mb-3">
        <label class="form-label">الحد الأدنى للمنتجات المشمولة</label>
        <input type="number" step="0.01" min="0" name="min_subtotal" value="{{ old('min_subtotal', $coupon->min_subtotal ?? 0) }}" class="form-control">
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">حد الاستخدام الكلي</label>
        <input type="number" min="1" name="usage_limit" value="{{ old('usage_limit', $coupon->usage_limit) }}" class="form-control">
        <div class="form-hint">فارغ = بلا حد.</div>
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
    <x-admin.product-picker
        name="product_ids[]"
        :selected="$selectedProducts ?? []"
        hint="ابحث وأضف المنتجات المشمولة بالكوبون."
    />
    @error('product_ids') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
</div>

<div class="mb-3" id="categories_wrap">
    <label class="form-label">الأقسام المشمولة</label>
    <input type="search" class="form-control mb-2" placeholder="ابحث في الأقسام..." data-category-picker-q>
    <div class="category-picker-grid" data-category-picker>
        @foreach ($categories as $category)
            <label class="category-picker-item" data-category-picker-item data-label="{{ $category->name }}">
                <input
                    type="checkbox"
                    name="category_ids[]"
                    value="{{ $category->id }}"
                    @checked(in_array($category->id, $selectedCategories))
                >
                <span>{{ $category->name }}</span>
            </label>
        @endforeach
    </div>
    @error('category_ids') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
    <div class="form-hint">يشمل القسم وفروعه. يُطبَّق الخصم عند الدفع فقط.</div>
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
