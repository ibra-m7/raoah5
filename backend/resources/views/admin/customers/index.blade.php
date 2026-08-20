<x-layouts.admin :title="$title">
    <x-admin.page-head
        :title="$title"
        subtitle="اضغط على العميل لعرض مواقعه وعدد طلباته وملخص حسابه"
    />

    <div class="d-flex justify-content-between align-items-center mb-3">
        <form method="GET" class="d-flex flex-wrap gap-2">
            <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control" style="max-width: 280px" placeholder="ابحث بالاسم أو الجوال أو البريد">
            <button class="btn btn-outline-success rounded-pill">{{ $strings::FILTER }}</button>
        </form>
    </div>

    <div class="page-card p-4">
        @if ($customers->isEmpty())
            <x-admin.empty-state icon="bi-people" />
        @else
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>العميل</th>
                            <th>الموقع</th>
                            <th>الجوال</th>
                            <th>العناوين</th>
                            <th>الطلبات</th>
                            <th>إجمالي المشتريات</th>
                            <th>آخر طلب</th>
                            <th>{{ $strings::ACTIONS }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($customers as $customer)
                            @php
                                $phone = \App\Http\Controllers\Admin\CustomerController::phoneDisplay($customer->phone);
                                $spent = (float) ($customer->orders_sum_total ?? 0);
                                $lastOrderAt = \App\Http\Controllers\Admin\CustomerController::lastOrderLabel($customer->orders_max_created_at);
                                $defaultAddress = $customer->addresses->firstWhere('is_default', true) ?? $customer->addresses->first();
                                $orders = $recentOrders->get($customer->id, collect());
                                $customerDetail = [
                                    'title' => $customer->name,
                                    'badges' => array_values(array_filter([
                                        $customer->phone_verified_at ? 'موثّق' : null,
                                        $customer->addresses_count ? $customer->addresses_count.' عنوان' : 'بدون عنوان',
                                        $customer->orders_count.' طلب',
                                    ])),
                                    'fields' => array_values(array_filter([
                                        ['label' => 'الجوال', 'value' => $phone],
                                        ['label' => 'البريد', 'value' => $customer->email],
                                        ['label' => 'تاريخ التسجيل', 'value' => $customer->created_at?->format('Y-m-d H:i')],
                                        ['label' => 'عدد الطلبات', 'value' => (string) $customer->orders_count],
                                        ['label' => 'إجمالي المشتريات', 'value' => number_format($spent, 2).' '.$strings::CURRENCY],
                                        ['label' => 'متوسط الطلب', 'value' => $customer->orders_count ? number_format($spent / max(1, $customer->orders_count), 2).' '.$strings::CURRENCY : '—'],
                                        ['label' => 'آخر طلب', 'value' => $lastOrderAt],
                                        array_filter([
                                            'label' => 'العنوان الحالي',
                                            'value' => $defaultAddress?->label,
                                            'map_url' => $defaultAddress?->mapsUrl(),
                                        ]),
                                    ], fn ($row) => filled($row['value'] ?? null))),
                                    'blocks' => array_values(array_filter([
                                        [
                                            'label' => 'عناوين التوصيل',
                                            'cards' => $customer->addresses->map(function ($address) {
                                                return [
                                                    'title' => $address->label.($address->is_default ? ' · افتراضي' : ''),
                                                    'text' => $address->displayLine() ?: 'بدون وصف',
                                                    'meta' => $address->orders_count.' طلب من هذا الموقع',
                                                    'badge' => $address->is_default ? 'التوصيل الحالي' : null,
                                                    'map_url' => $address->mapsUrl(),
                                                ];
                                            })->values()->all(),
                                        ],
                                        [
                                            'label' => 'آخر الطلبات',
                                            'cards' => $orders->map(function ($order) use ($strings) {
                                                return [
                                                    'title' => $order->order_number,
                                                    'text' => ($order->status?->label() ?? '').' · '.number_format((float) $order->total, 2).' '.$strings::CURRENCY,
                                                    'meta' => $order->created_at?->format('Y-m-d H:i'),
                                                ];
                                            })->values()->all(),
                                        ],
                                    ], fn ($block) => ! empty($block['cards'] ?? []))),
                                ];
                            @endphp
                            <tr>
                                <td>
                                    <button type="button" class="entity-open" data-detail='@json($customerDetail)'>
                                        <span class="customer-avatar">{{ mb_substr($customer->name, 0, 1) }}</span>
                                        <span class="entity-open-text">
                                            <strong>{{ $customer->name }}</strong>
                                            <small>{{ $defaultAddress?->label ?? 'لم يحدد عنواناً بعد' }}</small>
                                        </span>
                                    </button>
                                </td>
                                <td>
                                    @if ($defaultAddress?->mapsUrl())
                                        <a class="map-link" href="{{ $defaultAddress->mapsUrl() }}" target="_blank" rel="noopener noreferrer">
                                            <i class="bi bi-geo-alt-fill"></i>
                                            <span>{{ $defaultAddress->label ?: 'عرض على الخريطة' }}</span>
                                        </a>
                                    @else
                                        <span class="text-muted">لم يحدد موقعاً</span>
                                    @endif
                                </td>
                                <td dir="ltr">{{ $phone }}</td>
                                <td>
                                    <span class="badge badge-soft">{{ $customer->addresses_count }}</span>
                                </td>
                                <td>
                                    <span class="badge badge-soft">{{ $customer->orders_count }}</span>
                                </td>
                                <td class="fw-bold">{{ number_format($spent, 2) }} {{ $strings::CURRENCY }}</td>
                                <td>{{ $lastOrderAt }}</td>
                                <td>
                                    <form method="POST" action="{{ route('admin.customers.destroy', $customer) }}" onsubmit="return confirm(@js($strings::CONFIRM_DELETE_CUSTOMER))">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger rounded-pill" type="submit">
                                            {{ $strings::DELETE }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $customers->links() }}
        @endif
    </div>
</x-layouts.admin>
