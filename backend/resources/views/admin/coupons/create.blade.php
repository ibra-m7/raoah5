<x-layouts.admin :title="$title">
    <div class="page-card p-4 p-md-5" style="max-width: 860px">
        <x-admin.help-note>بعد الحفظ يمكن للعميل إدخال الكود في صفحة الطلب. التحقق يتم على السيرفر وليس في التطبيق فقط.</x-admin.help-note>
        <form method="POST" action="{{ route('admin.coupons.store') }}" class="mt-3">
            @csrf
            @include('admin.coupons._form')
            <div class="d-flex gap-2">
                <button class="btn btn-brand">{{ $strings::SAVE }}</button>
                <a href="{{ route('admin.coupons.index') }}" class="btn btn-outline-secondary rounded-pill">{{ $strings::CANCEL }}</a>
            </div>
        </form>
    </div>
</x-layouts.admin>
