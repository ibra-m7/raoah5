<x-layouts.admin :title="$title">
    <x-admin.page-head
        :title="$title"
        subtitle="إدارة عبارات البحث المتحركة والاقتراحات الذكية والأكثر بحثاً ومتابعة ما يبحث عنه العملاء في التطبيق."
    />

    <ul class="nav settings-tabs mb-3" role="tablist">
        <li class="nav-item" role="presentation">
            <a class="nav-link {{ $tab === 'phrases' ? 'active' : '' }}" href="{{ route('admin.search-placeholders.index', ['tab' => 'phrases']) }}">العبارات المتحركة</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link {{ $tab === 'smart' ? 'active' : '' }}" href="{{ route('admin.search-placeholders.index', ['tab' => 'smart']) }}">اقتراحات ذكية</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link {{ $tab === 'trending' ? 'active' : '' }}" href="{{ route('admin.search-placeholders.index', ['tab' => 'trending']) }}">الأكثر بحثاً حولك</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link {{ $tab === 'customers' ? 'active' : '' }}" href="{{ route('admin.search-placeholders.index', ['tab' => 'customers']) }}">سجل البحث</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link {{ $tab === 'queries' ? 'active' : '' }}" href="{{ route('admin.search-placeholders.index', ['tab' => 'queries']) }}">الكلمات والمنتجات</a>
        </li>
    </ul>

    @if ($tab === 'phrases')
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <form method="GET" class="d-flex flex-wrap gap-2">
                <input type="hidden" name="tab" value="phrases">
                <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control" style="max-width: 240px" placeholder="{{ $strings::SEARCH }}">
                <select name="status" class="form-select" style="max-width: 140px">
                    <option value="">{{ $strings::STATUS }}</option>
                    <option value="active" @selected(($filters['status'] ?? '') === 'active')">{{ $strings::ACTIVE }}</option>
                    <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')">{{ $strings::INACTIVE }}</option>
                </select>
                <button class="btn btn-outline-success rounded-pill">{{ $strings::FILTER }}</button>
            </form>
            <button type="button" class="btn btn-success rounded-pill" data-bs-toggle="modal" data-bs-target="#searchPhraseModal" data-search-phrase-create>
                <i class="bi bi-plus-lg ms-1"></i>
                {{ $strings::ADD_SEARCH_PLACEHOLDER }}
            </button>
        </div>

        <div class="page-card p-4">
            @if ($placeholders->isEmpty())
                <x-admin.empty-state icon="bi-search" modal action="#searchPhraseModal" :action-label="$strings::ADD_SEARCH_PLACEHOLDER" data-search-phrase-create />
            @else
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>العبارة</th>
                                <th>الترتيب</th>
                                <th>{{ $strings::STATUS }}</th>
                                <th>{{ $strings::ACTIONS }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($placeholders as $item)
                                <tr>
                                    <td class="fw-semibold">{{ $item->phrase }}</td>
                                    <td>{{ $item->sort_order }}</td>
                                    <td>
                                        @if ($item->is_active)
                                            <span class="badge badge-soft">{{ $strings::LIVE_IN_APP }}</span>
                                        @else
                                            <span class="badge badge-soft">{{ $strings::INACTIVE }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-success rounded-pill"
                                                data-bs-toggle="modal"
                                                data-bs-target="#searchPhraseModal"
                                                data-search-phrase-edit
                                                data-id="{{ $item->id }}"
                                                data-phrase="{{ $item->phrase }}"
                                                data-sort-order="{{ $item->sort_order }}"
                                                data-is-active="{{ $item->is_active ? '1' : '0' }}"
                                            >{{ $strings::EDIT }}</button>
                                            <form method="POST" action="{{ route('admin.search-placeholders.destroy', $item) }}" onsubmit="return confirm('{{ $strings::CONFIRM_DELETE }}')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger rounded-pill">{{ $strings::DELETE }}</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                {{ $placeholders->links() }}
            @endif
        </div>
    @endif

    @if ($tab === 'smart')
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <form method="GET" class="d-flex flex-wrap gap-2">
                <input type="hidden" name="tab" value="smart">
                <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control" style="max-width: 240px" placeholder="{{ $strings::SEARCH }}">
                <select name="status" class="form-select" style="max-width: 140px">
                    <option value="">{{ $strings::STATUS }}</option>
                    <option value="active" @selected(($filters['status'] ?? '') === 'active')">{{ $strings::ACTIVE }}</option>
                    <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')">{{ $strings::INACTIVE }}</option>
                </select>
                <button class="btn btn-outline-success rounded-pill">{{ $strings::FILTER }}</button>
            </form>
            <button type="button" class="btn btn-success rounded-pill" data-bs-toggle="modal" data-bs-target="#searchSmartModal" data-search-smart-create>
                <i class="bi bi-plus-lg ms-1"></i>
                {{ $strings::ADD_SEARCH_SMART }}
            </button>
        </div>

        <div class="page-card p-4">
            <p class="text-muted small mb-3">تظهر في سكشن «اقتراحات ذكية» داخل صفحة البحث في التطبيق.</p>
            @if ($smartItems->isEmpty())
                <x-admin.empty-state icon="bi-lightbulb" modal action="#searchSmartModal" :action-label="$strings::ADD_SEARCH_SMART" data-search-smart-create />
            @else
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>الاقتراح</th>
                                <th>الترتيب</th>
                                <th>{{ $strings::STATUS }}</th>
                                <th>{{ $strings::ACTIONS }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($smartItems as $item)
                                <tr>
                                    <td class="fw-semibold">{{ $item->phrase }}</td>
                                    <td>{{ $item->sort_order }}</td>
                                    <td>
                                        @if ($item->is_active)
                                            <span class="badge badge-soft">{{ $strings::LIVE_IN_APP }}</span>
                                        @else
                                            <span class="badge badge-soft">{{ $strings::INACTIVE }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-success rounded-pill"
                                                data-bs-toggle="modal"
                                                data-bs-target="#searchSmartModal"
                                                data-search-smart-edit
                                                data-id="{{ $item->id }}"
                                                data-phrase="{{ $item->phrase }}"
                                                data-sort-order="{{ $item->sort_order }}"
                                                data-is-active="{{ $item->is_active ? '1' : '0' }}"
                                            >{{ $strings::EDIT }}</button>
                                            <form method="POST" action="{{ route('admin.search-smart-suggestions.destroy', $item) }}" onsubmit="return confirm('{{ $strings::CONFIRM_DELETE }}')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger rounded-pill">{{ $strings::DELETE }}</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                {{ $smartItems->appends(['tab' => 'smart'])->links() }}
            @endif
        </div>
    @endif

    @if ($tab === 'trending')
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <form method="GET" class="d-flex flex-wrap gap-2">
                <input type="hidden" name="tab" value="trending">
                <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control" style="max-width: 240px" placeholder="{{ $strings::SEARCH }}">
                <select name="status" class="form-select" style="max-width: 140px">
                    <option value="">{{ $strings::STATUS }}</option>
                    <option value="active" @selected(($filters['status'] ?? '') === 'active')">{{ $strings::ACTIVE }}</option>
                    <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')">{{ $strings::INACTIVE }}</option>
                </select>
                <button class="btn btn-outline-success rounded-pill">{{ $strings::FILTER }}</button>
            </form>
            <button type="button" class="btn btn-success rounded-pill" data-bs-toggle="modal" data-bs-target="#searchTrendingModal" data-search-trending-create>
                <i class="bi bi-plus-lg ms-1"></i>
                {{ $strings::ADD_SEARCH_TRENDING_PIN }}
            </button>
        </div>

        <div class="page-card p-4 mb-3">
            <h6 class="fw-bold mb-2">كلمات مضافة يدوياً</h6>
            <p class="text-muted small mb-3">تظهر أولاً في سكشن «الأكثر بحثاً حولك» ثم تُكمَّل تلقائياً من أكثر كلمات بحثها العملاء.</p>
            @if ($trendingPins->isEmpty())
                <x-admin.empty-state icon="bi-fire" modal action="#searchTrendingModal" :action-label="$strings::ADD_SEARCH_TRENDING_PIN" data-search-trending-create />
            @else
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>الكلمة</th>
                                <th>الترتيب</th>
                                <th>{{ $strings::STATUS }}</th>
                                <th>{{ $strings::ACTIONS }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($trendingPins as $item)
                                <tr>
                                    <td class="fw-semibold">{{ $item->phrase }}</td>
                                    <td>{{ $item->sort_order }}</td>
                                    <td>
                                        @if ($item->is_active)
                                            <span class="badge badge-soft">{{ $strings::LIVE_IN_APP }}</span>
                                        @else
                                            <span class="badge badge-soft">{{ $strings::INACTIVE }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-success rounded-pill"
                                                data-bs-toggle="modal"
                                                data-bs-target="#searchTrendingModal"
                                                data-search-trending-edit
                                                data-id="{{ $item->id }}"
                                                data-phrase="{{ $item->phrase }}"
                                                data-sort-order="{{ $item->sort_order }}"
                                                data-is-active="{{ $item->is_active ? '1' : '0' }}"
                                            >{{ $strings::EDIT }}</button>
                                            <form method="POST" action="{{ route('admin.search-trending-pins.destroy', $item) }}" onsubmit="return confirm('{{ $strings::CONFIRM_DELETE }}')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger rounded-pill">{{ $strings::DELETE }}</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                {{ $trendingPins->appends(['tab' => 'trending'])->links() }}
            @endif
        </div>

        <div class="page-card p-4">
            <h6 class="fw-bold mb-2">أكثر ما بحث عنه العملاء (تلقائي)</h6>
            <p class="text-muted small mb-3">تُجمع من سجل البحث خلال آخر ٩٠ يوماً وتُدمج مع الكلمات اليدوية في التطبيق.</p>
            @if ($autoTrending->isEmpty())
                <div class="text-muted small">لا توجد كلمات بحث كافية بعد.</div>
            @else
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>الكلمة</th>
                                <th>مرات البحث</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($autoTrending as $row)
                                <tr>
                                    <td class="fw-semibold">{{ $row->query }}</td>
                                    <td>{{ $row->hits }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endif

    @if ($tab === 'customers')
        <form method="GET" class="d-flex flex-wrap gap-2 mb-3">
            <input type="hidden" name="tab" value="customers">
            <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control" style="max-width: 260px" placeholder="ابحث باسم العميل أو الجوال">
            <button class="btn btn-outline-success rounded-pill">{{ $strings::FILTER }}</button>
        </form>

        <div class="page-card p-4">
            @if ($guestCount > 0)
                <div class="mb-3">
                    <button
                        type="button"
                        class="entity-open w-100 justify-content-between"
                        data-bs-toggle="modal"
                        data-bs-target="#customerSearchLogsModal"
                        data-customer-search-logs
                        data-name="زوار (بدون حساب)"
                        data-logs="{{ $guestLogsJson }}"
                    >
                        <span class="entity-open-text text-start">
                            <strong>زوار (بدون حساب)</strong>
                            <small>{{ $guestCount }} عملية بحث</small>
                        </span>
                        <i class="bi bi-chevron-left"></i>
                    </button>
                </div>
            @endif

            @if ($customers->isEmpty() && $guestCount === 0)
                <x-admin.empty-state icon="bi-people" message="لا توجد عمليات بحث مسجّلة بعد." />
            @elseif ($customers->isEmpty())
                <div class="text-muted small">لا يوجد عملاء مسجّلون في نتائج البحث الحالية.</div>
            @else
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>العميل</th>
                                <th>عدد عمليات البحث</th>
                                <th>آخر بحث</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($customerRows as $row)
                                <tr>
                                    <td>
                                        <button
                                            type="button"
                                            class="entity-open"
                                            data-bs-toggle="modal"
                                            data-bs-target="#customerSearchLogsModal"
                                            data-customer-search-logs
                                            data-name="{{ $row->name }}"
                                            data-logs="{{ $row->logs_json }}"
                                        >
                                            <span class="entity-open-text">
                                                <strong>{{ $row->name }}</strong>
                                                <small>{{ $row->phone }}</small>
                                            </span>
                                        </button>
                                    </td>
                                    <td>{{ $row->searches_count }}</td>
                                    <td>{{ \Illuminate\Support\Carbon::parse($row->last_searched_at)->timezone(config('app.timezone'))->format('Y-m-d H:i') }}</td>
                                    <td>
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-success rounded-pill"
                                            data-bs-toggle="modal"
                                            data-bs-target="#customerSearchLogsModal"
                                            data-customer-search-logs
                                            data-name="{{ $row->name }}"
                                            data-logs="{{ $row->logs_json }}"
                                        >عرض الكلمات</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                {{ $customers->appends(['tab' => 'customers'])->links() }}
            @endif
        </div>
    @endif

    @if ($tab === 'queries')
        <form method="GET" class="d-flex flex-wrap gap-2 mb-3">
            <input type="hidden" name="tab" value="queries">
            <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control" style="max-width: 240px" placeholder="ابحث عن كلمة">
            <select name="match" class="form-select" style="max-width: 220px">
                <option value="all" @selected(($filters['match'] ?? 'all') === 'all')">كل الكلمات</option>
                <option value="found" @selected(($filters['match'] ?? '') === 'found')">كلمات بمنتج موجود</option>
                <option value="missing" @selected(($filters['match'] ?? '') === 'missing')">كلمات بدون منتج</option>
            </select>
            <button class="btn btn-outline-success rounded-pill">{{ $strings::FILTER }}</button>
        </form>

        <div class="page-card p-4">
            @if ($queries->isEmpty())
                <x-admin.empty-state icon="bi-chat-square-text" message="لا توجد كلمات بحث مطابقة لهذا الفلتر." />
            @else
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>الكلمة</th>
                                <th>مرات البحث</th>
                                <th>الحالة</th>
                                <th>المنتج</th>
                                <th>آخر بحث</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($queries as $row)
                                @php
                                    $product = $productsById->get((int) ($row->matched_product_id ?? 0));
                                    $found = (int) ($row->matched_product_id ?? 0) > 0 || (int) ($row->results_count ?? 0) > 0;
                                @endphp
                                <tr>
                                    <td class="fw-semibold">{{ $row->query }}</td>
                                    <td>{{ $row->hits }}</td>
                                    <td>
                                        @if ($found)
                                            <span class="badge badge-soft">موجود</span>
                                        @else
                                            <span class="badge badge-soft">غير موجود</span>
                                        @endif
                                    </td>
                                    <td>{{ $product?->name ?? '—' }}</td>
                                    <td>{{ \Illuminate\Support\Carbon::parse($row->last_searched_at)->timezone(config('app.timezone'))->format('Y-m-d H:i') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                {{ $queries->appends(['tab' => 'queries', 'match' => $filters['match'] ?? 'all', 'q' => $filters['q'] ?? ''])->links() }}
            @endif
        </div>
    @endif

    <div class="modal fade" id="searchPhraseModal" tabindex="-1" aria-hidden="true" @if ($openPhraseModal) data-open="1" @endif>
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content detail-modal">
                <form
                    method="POST"
                    action="{{ old('form') === 'phrase' && old('editing_id') ? route('admin.search-placeholders.update', old('editing_id')) : route('admin.search-placeholders.store') }}"
                    id="searchPhraseForm"
                    data-store="{{ route('admin.search-placeholders.store') }}"
                    data-update-base="{{ url('/admin/search-placeholders') }}"
                    data-title-create="{{ $strings::ADD_SEARCH_PLACEHOLDER }}"
                    data-title-edit="{{ $strings::EDIT_SEARCH_PLACEHOLDER }}"
                >
                    @csrf
                    <input type="hidden" name="_method" value="{{ old('form') === 'phrase' && old('editing_id') ? 'PUT' : 'POST' }}" data-http-method>
                    <input type="hidden" name="form" value="phrase">
                    <input type="hidden" name="editing_id" value="{{ old('form') === 'phrase' ? old('editing_id') : '' }}" data-editing-id>
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold" data-phrase-title>{{ old('form') === 'phrase' && old('editing_id') ? $strings::EDIT_SEARCH_PLACEHOLDER : $strings::ADD_SEARCH_PLACEHOLDER }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ $strings::CLOSE }}"></button>
                    </div>
                    <div class="modal-body">
                        @include('admin.search-placeholders._form', ['placeholder' => $modalPlaceholder, 'useOld' => old('form') === 'phrase'])
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-brand">{{ $strings::SAVE }}</button>
                        <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">{{ $strings::CANCEL }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="searchSmartModal" tabindex="-1" aria-hidden="true" @if ($openSmartModal) data-open="1" @endif>
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content detail-modal">
                <form
                    method="POST"
                    action="{{ old('form') === 'smart' && old('editing_id') ? route('admin.search-smart-suggestions.update', old('editing_id')) : route('admin.search-smart-suggestions.store') }}"
                    id="searchSmartForm"
                    data-store="{{ route('admin.search-smart-suggestions.store') }}"
                    data-update-base="{{ url('/admin/search-smart-suggestions') }}"
                    data-title-create="{{ $strings::ADD_SEARCH_SMART }}"
                    data-title-edit="{{ $strings::EDIT_SEARCH_SMART }}"
                >
                    @csrf
                    <input type="hidden" name="_method" value="{{ old('form') === 'smart' && old('editing_id') ? 'PUT' : 'POST' }}" data-http-method>
                    <input type="hidden" name="form" value="smart">
                    <input type="hidden" name="editing_id" value="{{ old('form') === 'smart' ? old('editing_id') : '' }}" data-editing-id>
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold" data-smart-title>{{ old('form') === 'smart' && old('editing_id') ? $strings::EDIT_SEARCH_SMART : $strings::ADD_SEARCH_SMART }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ $strings::CLOSE }}"></button>
                    </div>
                    <div class="modal-body">
                        @include('admin.search-placeholders._discovery_form', [
                            'item' => $modalSmart,
                            'useOld' => old('form') === 'smart',
                            'checkboxId' => 'smart_is_active',
                            'hint' => 'يظهر في سكشن اقتراحات ذكية داخل التطبيق.',
                        ])
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-brand">{{ $strings::SAVE }}</button>
                        <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">{{ $strings::CANCEL }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="searchTrendingModal" tabindex="-1" aria-hidden="true" @if ($openTrendingModal) data-open="1" @endif>
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content detail-modal">
                <form
                    method="POST"
                    action="{{ old('form') === 'trending' && old('editing_id') ? route('admin.search-trending-pins.update', old('editing_id')) : route('admin.search-trending-pins.store') }}"
                    id="searchTrendingForm"
                    data-store="{{ route('admin.search-trending-pins.store') }}"
                    data-update-base="{{ url('/admin/search-trending-pins') }}"
                    data-title-create="{{ $strings::ADD_SEARCH_TRENDING_PIN }}"
                    data-title-edit="{{ $strings::EDIT_SEARCH_TRENDING_PIN }}"
                >
                    @csrf
                    <input type="hidden" name="_method" value="{{ old('form') === 'trending' && old('editing_id') ? 'PUT' : 'POST' }}" data-http-method>
                    <input type="hidden" name="form" value="trending">
                    <input type="hidden" name="editing_id" value="{{ old('form') === 'trending' ? old('editing_id') : '' }}" data-editing-id>
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold" data-trending-title>{{ old('form') === 'trending' && old('editing_id') ? $strings::EDIT_SEARCH_TRENDING_PIN : $strings::ADD_SEARCH_TRENDING_PIN }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ $strings::CLOSE }}"></button>
                    </div>
                    <div class="modal-body">
                        @include('admin.search-placeholders._discovery_form', [
                            'item' => $modalTrending,
                            'useOld' => old('form') === 'trending',
                            'checkboxId' => 'trending_is_active',
                            'hint' => 'تظهر في سكشن الأكثر بحثاً حولك داخل التطبيق.',
                        ])
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-brand">{{ $strings::SAVE }}</button>
                        <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">{{ $strings::CANCEL }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="customerSearchLogsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content detail-modal">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" data-customer-logs-title>كلمات البحث</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ $strings::CLOSE }}"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th>الكلمة</th>
                                    <th>التاريخ</th>
                                    <th>المنتج</th>
                                </tr>
                            </thead>
                            <tbody data-customer-logs-body>
                                <tr><td colspan="3" class="text-muted">لا توجد كلمات.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.admin>
