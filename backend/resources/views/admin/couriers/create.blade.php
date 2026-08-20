<x-layouts.admin :title="$title">
    <div class="page-card p-4 p-md-5" style="max-width: 640px">
        <x-admin.help-note>بعد الحفظ يستطيع الموصل الدخول إلى تطبيق التوصيل برقم الجوال وكلمة المرور.</x-admin.help-note>
        <form method="POST" action="{{ route('admin.couriers.store') }}" class="mt-3">
            @csrf
            @include('admin.couriers._form')
            <div class="d-flex gap-2">
                <button class="btn btn-brand">{{ $strings::SAVE }}</button>
                <a href="{{ route('admin.couriers.index') }}" class="btn btn-outline-secondary rounded-pill">{{ $strings::CANCEL }}</a>
            </div>
        </form>
    </div>
</x-layouts.admin>
