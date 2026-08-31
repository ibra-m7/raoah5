@php
    $tab = old('active_tab', $tab ?? 'policy');
    $openRuleModal = $errors->any() && old('form') === 'rule';
    $openPerkModal = $errors->any() && old('form') === 'perk';
    $openSlotModal = $errors->any() && old('form') === 'slot';
    $openPickupSlotModal = $errors->any() && old('form') === 'pickup_slot';
    if ($errors->any() && in_array(old('active_tab'), ['policy', 'slots', 'pickup_slots', 'rules', 'perks'], true)) {
        $tab = old('active_tab');
    } elseif ($openRuleModal) {
        $tab = 'rules';
    } elseif ($openPerkModal) {
        $tab = 'perks';
    } elseif ($openPickupSlotModal || ($errors->hasAny(['interval_minutes']) && old('form') === 'pickup_slot')) {
        $tab = 'pickup_slots';
    } elseif ($openSlotModal || $errors->hasAny(['weekday', 'weekdays', 'weekdays.*', 'start_time', 'end_time'])) {
        $tab = 'slots';
    }
@endphp

<x-layouts.admin :title="$title">
    <x-admin.page-head :title="$title" />

    @if (! $settings['delivery_store_lat'] || ! $settings['delivery_store_lng'])
        <div class="alert alert-warning rounded-4">لم يُحدد موقع المتجر بعد. بدون الإحداثيات تُستخدم الرسوم الاحتياطية.</div>
    @endif

    <div class="page-card p-0 overflow-hidden">
        <ul class="nav settings-tabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button type="button" class="nav-link {{ $tab === 'policy' ? 'active' : '' }}" id="tab-policy" data-bs-toggle="tab" data-bs-target="#pane-policy" role="tab" aria-controls="pane-policy" aria-selected="{{ $tab === 'policy' ? 'true' : 'false' }}">
                    <i class="bi bi-sliders"></i>
                    السياسة
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button type="button" class="nav-link {{ $tab === 'slots' ? 'active' : '' }}" id="tab-slots" data-bs-toggle="tab" data-bs-target="#pane-slots" role="tab" aria-controls="pane-slots" aria-selected="{{ $tab === 'slots' ? 'true' : 'false' }}">
                    <i class="bi bi-clock-history"></i>
                    أوقات التوصيل
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button type="button" class="nav-link {{ $tab === 'pickup_slots' ? 'active' : '' }}" id="tab-pickup-slots" data-bs-toggle="tab" data-bs-target="#pane-pickup-slots" role="tab" aria-controls="pane-pickup-slots" aria-selected="{{ $tab === 'pickup_slots' ? 'true' : 'false' }}">
                    <i class="bi bi-bag-check"></i>
                    أوقات التجهيز
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button type="button" class="nav-link {{ $tab === 'rules' ? 'active' : '' }}" id="tab-rules" data-bs-toggle="tab" data-bs-target="#pane-rules" role="tab" aria-controls="pane-rules" aria-selected="{{ $tab === 'rules' ? 'true' : 'false' }}">
                    <i class="bi bi-signpost-split"></i>
                    شرائح المسافة
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button type="button" class="nav-link {{ $tab === 'perks' ? 'active' : '' }}" id="tab-perks" data-bs-toggle="tab" data-bs-target="#pane-perks" role="tab" aria-controls="pane-perks" aria-selected="{{ $tab === 'perks' ? 'true' : 'false' }}">
                    <i class="bi bi-gift"></i>
                    خصومات الولاء
                </button>
            </li>
        </ul>

        <div class="tab-content p-4 p-md-5">
            <div class="tab-pane fade {{ $tab === 'policy' ? 'show active' : '' }}" id="pane-policy" role="tabpanel" aria-labelledby="tab-policy" tabindex="0">
                <h2 class="settings-pane-title">سياسة التوصيل</h2>
                <p class="settings-pane-lead">موقع المتجر وحدود التوصيل والرسوم الاحتياطية كما تظهر في حساب الطلب.</p>

                <form method="POST" action="{{ route('admin.delivery.settings.update') }}">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="active_tab" value="policy">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="delivery_enabled" value="1" id="delivery_enabled" @checked(old('delivery_enabled', $settings['delivery_enabled']))>
                                <label class="form-check-label" for="delivery_enabled">تفعيل حساب التوصيل حسب المسافة</label>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="delivery_first_order_free" value="1" id="delivery_first_order_free" @checked(old('delivery_first_order_free', $settings['delivery_first_order_free']))>
                                <label class="form-check-label" for="delivery_first_order_free">أول طلب في التطبيق مجاني</label>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="delivery_hide_subtitle" value="1" id="delivery_hide_subtitle" @checked(old('delivery_hide_subtitle', $settings['delivery_hide_subtitle']))>
                                <label class="form-check-label" for="delivery_hide_subtitle">إخفاء أي نص تحت «التوصيل» في ملخص الدفع</label>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="delivery_notes_enabled" value="1" id="delivery_notes_enabled" @checked(old('delivery_notes_enabled', $settings['delivery_notes_enabled']))>
                                <label class="form-check-label" for="delivery_notes_enabled">تفعيل ملاحظات التوصيل في التطبيق</label>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="pickup_enabled" value="1" id="pickup_enabled" @checked(old('pickup_enabled', $settings['pickup_enabled']))>
                                <label class="form-check-label" for="pickup_enabled">تفعيل الاستلام من المركز في التطبيق</label>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">ملاحظة عامة للتوصيل</label>
                        <textarea name="delivery_general_note" rows="2" class="form-control @error('delivery_general_note') is-invalid @enderror" placeholder="تظهر تحت التوصيل عند تفعيل الملاحظات">{{ old('delivery_general_note', $settings['delivery_general_note']) }}</textarea>
                        @error('delivery_general_note') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-hint">تظهر مع ملاحظات شرائح المسافة المفعّلة (إن وُجدت).</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">عنوان المتجر</label>
                        <input type="text" name="delivery_store_address" value="{{ old('delivery_store_address', $settings['delivery_store_address']) }}" class="form-control @error('delivery_store_address') is-invalid @enderror">
                        @error('delivery_store_address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">خط عرض المتجر</label>
                            <input type="number" step="0.0000001" name="delivery_store_lat" id="delivery_store_lat" value="{{ old('delivery_store_lat', $settings['delivery_store_lat']) }}" class="form-control @error('delivery_store_lat') is-invalid @enderror" dir="ltr" placeholder="24.7136">
                            @error('delivery_store_lat') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">خط طول المتجر</label>
                            <input type="number" step="0.0000001" name="delivery_store_lng" id="delivery_store_lng" value="{{ old('delivery_store_lng', $settings['delivery_store_lng']) }}" class="form-control @error('delivery_store_lng') is-invalid @enderror" dir="ltr" placeholder="46.6753">
                            @error('delivery_store_lng') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">لصق إحداثيات من خرائط جوجل</label>
                        <div class="input-group">
                            <input type="text" id="delivery_coords_paste" class="form-control" dir="ltr" placeholder="24.7136, 46.6753">
                            <button type="button" class="btn btn-outline-success" id="delivery_coords_apply">تعبئة</button>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">الحد الأقصى للتوصيل (كم)</label>
                            <input type="number" step="0.1" min="0" name="delivery_max_km" value="{{ old('delivery_max_km', $settings['delivery_max_km']) }}" class="form-control @error('delivery_max_km') is-invalid @enderror">
                            @error('delivery_max_km') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div class="form-hint">فارغ أو 0 = بدون حد.</div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">رسوم احتياطية ({{ $strings::CURRENCY }})</label>
                            <input type="number" step="0.01" min="0" name="delivery_fallback_fee" value="{{ old('delivery_fallback_fee', $settings['delivery_fallback_fee']) }}" class="form-control @error('delivery_fallback_fee') is-invalid @enderror" required>
                            @error('delivery_fallback_fee') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div class="form-hint">تُستخدم إذا تعذّر حساب المسافة.</div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">توصيل مجاني إذا بلغ الطلب ({{ $strings::CURRENCY }})</label>
                            <input type="number" step="0.01" min="0" name="free_shipping_threshold" value="{{ old('free_shipping_threshold', $settings['free_shipping_threshold']) }}" class="form-control @error('free_shipping_threshold') is-invalid @enderror">
                            @error('free_shipping_threshold') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div class="form-hint">0 لتعطيل الشرط.</div>
                        </div>
                    </div>
                    <button class="btn btn-brand">{{ $strings::SAVE }}</button>
                </form>
            </div>

            <div class="tab-pane fade {{ $tab === 'slots' ? 'show active' : '' }}" id="pane-slots" role="tabpanel" aria-labelledby="tab-slots" tabindex="0">
                <h2 class="settings-pane-title">أوقات التوصيل</h2>
                <p class="settings-pane-lead">حدّد أيام الدوام وفترات التوصيل المتاحة. الأيام بدون فترات نشطة لن تظهر للعميل عند اختيار وقت آخر.</p>

                <div class="mb-4 p-3 rounded-4 border bg-white">
                    <h3 class="h6 fw-bold mb-3">إضافة فترة للأيام المحددة</h3>
                    <form method="POST" action="{{ route('admin.delivery.slots.store') }}">
                        @csrf
                        <input type="hidden" name="active_tab" value="slots">
                        <div class="mb-3">
                            <label class="form-label d-block">أيام الدوام</label>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach ($weekdayNames as $value => $label)
                                    <div class="form-check form-check-inline m-0">
                                        <input
                                            class="btn-check"
                                            type="checkbox"
                                            name="weekdays[]"
                                            value="{{ $value }}"
                                            id="weekday_{{ $value }}"
                                            @checked(collect(old('weekdays', []))->map(fn ($v) => (string) $v)->contains((string) $value))
                                        >
                                        <label class="btn btn-outline-success rounded-pill btn-sm" for="weekday_{{ $value }}">{{ $label }}</label>
                                    </div>
                                @endforeach
                            </div>
                            @error('weekdays') <div class="text-danger small mt-2">{{ $message }}</div> @enderror
                            @error('weekdays.*') <div class="text-danger small mt-2">{{ $message }}</div> @enderror
                            @error('weekday') <div class="text-danger small mt-2">{{ $message }}</div> @enderror
                        </div>
                        <div class="row g-2 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label">من</label>
                                <input type="time" name="start_time" value="{{ old('start_time', '10:00') }}" class="form-control @error('start_time') is-invalid @enderror" required>
                                @error('start_time') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">إلى</label>
                                <input type="time" name="end_time" value="{{ old('end_time', '12:00') }}" class="form-control @error('end_time') is-invalid @enderror" required>
                                @error('end_time') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ $strings::SORT_ORDER }}</label>
                                <input type="number" min="0" name="sort_order" value="{{ old('sort_order', 0) }}" class="form-control">
                            </div>
                            <div class="col-md-2">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="slot_is_active" @checked(old('is_active', true))>
                                    <label class="form-check-label" for="slot_is_active">ظاهر في التطبيق</label>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <button class="btn btn-brand w-100">{{ $strings::ADD_DELIVERY_SLOT }}</button>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="row g-3">
                    @foreach ($weekdayNames as $weekday => $dayLabel)
                        @php $daySlots = $slotsByWeekday->get($weekday, collect()); @endphp
                        <div class="col-md-6 col-xl-4">
                            <div class="border rounded-4 h-100 p-3 bg-white">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h3 class="h6 fw-bold mb-0">{{ $dayLabel }}</h3>
                                    @if ($daySlots->where('is_active', true)->isNotEmpty())
                                        <span class="badge badge-soft">{{ $daySlots->where('is_active', true)->count() }} فترة</span>
                                    @else
                                        <span class="badge text-bg-light text-muted">بدون دوام</span>
                                    @endif
                                </div>
                                @if ($daySlots->isEmpty())
                                    <p class="text-muted small mb-0">لا توجد فترات لهذا اليوم.</p>
                                @else
                                    @php
                                        $fmtSlot = function (string $raw): string {
                                            $parts = explode(':', substr($raw, 0, 5));
                                            $h = (int) ($parts[0] ?? 0);
                                            $m = $parts[1] ?? '00';
                                            $period = $h < 12 ? 'ص' : 'م';
                                            $h12 = $h % 12;
                                            if ($h12 === 0) {
                                                $h12 = 12;
                                            }

                                            return $h12.':'.$m;
                                        };
                                        $fmtRange = function (string $start, string $end) use ($fmtSlot): string {
                                            $h = (int) explode(':', substr($start, 0, 5))[0];
                                            $suffix = $h < 12 ? 'ص' : 'م';

                                            return $fmtSlot($start).' – '.$fmtSlot($end).' '.$suffix;
                                        };
                                    @endphp
                                    <div class="d-flex flex-column gap-2">
                                        @foreach ($daySlots as $slot)
                                            <div class="d-flex align-items-center justify-content-between gap-2 p-2 rounded-3 border {{ $slot->is_active ? '' : 'opacity-60' }}">
                                                <div>
                                                    <div class="fw-bold" dir="ltr">
                                                        {{ $fmtRange((string) $slot->start_time, (string) $slot->end_time) }}
                                                    </div>
                                                    <div class="small text-muted">
                                                        @if ($slot->is_active)
                                                            {{ $strings::LIVE_IN_APP }}
                                                        @else
                                                            {{ $strings::INACTIVE }}
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="d-flex gap-1 flex-shrink-0">
                                                    <button
                                                        type="button"
                                                        class="btn btn-sm btn-outline-success rounded-pill"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#deliverySlotModal"
                                                        data-delivery-slot-edit
                                                        data-id="{{ $slot->id }}"
                                                        data-weekday="{{ $slot->weekday }}"
                                                        data-start-time="{{ \Illuminate\Support\Str::of($slot->start_time)->substr(0, 5) }}"
                                                        data-end-time="{{ \Illuminate\Support\Str::of($slot->end_time)->substr(0, 5) }}"
                                                        data-sort-order="{{ $slot->sort_order }}"
                                                        data-is-active="{{ $slot->is_active ? '1' : '0' }}"
                                                    >{{ $strings::EDIT }}</button>
                                                    <form method="POST" action="{{ route('admin.delivery.slots.destroy', $slot) }}" onsubmit="return confirm('{{ $strings::CONFIRM_DELETE }}')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button class="btn btn-sm btn-outline-danger rounded-pill">{{ $strings::DELETE }}</button>
                                                    </form>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="tab-pane fade {{ $tab === 'pickup_slots' ? 'show active' : '' }}" id="pane-pickup-slots" role="tabpanel" aria-labelledby="tab-pickup-slots" tabindex="0">
                <h2 class="settings-pane-title">أوقات التجهيز (استلام من المركز)</h2>
                <p class="settings-pane-lead">حدد نوافذ التجهيز المتاحة للعميل عند اختيار «استلم بنفسك».</p>

                <div class="border rounded-4 p-3 p-md-4 mb-4 bg-light">
                    <form method="POST" action="{{ route('admin.delivery.pickup-slots.store') }}">
                        @csrf
                        <input type="hidden" name="active_tab" value="pickup_slots">
                        <input type="hidden" name="form" value="pickup_slot">
                        <div class="mb-3">
                            <label class="form-label d-block">أيام الدوام</label>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach ($weekdayNames as $value => $label)
                                    <div class="form-check form-check-inline m-0">
                                        <input class="btn-check" type="checkbox" name="weekdays[]" value="{{ $value }}" id="pickup_weekday_{{ $value }}" @checked(collect(old('weekdays', []))->map(fn ($v) => (string) $v)->contains((string) $value))>
                                        <label class="btn btn-outline-success rounded-pill btn-sm" for="pickup_weekday_{{ $value }}">{{ $label }}</label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="row g-2 align-items-end">
                            <div class="col-md-2">
                                <label class="form-label">من</label>
                                <input type="time" name="start_time" value="{{ old('start_time', '10:00') }}" class="form-control" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">إلى</label>
                                <input type="time" name="end_time" value="{{ old('end_time', '12:00') }}" class="form-control" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">فاصل الدقائق</label>
                                <select name="interval_minutes" class="form-select">
                                    @foreach ([5, 10, 15, 30, 60] as $interval)
                                        <option value="{{ $interval }}" @selected((int) old('interval_minutes', 15) === $interval)>{{ $interval }} د</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ $strings::SORT_ORDER }}</label>
                                <input type="number" min="0" name="sort_order" value="{{ old('sort_order', 0) }}" class="form-control">
                            </div>
                            <div class="col-md-2">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="pickup_slot_is_active" @checked(old('is_active', true))>
                                    <label class="form-check-label" for="pickup_slot_is_active">ظاهر في التطبيق</label>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <button class="btn btn-brand w-100">{{ $strings::ADD_PICKUP_SLOT }}</button>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="row g-3">
                    @foreach ($weekdayNames as $weekday => $dayLabel)
                        @php $daySlots = $pickupSlotsByWeekday->get($weekday, collect()); @endphp
                        <div class="col-md-6 col-xl-4">
                            <div class="border rounded-4 h-100 p-3 bg-white">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h3 class="h6 fw-bold mb-0">{{ $dayLabel }}</h3>
                                    @if ($daySlots->where('is_active', true)->isNotEmpty())
                                        <span class="badge badge-soft">{{ $daySlots->where('is_active', true)->count() }} فترة</span>
                                    @else
                                        <span class="badge text-bg-light text-muted">بدون دوام</span>
                                    @endif
                                </div>
                                @if ($daySlots->isEmpty())
                                    <p class="text-muted small mb-0">لا توجد فترات لهذا اليوم.</p>
                                @else
                                    <div class="d-flex flex-column gap-2">
                                        @foreach ($daySlots as $slot)
                                            <div class="d-flex align-items-center justify-content-between gap-2 p-2 rounded-3 border {{ $slot->is_active ? '' : 'opacity-60' }}">
                                                <div>
                                                    <div class="fw-bold" dir="ltr">{{ \Illuminate\Support\Str::of($slot->start_time)->substr(0, 5) }} – {{ \Illuminate\Support\Str::of($slot->end_time)->substr(0, 5) }}</div>
                                                    <div class="small text-muted">كل {{ $slot->interval_minutes }} د · {{ $slot->is_active ? $strings::LIVE_IN_APP : $strings::INACTIVE }}</div>
                                                </div>
                                                <div class="d-flex gap-1 flex-shrink-0">
                                                    <button type="button" class="btn btn-sm btn-outline-success rounded-pill" data-bs-toggle="modal" data-bs-target="#pickupSlotModal"
                                                        data-pickup-slot-edit
                                                        data-id="{{ $slot->id }}"
                                                        data-weekday="{{ $slot->weekday }}"
                                                        data-start-time="{{ \Illuminate\Support\Str::of($slot->start_time)->substr(0, 5) }}"
                                                        data-end-time="{{ \Illuminate\Support\Str::of($slot->end_time)->substr(0, 5) }}"
                                                        data-interval-minutes="{{ $slot->interval_minutes }}"
                                                        data-sort-order="{{ $slot->sort_order }}"
                                                        data-is-active="{{ $slot->is_active ? '1' : '0' }}"
                                                    >{{ $strings::EDIT }}</button>
                                                    <form method="POST" action="{{ route('admin.delivery.pickup-slots.destroy', $slot) }}" onsubmit="return confirm('{{ $strings::CONFIRM_DELETE }}')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button class="btn btn-sm btn-outline-danger rounded-pill">{{ $strings::DELETE }}</button>
                                                    </form>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="tab-pane fade {{ $tab === 'rules' ? 'show active' : '' }}" id="pane-rules" role="tabpanel" aria-labelledby="tab-rules" tabindex="0">
                <div class="d-flex justify-content-between align-items-center mb-3 gap-2 flex-wrap">
                    <div>
                        <h2 class="settings-pane-title mb-1">شرائح المسافة</h2>
                        <p class="settings-pane-lead mb-0">سعّر التوصيل حسب بُعد العميل عن المتجر.</p>
                    </div>
                    <button type="button" class="btn btn-outline-success rounded-pill btn-sm" data-bs-toggle="modal" data-bs-target="#deliveryRuleModal" data-delivery-rule-create>
                        <i class="bi bi-plus-lg ms-1"></i>
                        {{ $strings::ADD_DELIVERY_RULE }}
                    </button>
                </div>
                @if ($rules->isEmpty())
                    <x-admin.empty-state icon="bi-truck" modal action="#deliveryRuleModal" :action-label="$strings::ADD_DELIVERY_RULE" data-delivery-rule-create />
                @else
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>الاسم</th>
                                    <th>المسافة</th>
                                    <th>التسعير</th>
                                    <th>{{ $strings::SORT_ORDER }}</th>
                                    <th>{{ $strings::STATUS }}</th>
                                    <th>{{ $strings::ACTIONS }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($rules as $rule)
                                    <tr>
                                        <td class="fw-bold">{{ $rule->name }}</td>
                                        <td>{{ $rule->rangeLabel() }}</td>
                                        <td>{{ $rule->priceLabel() }}</td>
                                        <td>{{ $rule->sort_order }}</td>
                                        <td>
                                            @if ($rule->is_active)
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
                                                    data-bs-target="#deliveryRuleModal"
                                                    data-delivery-rule-edit
                                                    data-id="{{ $rule->id }}"
                                                    data-name="{{ $rule->name }}"
                                                    data-min-km="{{ $rule->min_km }}"
                                                    data-max-km="{{ $rule->max_km }}"
                                                    data-pricing-type="{{ $rule->pricing_type->value }}"
                                                    data-amount="{{ $rule->amount }}"
                                                    data-per-km-mode="{{ $rule->per_km_mode->value }}"
                                                    data-sort-order="{{ $rule->sort_order }}"
                                                    data-is-active="{{ $rule->is_active ? '1' : '0' }}"
                                                    data-note="{{ e($rule->note ?? '') }}"
                                                    data-note-enabled="{{ $rule->note_enabled ? '1' : '0' }}"
                                                >{{ $strings::EDIT }}</button>
                                                <form method="POST" action="{{ route('admin.delivery.rules.destroy', $rule) }}" onsubmit="return confirm('{{ $strings::CONFIRM_DELETE }}')">
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
                @endif
            </div>

            <div class="tab-pane fade {{ $tab === 'perks' ? 'show active' : '' }}" id="pane-perks" role="tabpanel" aria-labelledby="tab-perks" tabindex="0">
                <div class="d-flex justify-content-between align-items-center mb-3 gap-2 flex-wrap">
                    <div>
                        <h2 class="settings-pane-title mb-1">خصومات الولاء للتوصيل</h2>
                        <p class="settings-pane-lead mb-0">بعد حساب المسافة: توصيل مجاني أو خصم حسب عدد طلبات العميل.</p>
                    </div>
                    <button type="button" class="btn btn-outline-success rounded-pill btn-sm" data-bs-toggle="modal" data-bs-target="#deliveryPerkModal" data-delivery-perk-create>
                        <i class="bi bi-plus-lg ms-1"></i>
                        {{ $strings::ADD_DELIVERY_PERK }}
                    </button>
                </div>
                @if ($perks->isEmpty())
                    <x-admin.empty-state icon="bi-gift" modal action="#deliveryPerkModal" :action-label="$strings::ADD_DELIVERY_PERK" data-delivery-perk-create />
                @else
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>الاسم</th>
                                    <th>الشرط</th>
                                    <th>الخصم</th>
                                    <th>{{ $strings::SORT_ORDER }}</th>
                                    <th>{{ $strings::STATUS }}</th>
                                    <th>{{ $strings::ACTIONS }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($perks as $perk)
                                    <tr>
                                        <td class="fw-bold">{{ $perk->name }}</td>
                                        <td>{{ $perk->triggerLabel() }}</td>
                                        <td>{{ $perk->rewardLabel() }}</td>
                                        <td>{{ $perk->sort_order }}</td>
                                        <td>
                                            @if ($perk->is_active)
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
                                                    data-bs-target="#deliveryPerkModal"
                                                    data-delivery-perk-edit
                                                    data-id="{{ $perk->id }}"
                                                    data-name="{{ $perk->name }}"
                                                    data-trigger-type="{{ $perk->trigger_type->value }}"
                                                    data-min-orders="{{ $perk->min_orders }}"
                                                    data-reward-type="{{ $perk->reward_type->value }}"
                                                    data-reward-value="{{ $perk->reward_value }}"
                                                    data-sort-order="{{ $perk->sort_order }}"
                                                    data-is-active="{{ $perk->is_active ? '1' : '0' }}"
                                                >{{ $strings::EDIT }}</button>
                                                <form method="POST" action="{{ route('admin.delivery.perks.destroy', $perk) }}" onsubmit="return confirm('{{ $strings::CONFIRM_DELETE }}')">
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
                @endif
            </div>
        </div>
    </div>

    @php
        $modalRule = new \App\Models\DeliveryRule([
            'is_active' => true,
            'sort_order' => 0,
            'pricing_type' => \App\Enums\DeliveryPricingType::Free,
            'per_km_mode' => \App\Enums\DeliveryPerKmMode::Entire,
            'amount' => 0,
            'min_km' => 0,
        ]);
        $modalPerk = new \App\Models\DeliveryPerk([
            'is_active' => true,
            'sort_order' => 0,
            'trigger_type' => \App\Enums\DeliveryPerkTrigger::MinOrders,
            'reward_type' => \App\Enums\DeliveryPerkReward::Free,
            'min_orders' => 4,
            'reward_value' => 0,
        ]);
    @endphp

    <div class="modal fade" id="deliveryRuleModal" tabindex="-1" aria-hidden="true" @if ($openRuleModal) data-open="1" @endif>
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content detail-modal">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" data-rule-title>{{ old('form') === 'rule' && old('editing_id') ? $strings::EDIT_DELIVERY_RULE : $strings::ADD_DELIVERY_RULE }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ $strings::CLOSE }}"></button>
                </div>
                <form
                    method="POST"
                    action="{{ old('form') === 'rule' && old('editing_id') ? route('admin.delivery.rules.update', old('editing_id')) : route('admin.delivery.rules.store') }}"
                    id="deliveryRuleForm"
                    class="modal-scroll-form"
                    data-store="{{ route('admin.delivery.rules.store') }}"
                    data-update-base="{{ url('/admin/delivery/rules') }}"
                    data-title-create="{{ $strings::ADD_DELIVERY_RULE }}"
                    data-title-edit="{{ $strings::EDIT_DELIVERY_RULE }}"
                >
                    @csrf
                    <input type="hidden" name="_method" value="{{ old('form') === 'rule' && old('editing_id') ? 'PUT' : 'POST' }}" data-http-method>
                    <input type="hidden" name="form" value="rule">
                    <input type="hidden" name="active_tab" value="rules">
                    <input type="hidden" name="editing_id" value="{{ old('form') === 'rule' ? old('editing_id') : '' }}" data-editing-id>
                    <div class="modal-body">
                        @include('admin.delivery._form', ['rule' => $modalRule])
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-brand">{{ $strings::SAVE }}</button>
                        <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">{{ $strings::CANCEL }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deliveryPerkModal" tabindex="-1" aria-hidden="true" @if ($openPerkModal) data-open="1" @endif>
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content detail-modal">
                <form
                    method="POST"
                    action="{{ old('form') === 'perk' && old('editing_id') ? route('admin.delivery.perks.update', old('editing_id')) : route('admin.delivery.perks.store') }}"
                    id="deliveryPerkForm"
                    data-store="{{ route('admin.delivery.perks.store') }}"
                    data-update-base="{{ url('/admin/delivery/perks') }}"
                    data-title-create="{{ $strings::ADD_DELIVERY_PERK }}"
                    data-title-edit="{{ $strings::EDIT_DELIVERY_PERK }}"
                >
                    @csrf
                    <input type="hidden" name="_method" value="{{ old('form') === 'perk' && old('editing_id') ? 'PUT' : 'POST' }}" data-http-method>
                    <input type="hidden" name="form" value="perk">
                    <input type="hidden" name="active_tab" value="perks">
                    <input type="hidden" name="editing_id" value="{{ old('form') === 'perk' ? old('editing_id') : '' }}" data-editing-id>
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold" data-perk-title>{{ old('form') === 'perk' && old('editing_id') ? $strings::EDIT_DELIVERY_PERK : $strings::ADD_DELIVERY_PERK }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ $strings::CLOSE }}"></button>
                    </div>
                    <div class="modal-body">
                        @include('admin.delivery._perk-form', ['perk' => $modalPerk])
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-brand">{{ $strings::SAVE }}</button>
                        <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">{{ $strings::CANCEL }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deliverySlotModal" tabindex="-1" aria-hidden="true" @if ($openSlotModal) data-open="1" @endif>
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content detail-modal">
                <form
                    method="POST"
                    action="{{ old('form') === 'slot' && old('editing_id') ? route('admin.delivery.slots.update', old('editing_id')) : route('admin.delivery.slots.store') }}"
                    id="deliverySlotForm"
                    data-update-base="{{ url('/admin/delivery/slots') }}"
                >
                    @csrf
                    <input type="hidden" name="_method" value="PUT" data-http-method>
                    <input type="hidden" name="form" value="slot">
                    <input type="hidden" name="active_tab" value="slots">
                    <input type="hidden" name="editing_id" value="{{ old('form') === 'slot' ? old('editing_id') : '' }}" data-editing-id>
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">تعديل فترة التوصيل</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ $strings::CLOSE }}"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">اليوم</label>
                            <select name="weekday" class="form-select" required data-slot-weekday>
                                @foreach ($weekdayNames as $value => $label)
                                    <option value="{{ $value }}" @selected((string) old('weekday') === (string) $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">من</label>
                                <input type="time" name="start_time" class="form-control" required data-slot-start value="{{ old('start_time', '10:00') }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">إلى</label>
                                <input type="time" name="end_time" class="form-control" required data-slot-end value="{{ old('end_time', '12:00') }}">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ $strings::SORT_ORDER }}</label>
                                <input type="number" min="0" name="sort_order" class="form-control" data-slot-sort value="{{ old('sort_order', 0) }}">
                            </div>
                            <div class="col-md-6 mb-3 d-flex align-items-end">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="slot_edit_is_active" data-slot-active @checked(old('is_active', true))>
                                    <label class="form-check-label" for="slot_edit_is_active">ظاهر في التطبيق</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-brand">{{ $strings::SAVE }}</button>
                        <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">{{ $strings::CANCEL }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="pickupSlotModal" tabindex="-1" aria-hidden="true" @if ($openPickupSlotModal) data-open="1" @endif>
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content detail-modal">
                <form method="POST" action="{{ route('admin.delivery.pickup-slots.store') }}" id="pickupSlotForm" data-update-base="{{ url('/admin/delivery/pickup-slots') }}">
                    @csrf
                    <input type="hidden" name="_method" value="PUT" data-http-method>
                    <input type="hidden" name="form" value="pickup_slot">
                    <input type="hidden" name="active_tab" value="pickup_slots">
                    <input type="hidden" name="editing_id" value="" data-editing-id>
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">تعديل فترة التجهيز</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ $strings::CLOSE }}"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">اليوم</label>
                            <select name="weekday" class="form-select" required data-slot-weekday>
                                @foreach ($weekdayNames as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">من</label>
                                <input type="time" name="start_time" class="form-control" required data-slot-start>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">إلى</label>
                                <input type="time" name="end_time" class="form-control" required data-slot-end>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">فاصل الدقائق</label>
                                <select name="interval_minutes" class="form-select" data-slot-interval>
                                    @foreach ([5, 10, 15, 30, 60] as $interval)
                                        <option value="{{ $interval }}">{{ $interval }} د</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">{{ $strings::SORT_ORDER }}</label>
                                <input type="number" min="0" name="sort_order" class="form-control" data-slot-sort>
                            </div>
                            <div class="col-md-4 mb-3 d-flex align-items-end">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="pickup_slot_edit_is_active" data-slot-active checked>
                                    <label class="form-check-label" for="pickup_slot_edit_is_active">ظاهر في التطبيق</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-brand">{{ $strings::SAVE }}</button>
                        <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">{{ $strings::CANCEL }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-layouts.admin>
