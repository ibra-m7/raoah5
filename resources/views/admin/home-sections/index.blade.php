<x-layouts.admin :title="$title">
    <x-admin.page-head
        :title="$title"
        subtitle="أدر شرائط المنتجات وسلات التوفير في الصفحة الرئيسية"
        :create="route('admin.home-sections.create')"
        :create-label="$strings::ADD_HOME_SECTION"
    />

    <div class="alert alert-light border mb-3">
        <strong>مركز واجهة التطبيق</strong>
        <p class="mb-0 small text-muted">كل ما يظهر كشريط في الصفحة الرئيسية يُدار من هنا. السلات تُنشأ داخل قسم «عرض السلات».</p>
    </div>

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
                            <th>نوع المحتوى</th>
                            <th>لون الخلفية</th>
                            <th>المنتجات / السلات</th>
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
                                <td>{{ $section->contentTypeLabel() }}</td>
                                <td>
                                    @if ($section->background_color)
                                        <span class="d-inline-flex align-items-center gap-2">
                                            <span
                                                class="d-inline-block rounded border"
                                                style="width: 1.25rem; height: 1.25rem; background: {{ $section->background_color }};"
                                                title="{{ $section->background_color }}"
                                            ></span>
                                            <span class="text-muted small">{{ $section->background_color }}</span>
                                        </span>
                                    @else
                                        <span class="badge badge-soft">افتراضي</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($section->showsBundles())
                                        {{ $section->bundles_count }} سلة
                                    @else
                                        {{ $section->products_count }}
                                    @endif
                                </td>
                                <td>
                                    <span class="badge badge-soft">{{ $section->is_active ? $strings::LIVE_IN_APP : $strings::INACTIVE }}</span>
                                </td>
                                <td>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <a href="{{ route('admin.home-sections.edit', $section) }}" class="btn btn-sm btn-outline-success rounded-pill">{{ $strings::EDIT }}</a>
                                        @if ($section->showsBundles())
                                            <a href="{{ route('admin.home-sections.bundles.create', $section) }}" class="btn btn-sm btn-outline-primary rounded-pill">إضافة سلة</a>
                                        @endif
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
