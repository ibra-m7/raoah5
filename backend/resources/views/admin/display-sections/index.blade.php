<x-layouts.admin :title="$title">
    <x-admin.page-head
        :title="$title"
        subtitle="مجموعات الأقسام في تبويب الأقسام داخل التطبيق"
        :create="route('admin.display-sections.create')"
        :create-label="$strings::ADD_DISPLAY_SECTION"
    />

    <x-admin.help-note>هذه ليست الأقسام الرئيسية. الأقسام الرئيسية تُدار من قائمة الأقسام. هنا تجمع أقساماً فرعية في شبكات تبويب الأقسام.</x-admin.help-note>

    <form method="GET" class="d-flex flex-wrap gap-2 mb-3">
        <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control" style="max-width: 240px" placeholder="{{ $strings::SEARCH }}">
        <button class="btn btn-outline-success rounded-pill">{{ $strings::FILTER }}</button>
    </form>

    <div class="page-card p-4">
        @if ($sections->isEmpty())
            <x-admin.empty-state icon="bi-ui-checks-grid" :action="route('admin.display-sections.create')" :action-label="$strings::ADD_DISPLAY_SECTION" />
        @else
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>القسم</th>
                            <th>المعرف</th>
                            <th>الفئات</th>
                            <th>{{ $strings::STATUS }}</th>
                            <th>{{ $strings::ACTIONS }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sections as $section)
                            <tr>
                                <td class="fw-bold">{{ $section->emoji }} {{ $section->name }}</td>
                                <td dir="ltr">{{ $section->slug }}</td>
                                <td>{{ $section->categories_count }}</td>
                                <td>
                                    <span class="badge badge-soft">{{ $section->is_active ? $strings::LIVE_IN_APP : $strings::INACTIVE }}</span>
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('admin.display-sections.edit', $section) }}" class="btn btn-sm btn-outline-success rounded-pill">{{ $strings::EDIT }}</a>
                                        <form method="POST" action="{{ route('admin.display-sections.destroy', $section) }}" onsubmit="return confirm('{{ $strings::CONFIRM_DELETE }}')">
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
