<x-layouts.admin :title="$title">
    <div class="page-card p-4 p-md-5 mb-4">
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="p-3 rounded-4" style="background: #E8F8EC">
                    <div class="text-muted small">الطلبات المسلّمة</div>
                    <div class="fs-3 fw-bold">{{ $courier->delivered_orders_count ?? 0 }}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-3 rounded-4" style="background: #E8F8EC">
                    <div class="text-muted small">تحصيل الدفع عند الاستلام</div>
                    <div class="fs-4 fw-bold">{{ number_format($summary['collected'], 2) }} {{ $strings::CURRENCY }}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-3 rounded-4" style="background: #FDECEC">
                    <div class="text-muted small">{{ $strings::COURIER_OWES }}</div>
                    <div class="fs-4 fw-bold">{{ number_format($summary['owes'], 2) }} {{ $strings::CURRENCY }}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-3 rounded-4" style="background: #E8F8EC">
                    <div class="text-muted small">{{ $strings::COURIER_OWED }}</div>
                    <div class="fs-4 fw-bold">{{ number_format($summary['owed'], 2) }} {{ $strings::CURRENCY }}</div>
                </div>
            </div>
        </div>

        @if ($summary['owes'] > 0)
            <form method="POST" action="{{ route('admin.couriers.settle', $courier) }}" class="row g-2 align-items-end mb-4">
                @csrf
                <div class="col-md-4">
                    <label class="form-label">تسديد المديونية</label>
                    <input type="number" step="0.01" min="0.01" max="{{ $summary['owes'] }}" name="amount" value="{{ old('amount', $summary['owes']) }}" class="form-control" required>
                </div>
                <div class="col-md-5">
                    <label class="form-label">ملاحظة</label>
                    <input type="text" name="note" value="{{ old('note') }}" class="form-control">
                </div>
                <div class="col-md-3">
                    <button class="btn btn-brand w-100">تسديد</button>
                </div>
            </form>
        @else
            <p class="text-muted">لا توجد مديونية حالية على هذا الموصل.</p>
        @endif

        <form method="POST" action="{{ route('admin.couriers.update', $courier) }}">
            @csrf
            @method('PUT')
            @include('admin.couriers._form')
            <div class="d-flex gap-2">
                <button class="btn btn-brand">{{ $strings::SAVE }}</button>
                <a href="{{ route('admin.couriers.index') }}" class="btn btn-outline-secondary rounded-pill">{{ $strings::CANCEL }}</a>
            </div>
        </form>
    </div>

    <div class="page-card p-4">
        <h2 class="h5 mb-3">كشف الحساب</h2>
        @if ($entries->isEmpty())
            <p class="text-muted mb-0">لا توجد حركات بعد.</p>
        @else
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>التاريخ</th>
                            <th>الحركة</th>
                            <th>الطلب</th>
                            <th>له</th>
                            <th>عليه</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($entries as $entry)
                            <tr>
                                <td>{{ $entry->created_at?->format('Y-m-d H:i') }}</td>
                                <td>
                                    <div class="fw-bold">{{ $entry->typeLabel() }}</div>
                                    @if ($entry->note)
                                        <small class="text-muted">{{ $entry->note }}</small>
                                    @endif
                                </td>
                                <td>{{ $entry->order?->order_number ?: '—' }}</td>
                                <td>
                                    @if ($entry->direction === 'credit')
                                        {{ number_format((float) $entry->amount, 2) }} {{ $strings::CURRENCY }}
                                    @endif
                                </td>
                                <td>
                                    @if ($entry->direction === 'debit')
                                        {{ number_format((float) $entry->amount, 2) }} {{ $strings::CURRENCY }}
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-layouts.admin>
