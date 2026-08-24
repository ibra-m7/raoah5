<x-layouts.admin :title="$title">
    <x-admin.page-head
        :title="$title"
        :create="route('admin.couriers.create')"
        :create-label="$strings::ADD_COURIER"
    />

    <form method="GET" class="d-flex flex-wrap gap-2 mb-3">
        <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control" style="max-width: 240px" placeholder="ابحث بالاسم أو الجوال">
        <select name="status" class="form-select" style="max-width: 140px">
            <option value="">{{ $strings::STATUS }}</option>
            <option value="active" @selected(($filters['status'] ?? '') === 'active')">{{ $strings::ACTIVE }}</option>
            <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')">{{ $strings::INACTIVE }}</option>
        </select>
        <button class="btn btn-outline-success rounded-pill">{{ $strings::FILTER }}</button>
    </form>

    <div class="page-card p-4">
        @if ($couriers->isEmpty())
            <x-admin.empty-state icon="bi-bicycle" :action="route('admin.couriers.create')" :action-label="$strings::ADD_COURIER" />
        @else
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>الموصل</th>
                            <th>الجوال</th>
                            <th>المسلّمة</th>
                            <th>{{ $strings::COURIER_OWES }}</th>
                            <th>{{ $strings::COURIER_OWED }}</th>
                            <th>التواجد</th>
                            <th>{{ $strings::STATUS }}</th>
                            <th>{{ $strings::ACTIONS }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($couriers as $courier)
                            <tr>
                                <td class="fw-bold">{{ $courier->name }}</td>
                                <td dir="ltr">{{ $courier->phoneDisplay() }}</td>
                                <td>{{ $courier->delivered_orders_count }}</td>
                                <td class="fw-bold">{{ number_format((float) $courier->owes_amount, 2) }} {{ $strings::CURRENCY }}</td>
                                <td>{{ number_format((float) $courier->owed_amount, 2) }} {{ $strings::CURRENCY }}</td>
                                <td>
                                    @if ($courier->is_online)
                                        <span class="badge badge-soft">متاح</span>
                                    @else
                                        <span class="badge badge-soft">متوقف</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($courier->is_active)
                                        <span class="badge badge-soft">{{ $strings::LIVE_IN_APP }}</span>
                                    @else
                                        <span class="badge badge-soft">{{ $strings::INACTIVE }}</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('admin.couriers.edit', $courier) }}" class="btn btn-sm btn-outline-success rounded-pill">الحساب</a>
                                        <form method="POST" action="{{ route('admin.couriers.destroy', $courier) }}" onsubmit="return confirm('{{ $strings::CONFIRM_DELETE }}')">
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
            {{ $couriers->links() }}
        @endif
    </div>
</x-layouts.admin>
