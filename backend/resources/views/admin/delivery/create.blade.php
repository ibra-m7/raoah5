<x-layouts.admin :title="$title">
    <x-admin.page-head :title="$title" subtitle="حدد المسافة ونوع التسعير ثم احفظ" />
    <div class="page-card p-4 p-md-5" style="max-width: 720px">
        <x-admin.help-note>الشريحة النشطة تُحسب من موقع المتجر إلى عنوان العميل.</x-admin.help-note>
        <form method="POST" action="{{ route('admin.delivery.rules.store') }}" class="mt-3">
            @csrf
            @include('admin.delivery._form')
            <div class="d-flex gap-2">
                <button class="btn btn-brand">{{ $strings::SAVE }}</button>
                <a href="{{ route('admin.delivery.index') }}" class="btn btn-outline-secondary rounded-pill">{{ $strings::CANCEL }}</a>
            </div>
        </form>
    </div>
</x-layouts.admin>
