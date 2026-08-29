@php
    $section = $section ?? new \App\Models\HomeSection(['is_active' => true, 'sort_order' => 0]);
    $style = old('display_style', $section->exists ? $section->displayStyle() : 'general');
    $styles = \App\Models\HomeSection::displayStyles();
@endphp

<div class="row">
    <div class="col-md-8 mb-3">
        <label class="form-label">اسم القسم</label>
        <input type="text" name="title" value="{{ old('title', $section->title) }}" class="form-control @error('title') is-invalid @enderror" required>
        @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">شكل العرض</label>
        <select name="display_style" class="form-select @error('display_style') is-invalid @enderror @error('key') is-invalid @enderror">
            @foreach ($styles as $value => $meta)
                <option value="{{ $value }}" @selected($style === $value)>{{ $meta['label'] }}</option>
            @endforeach
        </select>
        @error('display_style') <div class="invalid-feedback">{{ $message }}</div> @enderror
        @error('key') <div class="invalid-feedback">هذا الشكل مستخدم في قسم آخر. اختر شكلاً مختلفاً أو قسماً عادياً.</div> @enderror
        <div class="form-hint">يغيّر مظهر الشريط فقط.</div>
    </div>
</div>

<div class="mb-3">
    <label class="form-label">العنوان الفرعي</label>
    <input type="text" name="subtitle" value="{{ old('subtitle', $section->subtitle) }}" class="form-control">
    <div class="form-hint">يظهر تحت الاسم في التطبيق.</div>
</div>

@php
    $useDefaultBg = old('use_default_background', empty($section->background_color));
    $bgColor = old('background_color', $section->background_color ?: '#E8F8EC');
    $bgColor = preg_match('/^#?[0-9A-Fa-f]{6}$/', (string) $bgColor)
        ? '#'.strtoupper(ltrim((string) $bgColor, '#'))
        : '#E8F8EC';
@endphp
<div class="mb-3">
    <label class="form-label">لون خلفية القسم في التطبيق</label>
    <div class="d-flex align-items-center gap-3 flex-wrap mb-2">
        <div class="color-picker-field">
            <input
                type="color"
                id="home_section_background_color"
                name="background_color"
                value="{{ $bgColor }}"
                class="color-picker-input @error('background_color') is-invalid @enderror"
                data-color-sync="#home-section-bg-hex"
                @disabled($useDefaultBg)
            >
            <span id="home-section-bg-hex" class="color-picker-hex">{{ $bgColor }}</span>
        </div>
        <div class="form-check mb-0">
            <input
                class="form-check-input"
                type="checkbox"
                name="use_default_background"
                value="1"
                id="use_default_background"
                data-home-section-bg-toggle="#home_section_background_color"
                @checked($useDefaultBg)
            >
            <label class="form-check-label" for="use_default_background">اللون الافتراضي للتطبيق (فاتح جداً)</label>
        </div>
    </div>
    @error('background_color') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
    <div class="form-hint">يُعرض كتدرج من الزاوية اليمنى العليا إلى اليسار السفلي في الصفحة الرئيسية.</div>
</div>

<div class="row">
    <div class="col-md-4 mb-3">
        <label class="form-label">{{ $strings::SORT_ORDER }}</label>
        <input type="number" min="0" name="sort_order" value="{{ old('sort_order', $section->sort_order ?? 0) }}" class="form-control">
    </div>
    <div class="col-md-4 mb-3 d-flex align-items-end">
        <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" @checked(old('is_active', $section->is_active ?? true))>
            <label class="form-check-label" for="is_active">{{ $strings::ACTIVE }}</label>
        </div>
    </div>
</div>

<div class="mb-3">
    <label class="form-label">المنتجات</label>
    <x-admin.product-picker
        name="product_ids[]"
        :selected="$selectedProducts ?? []"
        hint="ابحث وأضف منتجات هذا القسم دون تحميل الكتالوج كاملاً."
    />
    @error('product_ids') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
</div>
