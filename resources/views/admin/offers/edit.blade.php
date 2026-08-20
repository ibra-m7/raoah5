<x-layouts.admin :title="$title">
    <div class="page-card p-4 p-md-5" style="max-width: 720px">
        <x-admin.help-note>تغيير سعر العرض يظهر في التطبيق فوراً. إلغاء الخصم يعيد السعر الأصلي دون حذف المنتج.</x-admin.help-note>
        <form method="POST" action="{{ route('admin.offers.update', $product) }}" class="mt-3">
            @csrf
            @method('PUT')
            @include('admin.offers._form')
            <div class="d-flex gap-2">
                <button class="btn btn-brand">{{ $strings::SAVE }}</button>
                <a href="{{ route('admin.offers.index') }}" class="btn btn-outline-secondary rounded-pill">{{ $strings::CANCEL }}</a>
            </div>
        </form>
    </div>
</x-layouts.admin>
