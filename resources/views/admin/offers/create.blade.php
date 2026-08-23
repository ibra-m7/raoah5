<x-layouts.admin :title="$title">
    <div class="page-card p-4 p-md-5">
        <x-admin.help-note>
            ابحث واختر منتجات بلا خصم أو عرض، ثم حدّد السعر أو النسبة. يمكن تطبيقها على عدة منتجات دفعة واحدة.
        </x-admin.help-note>

        <form method="POST" action="{{ route('admin.offers.store') }}" class="mt-3" id="promo-form">
            @csrf
            <input type="hidden" name="promo_type" value="{{ $type->value }}">

            <div class="promo-search-bar">
                <i class="bi bi-search"></i>
                <input type="search" id="promo-search" class="form-control" placeholder="ابحث بالاسم أو الرمز... المنتجات المخفّضة حالياً لن تظهر" autocomplete="off">
            </div>
            @error('product_ids') <div class="text-danger small mb-2">{{ $message }}</div> @enderror

            <div id="promo-selected" class="promo-selected" hidden></div>

            <div id="promo-grid" class="promo-grid" data-endpoint="{{ route('admin.offers.available') }}"></div>
            <div id="promo-empty" class="text-muted text-center py-4" hidden>لا توجد منتجات متاحة مطابقة.</div>

            <div class="promo-apply mt-4">
                <div class="promo-mode">
                    <label class="promo-mode-item">
                        <input type="radio" name="mode" value="percent" checked>
                        <span>نسبة مئوية</span>
                    </label>
                    <label class="promo-mode-item">
                        <input type="radio" name="mode" value="price">
                        <span>سعر ثابت</span>
                    </label>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3" id="promo-percent-wrap">
                        <label class="form-label">نسبة الخصم %</label>
                        <input type="number" step="1" min="1" max="99" name="percent" value="{{ old('percent', 10) }}" class="form-control @error('percent') is-invalid @enderror">
                        @error('percent') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6 mb-3" id="promo-price-wrap" hidden>
                        <label class="form-label">السعر بعد التخفيض</label>
                        <input type="number" step="0.01" min="0.01" name="discount_price" value="{{ old('discount_price') }}" class="form-control @error('discount_price') is-invalid @enderror">
                        @error('discount_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6 mb-3 d-flex align-items-end">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="is_featured" value="1" id="is_featured" @checked(old('is_featured', true))>
                            <label class="form-check-label" for="is_featured">إبراز في الرئيسية</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button class="btn btn-brand" type="submit">{{ $type->addLabel() }}</button>
                <a href="{{ route('admin.offers.index', ['type' => $type->value]) }}" class="btn btn-outline-secondary rounded-pill">{{ $strings::CANCEL }}</a>
            </div>
        </form>
    </div>

    <script>
        (() => {
            const grid = document.getElementById('promo-grid');
            const empty = document.getElementById('promo-empty');
            const selectedBox = document.getElementById('promo-selected');
            const search = document.getElementById('promo-search');
            const form = document.getElementById('promo-form');
            const endpoint = grid.dataset.endpoint;
            const selected = new Map();
            let timer = null;
            let catalog = @json($products);

            const money = (n) => Number(n).toFixed(2);

            const renderSelected = () => {
                selectedBox.hidden = selected.size === 0;
                selectedBox.innerHTML = [...selected.values()].map((p) => `
                    <span class="promo-chip">
                        ${p.name}
                        <button type="button" data-remove="${p.id}" aria-label="إزالة">&times;</button>
                        <input type="hidden" name="product_ids[]" value="${p.id}">
                    </span>
                `).join('');
            };

            const card = (p) => {
                const on = selected.has(String(p.id));
                return `
                    <button type="button" class="promo-pick ${on ? 'is-on' : ''}" data-id="${p.id}">
                        <span class="promo-pick-img" style="${p.image ? `background-image:url('${p.image}')` : ''}"></span>
                        <span class="promo-pick-name">${p.name}</span>
                        <span class="promo-pick-price">${money(p.price)}</span>
                    </button>
                `;
            };

            const renderGrid = (items) => {
                empty.hidden = items.length > 0;
                grid.innerHTML = items.map(card).join('');
            };

            const load = (q = '') => {
                const url = new URL(endpoint, window.location.origin);
                if (q) url.searchParams.set('q', q);
                fetch(url, { headers: { 'Accept': 'application/json' } })
                    .then((r) => r.json())
                    .then((data) => {
                        catalog = data.products || [];
                        renderGrid(catalog);
                    });
            };

            grid.addEventListener('click', (e) => {
                const btn = e.target.closest('[data-id]');
                if (!btn) return;
                const id = btn.dataset.id;
                const item = catalog.find((p) => String(p.id) === id);
                if (!item) return;
                if (selected.has(id)) selected.delete(id);
                else selected.set(id, item);
                renderSelected();
                renderGrid(catalog);
            });

            selectedBox.addEventListener('click', (e) => {
                const btn = e.target.closest('[data-remove]');
                if (!btn) return;
                selected.delete(btn.dataset.remove);
                renderSelected();
                renderGrid(catalog);
            });

            search.addEventListener('input', () => {
                clearTimeout(timer);
                timer = setTimeout(() => load(search.value.trim()), 220);
            });

            const syncMode = () => {
                const mode = form.querySelector('input[name="mode"]:checked')?.value;
                document.getElementById('promo-percent-wrap').hidden = mode !== 'percent';
                document.getElementById('promo-price-wrap').hidden = mode !== 'price';
            };
            form.querySelectorAll('input[name="mode"]').forEach((el) => el.addEventListener('change', syncMode));
            syncMode();

            form.addEventListener('submit', (e) => {
                if (selected.size === 0) {
                    e.preventDefault();
                    alert('اختر منتجاً واحداً على الأقل.');
                }
            });

            renderGrid(catalog);
        })();
    </script>
</x-layouts.admin>
