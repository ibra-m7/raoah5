<x-layouts.admin :title="$title">
    <x-admin.page-head
        :title="$title"
        subtitle="شرائط المنتجات في الصفحة الرئيسية"
        :create="route('admin.home-sections.create')"
        :create-label="$strings::ADD_HOME_SECTION"
    />

    <form method="GET" class="d-flex flex-wrap gap-2 mb-3">
        <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control" style="max-width: 240px" placeholder="{{ $strings::SEARCH }}">
        <button class="btn btn-outline-success rounded-pill">{{ $strings::FILTER }}</button>
    </form>

    <div class="page-card p-4">
        @if ($sections->isEmpty())
            <x-admin.empty-state icon="bi-house" :action="route('admin.home-sections.create')" :action-label="$strings::ADD_HOME_SECTION" />
        @else
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>الاسم</th>
                            <th>شكل العرض</th>
                            <th>المنتجات</th>
                            <th>{{ $strings::STATUS }}</th>
                            <th>{{ $strings::ACTIONS }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sections as $section)
                            <tr>
                                <td>
                                    <div class="fw-bold">{{ $section->title }}</div>
                                    <div class="text-muted small">{{ $section->subtitle }}</div>
                                </td>
                                <td>{{ $section->styleLabel() }}</td>
                                <td>{{ $section->products_count }}</td>
                                <td>
                                    <span class="badge badge-soft">{{ $section->is_active ? $strings::LIVE_IN_APP : $strings::INACTIVE }}</span>
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('admin.home-sections.edit', $section) }}" class="btn btn-sm btn-outline-success rounded-pill">{{ $strings::EDIT }}</a>
                                        <form method="POST" action="{{ route('admin.home-sections.destroy', $section) }}" onsubmit="return confirm('{{ $strings::CONFIRM_DELETE }}')">
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
            {{ $sections->links() }}
        @endif
    </div>
</x-layouts.admin>
