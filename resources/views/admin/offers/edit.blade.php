<x-layouts.admin :title="$title">
    <div class="page-card p-4 p-md-5" style="max-width: 720px">
        <form method="POST" action="{{ route('admin.offers.update', $product) }}" id="promo-edit-form">
            @csrf
            @method('PUT')
            <input type="hidden" name="product_id" value="{{ $product->id }}">

            <div class="locked-product mb-3">
                <strong>{{ $product->name }}</strong>
                <span class="text-muted">السعر الأصلي {{ number_format((float) $product->price, 2) }} {{ $strings::CURRENCY }}</span>
            </div>

            <div class="mb-3">
                <label class="form-label">النوع</label>
                <select name="promo_type" class="form-select">
                    @foreach (\App\Enums\PromoType::cases() as $option)
                        <option value="{{ $option->value }}" @selected(old('promo_type', $type->value) === $option->value)>{{ $option->label() }}</option>
                    @endforeach
                </select>
            </div>

            <div class="promo-mode mb-3">
                <label class="promo-mode-item">
                    <input type="radio" name="mode" value="percent">
                    <span>نسبة مئوية</span>
                </label>
                <label class="promo-mode-item">
                    <input type="radio" name="mode" value="price" checked>
                    <span>سعر ثابت</span>
                </label>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3" id="promo-percent-wrap" hidden>
                    <label class="form-label">نسبة الخصم %</label>
                    <input type="number" step="1" min="1" max="99" name="percent" value="{{ old('percent', $product->discount_percent) }}" class="form-control">
                </div>
                <div class="col-md-6 mb-3" id="promo-price-wrap">
                    <label class="form-label">السعر بعد التخفيض</label>
                    <input type="number" step="0.01" min="0.01" name="discount_price" value="{{ old('discount_price', $product->discount_price) }}" class="form-control @error('discount_price') is-invalid @enderror" required>
                    @error('discount_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6 mb-3 d-flex align-items-end">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="is_featured" value="1" id="is_featured" @checked(old('is_featured', $product->is_featured))>
                        <label class="form-check-label" for="is_featured">إبراز في الرئيسية</label>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button class="btn btn-brand">{{ $strings::SAVE }}</button>
                <a href="{{ route('admin.offers.index', ['type' => $type->value]) }}" class="btn btn-outline-secondary rounded-pill">{{ $strings::CANCEL }}</a>
            </div>
        </form>
    </div>
    <script>
        (() => {
            const form = document.getElementById('promo-edit-form');
            const sync = () => {
                const mode = form.querySelector('input[name="mode"]:checked')?.value;
                document.getElementById('promo-percent-wrap').hidden = mode !== 'percent';
                document.getElementById('promo-price-wrap').hidden = mode !== 'price';
                form.querySelector('[name="discount_price"]').required = mode === 'price';
            };
            form.querySelectorAll('input[name="mode"]').forEach((el) => el.addEventListener('change', sync));
            sync();
        })();
    </script>
</x-layouts.admin>
