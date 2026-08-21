@php
    $category = $category ?? new \App\Models\Category(['is_active' => true, 'sort_order' => 0, 'color' => \App\Support\Theme::PRIMARY]);
    $color = old('color', $category->color ?: \App\Support\Theme::PRIMARY);
    $level = $level ?? 'root';
    $tab = $tab ?? 'roots';
    $selectedSections = old('display_section_ids', $selectedSectionIds ?? []);
    $displaySections = $displaySections ?? collect();
    $nameLabel = match ($level) {
        'category' => 'اسم التصنيف',
        'sub' => 'اسم التصنيف الفرعي',
        default => 'اسم القسم',
    };
    $parentLabel = $level === 'sub' ? 'التصنيف' : 'القسم الرئيسي';
@endphp

<input type="hidden" name="level" value="{{ $level }}">

<div class="mb-3">
    <label class="form-label">{{ $nameLabel }}</label>
    <input type="text" name="name" value="{{ old('name', $category->name) }}" class="form-control @error('name') is-invalid @enderror" required>
    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

@if ($level === 'root')
    <input type="hidden" name="parent_id" value="">
@else
    <div class="mb-3">
        <label class="form-label">{{ $parentLabel }}</label>
        <select name="parent_id" class="form-select @error('parent_id') is-invalid @enderror" required>
            <option value="">اختر</option>
            @foreach ($parents as $parent)
                <option value="{{ $parent->id }}" @selected(old('parent_id', $category->parent_id) == $parent->id)>
                    {{ $parent->path_label ?? $parent->name }}
                </option>
            @endforeach
        </select>
        @error('parent_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
@endif

@if ($level === 'category' && $displaySections->isNotEmpty())
    <div class="mb-3">
        <label class="form-label">التبويب</label>
        <div class="picker-grid">
            @foreach ($displaySections as $section)
                <label class="picker-item">
                    <input type="checkbox" name="display_section_ids[]" value="{{ $section->id }}" @checked(in_array($section->id, $selectedSections, false) || in_array((string) $section->id, $selectedSections, true))>
                    <span><strong>{{ $section->emoji }} {{ $section->name }}</strong></span>
                </label>
            @endforeach
        </div>
        @error('display_section_ids') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
    </div>
@endif

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">{{ $strings::ICON }}</label>
        <input type="file" name="icon" accept="image/*" class="form-control @error('icon') is-invalid @enderror" data-image-preview="#icon-preview">
        @error('icon') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <img id="icon-preview" src="{{ $category->icon_src }}" alt="" class="upload-preview mt-2" @if(! $category->icon_src) hidden @endif>
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">{{ $strings::IMAGE }}</label>
        <input type="file" name="image" accept="image/*" class="form-control @error('image') is-invalid @enderror" data-image-preview="#image-preview">
        @error('image') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <img id="image-preview" src="{{ $category->image_src }}" alt="" class="upload-preview mt-2" @if(! $category->image_src) hidden @endif>
    </div>
</div>

<div class="row">
    <div class="col-md-4 mb-3">
        <label class="form-label">{{ $strings::COLOR }}</label>
        <div class="color-picker-field">
            <input type="color" name="color" value="{{ $color }}" class="color-picker-input @error('color') is-invalid @enderror" data-color-sync="#color-hex">
            <span id="color-hex" class="color-picker-hex">{{ $color }}</span>
        </div>
        @error('color') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">{{ $strings::SORT_ORDER }}</label>
        <input type="number" min="0" name="sort_order" value="{{ old('sort_order', $category->sort_order ?? 0) }}" class="form-control @error('sort_order') is-invalid @enderror">
        @error('sort_order') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4 mb-3 d-flex align-items-end">
        <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" @checked(old('is_active', $category->is_active))>
            <label class="form-check-label" for="is_active">{{ $strings::ACTIVE }}</label>
        </div>
    </div>
</div>
