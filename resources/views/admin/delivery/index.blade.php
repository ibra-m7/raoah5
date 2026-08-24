<x-layouts.admin :title="$title">
    <x-admin.page-head
        :title="$title"
        create-modal="#deliveryRuleModal"
        :create-label="$strings::ADD_DELIVERY_RULE"
        data-delivery-rule-create
    />

    @if (! $settings['delivery_store_lat'] || ! $settings['delivery_store_lng'])
        <div class="alert alert-warning rounded-4">لم يُحدد موقع المتجر بعد. بدون الإحداثيات تُستخدم الرسوم الاحتياطية.</div>
    @endif

    <div class="page-card p-4 p-md-5 mb-4">
        <h2 class="h5 mb-3">سياسة التوصيل</h2>
        <form method="POST" action="{{ route('admin.delivery.settings.update') }}">
            @csrf
            @method('PUT')
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

    <div class="page-card p-4 mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h5 mb-0">شرائح المسافة</h2>
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

    <div class="page-card p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h5 mb-0">خصومات الولاء للتوصيل</h2>
            <button type="button" class="btn btn-outline-success rounded-pill btn-sm" data-bs-toggle="modal" data-bs-target="#deliveryPerkModal" data-delivery-perk-create>
                <i class="bi bi-plus-lg ms-1"></i>
                {{ $strings::ADD_DELIVERY_PERK }}
            </button>
        </div>
        <p class="text-muted small">بعد حساب المسافة: توصيل مجاني أو خصم حسب عدد طلبات العميل. عطّل العرض دون حذفه متى شئت.</p>
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
        $openRuleModal = $errors->any() && old('form') === 'rule';
        $openPerkModal = $errors->any() && old('form') === 'perk';
    @endphp

    <div class="modal fade" id="deliveryRuleModal" tabindex="-1" aria-hidden="true" @if ($openRuleModal) data-open="1" @endif>
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content detail-modal">
                <form
                    method="POST"
                    action="{{ old('form') === 'rule' && old('editing_id') ? route('admin.delivery.rules.update', old('editing_id')) : route('admin.delivery.rules.store') }}"
                    id="deliveryRuleForm"
                    data-store="{{ route('admin.delivery.rules.store') }}"
                    data-update-base="{{ url('/admin/delivery/rules') }}"
                    data-title-create="{{ $strings::ADD_DELIVERY_RULE }}"
                    data-title-edit="{{ $strings::EDIT_DELIVERY_RULE }}"
                >
                    @csrf
                    <input type="hidden" name="_method" value="{{ old('form') === 'rule' && old('editing_id') ? 'PUT' : 'POST' }}" data-http-method>
                    <input type="hidden" name="form" value="rule">
                    <input type="hidden" name="editing_id" value="{{ old('form') === 'rule' ? old('editing_id') : '' }}" data-editing-id>
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold" data-rule-title>{{ old('form') === 'rule' && old('editing_id') ? $strings::EDIT_DELIVERY_RULE : $strings::ADD_DELIVERY_RULE }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ $strings::CLOSE }}"></button>
                    </div>
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
</x-layouts.admin>
