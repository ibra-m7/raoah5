<x-layouts.admin :title="$title">
    <x-admin.page-head
        :title="$title"
        :create="route('admin.payment-methods.create')"
        :create-label="$strings::ADD_PAYMENT_METHOD"
    />

    <form method="GET" class="d-flex flex-wrap gap-2 mb-3">
        <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control" style="max-width: 240px" placeholder="{{ $strings::SEARCH }}">
        <select name="status" class="form-select" style="max-width: 140px">
            <option value="">{{ $strings::STATUS }}</option>
            <option value="active" @selected(($filters['status'] ?? '') === 'active')">{{ $strings::ACTIVE }}</option>
            <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')">{{ $strings::INACTIVE }}</option>
        </select>
        <button class="btn btn-outline-success rounded-pill">{{ $strings::FILTER }}</button>
    </form>

    <div class="page-card p-4">
        @if ($methods->isEmpty())
            <x-admin.empty-state icon="bi-credit-card" :action="route('admin.payment-methods.create')" :action-label="$strings::ADD_PAYMENT_METHOD" />
        @else
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th></th>
                            <th>الاسم</th>
                            <th>المعرّف</th>
                            <th>{{ $strings::SORT_ORDER }}</th>
                            <th>{{ $strings::STATUS }}</th>
                            <th>{{ $strings::ACTIONS }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($methods as $method)
                            <tr>
                                <td><i class="{{ $method->icon }} fs-4 text-success"></i></td>
                                <td>
                                    <div class="fw-bold">{{ $method->label }}</div>
                                    <div class="text-muted small">{{ $method->hint }}</div>
                                </td>
                                <td><code>{{ $method->slug }}</code></td>
                                <td>{{ $method->sort_order }}</td>
                                <td>
                                    @if ($method->is_active)
                                        <span class="badge badge-soft">{{ $strings::LIVE_IN_APP }}</span>
                                    @else
                                        <span class="badge badge-soft">{{ $strings::INACTIVE }}</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('admin.payment-methods.edit', $method) }}" class="btn btn-sm btn-outline-success rounded-pill">{{ $strings::EDIT }}</a>
                                        <form method="POST" action="{{ route('admin.payment-methods.destroy', $method) }}" onsubmit="return confirm('{{ $strings::CONFIRM_DELETE }}')">
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
            {{ $methods->links() }}
        @endif
    </div>
</x-layouts.admin>
