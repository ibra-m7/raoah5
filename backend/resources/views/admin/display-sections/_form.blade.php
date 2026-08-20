@php
    $section = $section ?? new \App\Models\DisplaySection(['is_active' => true, 'sort_order' => 0]);
    $selected = old('category_ids', $selectedIds ?? []);
@endphp

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">الاسم</label>
        <input type="text" name="name" value="{{ old('name', $section->name) }}" class="form-control @error('name') is-invalid @enderror" required>
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">المعرف</label>
        <input type="text" name="slug" value="{{ old('slug', $section->slug) }}" class="form-control @error('slug') is-invalid @enderror" placeholder="groceries" dir="ltr">
        @error('slug') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">إيموجي</label>
        <input type="text" name="emoji" value="{{ old('emoji', $section->emoji) }}" class="form-control" placeholder="🛒">
    </div>
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
    <label class="form-label">الأقسام داخل هذا العرض</label>
    <input type="search" class="form-control mb-2" placeholder="ابحث داخل الأقسام..." data-picker-search="#display-categories">
    <div class="picker-grid" id="display-categories">
        @foreach ($categories as $category)
            <label class="picker-item" data-picker-text="{{ $category->name }} {{ $category->parent?->name }}">
                <input type="checkbox" name="category_ids[]" value="{{ $category->id }}" @checked(in_array($category->id, $selected, false) || in_array((string) $category->id, $selected, true))>
                <span>
                    <strong>{{ $category->name }}</strong>
                    <small class="d-block text-muted">{{ $category->parent?->name ?? $strings::ROOT_CATEGORY }}</small>
                </span>
            </label>
        @endforeach
    </div>
    @error('category_ids') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
</div>
