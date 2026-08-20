<x-layouts.admin :title="$title">
    <div class="page-card p-4 p-md-5" style="max-width: 980px">
        <x-admin.help-note>أقسام العرض تظهر في تبويب الأقسام داخل التطبيق (مثل المقاضي والمشروبات). اختر الأقسام الفرعية التي تريد تجميعها.</x-admin.help-note>
        <form method="POST" action="{{ route('admin.display-sections.store') }}" class="mt-3">
            @csrf
            @include('admin.display-sections._form')
            <div class="d-flex gap-2">
                <button class="btn btn-brand">{{ $strings::SAVE }}</button>
                <a href="{{ route('admin.display-sections.index') }}" class="btn btn-outline-secondary rounded-pill">{{ $strings::CANCEL }}</a>
            </div>
        </form>
    </div>
</x-layouts.admin>
