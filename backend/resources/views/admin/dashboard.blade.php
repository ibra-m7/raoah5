<x-layouts.admin :title="$title">
    <div class="row g-3 mb-4">
        @foreach ($stats as $stat)
            <x-admin.stat-card :icon="$stat['icon']" :label="$stat['label']" :value="$stat['value']" />
        @endforeach
    </div>

    <h2 class="h5 fw-bold mb-3">إدارة ما يظهر في التطبيق</h2>
    <div class="row g-3 mb-4">
        @foreach ($shortcuts as $shortcut)
            <div class="col-md-6 col-xl-3">
                <a href="{{ route($shortcut['route']) }}" class="quick-link">
                    <i class="bi {{ $shortcut['icon'] }}"></i>
                    <strong>{{ $shortcut['label'] }}</strong>
                    <small>{{ $shortcut['hint'] }}</small>
                </a>
            </div>
        @endforeach
    </div>

    <div class="page-card p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h5 mb-0">{{ $strings::STAT_PENDING_ORDERS }}</h2>
            <a href="{{ route('admin.orders.index') }}" class="btn btn-sm btn-brand">{{ $strings::VIEW_ALL }}</a>
        </div>

        @if ($pendingOrders->isEmpty())
            <x-admin.empty-state icon="bi-bag" />
        @else
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>رقم الطلب</th>
                            <th>العميل</th>
                            <th>الإجمالي</th>
                            <th>التاريخ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($pendingOrders as $order)
                            <tr>
                                <td class="fw-bold">{{ $order->order_number }}</td>
                                <td>{{ $order->user?->name }}</td>
                                <td>{{ number_format((float) $order->total, 2) }} {{ $strings::CURRENCY }}</td>
                                <td>{{ $order->created_at?->format('Y-m-d') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="page-card p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h5 mb-0">آخر التحديثات</h2>
            <span class="small text-muted">تتحدّث تلقائياً</span>
        </div>
        @if ($liveEvents->isEmpty())
            <p class="text-muted mb-0">ستظهر هنا الطلبات الجديدة وقبول وتسليم الموصل.</p>
        @else
            <div class="d-flex flex-column gap-2">
                @foreach ($liveEvents as $event)
                    <div class="d-flex justify-content-between gap-3 p-3 rounded-4" style="background: #F0FAF3">
                        <div>
                            <strong>{{ $event->title }}</strong>
                            <div class="small text-muted">{{ $event->body }}</div>
                        </div>
                        <small class="text-muted">{{ $event->created_at?->format('H:i') }}</small>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-layouts.admin>
