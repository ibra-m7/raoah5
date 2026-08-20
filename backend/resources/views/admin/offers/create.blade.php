<x-layouts.admin :title="$title">
    <div class="page-card p-4 p-md-5" style="max-width: 720px">
        <x-admin.help-note>اختر منتجاً وضع له سعراً أقل من سعره الأصلي. سيظهر تلقائياً في شريط العروض داخل التطبيق.</x-admin.help-note>
        <form method="POST" action="{{ route('admin.offers.store') }}" class="mt-3">
            @csrf
            @include('admin.offers._form')
            <div class="d-flex gap-2">
                <button class="btn btn-brand">{{ $strings::SAVE }}</button>
                <a href="{{ route('admin.offers.index') }}" class="btn btn-outline-secondary rounded-pill">{{ $strings::CANCEL }}</a>
            </div>
        </form>
    </div>
</x-layouts.admin>
