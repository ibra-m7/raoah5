@php
    $sectionBundles = $sectionBundles ?? collect();
@endphp

<div class="page-card p-4 mt-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h2 class="h5 mb-1">سلات التوفير</h2>
            <p class="text-muted small mb-0">أنشئ السلات ورتّبها هنا. تظهر في التطبيق ضمن هذا القسم مباشرة.</p>
        </div>
        <a href="{{ route('admin.home-sections.bundles.create', $section) }}" class="btn btn-brand btn-sm rounded-pill">
            <i class="bi bi-plus-lg"></i> {{ $strings::ADD_BUNDLE }}
        </a>
    </div>

    @if ($sectionBundles->isEmpty())
        <x-admin.empty-state
            icon="bi-basket"
            :action="route('admin.home-sections.bundles.create', $section)"
            :action-label="$strings::ADD_BUNDLE"
        />
    @else
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>الاسم</th>
                        <th>المنتجات</th>
                        <th>الخصم</th>
                        <th>السعر</th>
                        <th>{{ $strings::STATUS }}</th>
                        <th>{{ $strings::ACTIONS }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sectionBundles as $bundle)
                        <tr>
                            <td>
                                <div class="fw-bold">{{ $bundle->name }}</div>
                                @if ($bundle->summary)
                                    <div class="text-muted small">{{ $bundle->summary }}</div>
                                @endif
                            </td>
                            <td>{{ $bundle->items_count }}</td>
                            <td>{{ number_format((float) $bundle->discount_percent, 0) }}%</td>
                            <td>{{ number_format((float) $bundle->bundle_price, 2) }} {{ $strings::CURRENCY }}</td>
                            <td>
                                <span class="badge badge-soft">{{ $bundle->is_active ? $strings::LIVE_IN_APP : $strings::INACTIVE }}</span>
                            </td>
                            <td>
                                <div class="d-flex gap-2">
                                    <a href="{{ route('admin.home-sections.bundles.edit', [$section, $bundle]) }}" class="btn btn-sm btn-outline-success rounded-pill">{{ $strings::EDIT }}</a>
                                    <form method="POST" action="{{ route('admin.home-sections.bundles.destroy', [$section, $bundle]) }}" onsubmit="return confirm('{{ $strings::CONFIRM_DELETE }}')">
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
