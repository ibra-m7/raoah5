<x-layouts.admin :title="$title">
    <x-admin.page-head
        :title="$title"
        :create="route('admin.coupons.create')"
        :create-label="$strings::ADD_COUPON"
    />

    <form method="GET" class="d-flex flex-wrap gap-2 mb-3">
        <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control" style="max-width: 240px" placeholder="ابحث بالكود أو العنوان">
        <select name="status" class="form-select" style="max-width: 140px">
            <option value="">{{ $strings::STATUS }}</option>
            <option value="active" @selected(($filters['status'] ?? '') === 'active')">{{ $strings::ACTIVE }}</option>
            <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')">{{ $strings::INACTIVE }}</option>
        </select>
        <button class="btn btn-outline-success rounded-pill">{{ $strings::FILTER }}</button>
    </form>

    <div class="page-card p-4">
        @if ($coupons->isEmpty())
            <x-admin.empty-state icon="bi-ticket-perforated" :action="route('admin.coupons.create')" :action-label="$strings::ADD_COUPON" />
        @else
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>الكود</th>
                            <th>النوع</th>
                            <th>الاستخدام</th>
                            <th>الصلاحية</th>
                            <th>{{ $strings::STATUS }}</th>
                            <th>{{ $strings::ACTIONS }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($coupons as $coupon)
                            <tr>
                                <td>
                                    <div class="fw-bold"><code>{{ $coupon->code }}</code></div>
                                    <div class="text-muted small">{{ $coupon->title }}</div>
                                </td>
                                <td>
                                    {{ $coupon->type->label() }}
                                    @if ($coupon->type->value !== 'free_shipping')
                                        <span class="text-muted">
                                            ·
                                            @if ($coupon->type->value === 'percent')
                                                {{ rtrim(rtrim(number_format($coupon->value, 2), '0'), '.') }}٪
                                            @else
                                                {{ number_format($coupon->value, 2) }} {{ $strings::CURRENCY }}
                                            @endif
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    {{ $coupon->redemptions_count }}
                                    @if ($coupon->usage_limit)
                                        / {{ $coupon->usage_limit }}
                                    @else
                                        <span class="text-muted">/ مفتوح</span>
                                    @endif
                                </td>
                                <td class="small">
                                    @if ($coupon->starts_at || $coupon->ends_at)
                                        {{ optional($coupon->starts_at)->format('Y-m-d') ?? '—' }}
                                        →
                                        {{ optional($coupon->ends_at)->format('Y-m-d') ?? '—' }}
                                    @else
                                        بدون تاريخ
                                    @endif
                                </td>
                                <td>
                                    @if ($coupon->is_active)
                                        <span class="badge badge-soft">{{ $strings::LIVE_IN_APP }}</span>
                                    @else
                                        <span class="badge badge-soft">{{ $strings::INACTIVE }}</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('admin.coupons.edit', $coupon) }}" class="btn btn-sm btn-outline-success rounded-pill">{{ $strings::EDIT }}</a>
                                        <form method="POST" action="{{ route('admin.coupons.destroy', $coupon) }}" onsubmit="return confirm('{{ $strings::CONFIRM_DELETE }}')">
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
            {{ $coupons->links() }}
        @endif
    </div>
</x-layouts.admin>
