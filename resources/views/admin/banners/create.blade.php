<x-layouts.admin :title="$title">
    <div class="page-card p-4 p-md-5" style="max-width: 920px">
        <x-admin.help-note>هذا الإعلان يظهر في شريط الرئيسية داخل التطبيق بعد الحفظ مباشرة.</x-admin.help-note>
        <form method="POST" action="{{ route('admin.banners.store') }}" enctype="multipart/form-data" class="mt-3">
            @csrf
            @include('admin.banners._form')
            <div class="d-flex gap-2">
                <button class="btn btn-brand">{{ $strings::SAVE }}</button>
                <a href="{{ route('admin.banners.index') }}" class="btn btn-outline-secondary rounded-pill">{{ $strings::CANCEL }}</a>
            </div>
        </form>
    </div>
</x-layouts.admin>
