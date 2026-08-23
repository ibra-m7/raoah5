<x-layouts.admin :title="$title">
    <x-admin.page-head
        :title="$title"
        subtitle="حدّد مكان ظهور الصفحة في التطبيق، واربط البنر بها من شاشة الإعلانات"
        :create="route('admin.dynamic-pages.create')"
        :create-label="$strings::ADD_DYNAMIC_PAGE"
    />

    <x-admin.help-note>اربط البنر بهذه الصفحة من شاشة الإعلانات باختيار «صفحة ترويجية».</x-admin.help-note>

    <form method="GET" class="d-flex flex-wrap gap-2 mb-3">
        <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control" style="max-width: 240px" placeholder="{{ $strings::SEARCH }}">
        <button class="btn btn-outline-success rounded-pill">{{ $strings::FILTER }}</button>
    </form>

    <div class="page-card p-4">
        @if ($pages->isEmpty())
            <x-admin.empty-state icon="bi-layout-text-window-reverse" :action="route('admin.dynamic-pages.create')" :action-label="$strings::ADD_DYNAMIC_PAGE" />
        @else
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>العنوان</th>
                            <th>مكان الظهور</th>
                            <th>المنتجات</th>
                            <th>{{ $strings::STATUS }}</th>
                            <th>{{ $strings::ACTIONS }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($pages as $page)
                            <tr>
                                <td>
                                    <div class="fw-bold">{{ $page->title }}</div>
                                </td>
                                <td>{{ $page->placement?->label() ?? '—' }}</td>
                                <td>{{ $page->products_count }}</td>
                                <td>
                                    <span class="badge badge-soft">{{ $page->is_active ? $strings::LIVE_IN_APP : $strings::INACTIVE }}</span>
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('admin.dynamic-pages.edit', $page) }}" class="btn btn-sm btn-outline-success rounded-pill">{{ $strings::EDIT }}</a>
                                        <form method="POST" action="{{ route('admin.dynamic-pages.destroy', $page) }}" onsubmit="return confirm('{{ $strings::CONFIRM_DELETE }}')">
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
            {{ $pages->links() }}
        @endif
    </div>
</x-layouts.admin>
