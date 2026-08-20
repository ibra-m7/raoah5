<x-layouts.admin :title="$title">
    <div class="page-card p-4 p-md-5" style="max-width: 720px">
        <x-admin.help-note>التعديل يظهر في التطبيق فوراً بعد الحفظ.</x-admin.help-note>
        <form method="POST" action="{{ route('admin.payment-methods.update', $method) }}" class="mt-3">
            @csrf
            @method('PUT')
            @include('admin.payment-methods._form')
            <div class="d-flex gap-2">
                <button class="btn btn-brand">{{ $strings::SAVE }}</button>
                <a href="{{ route('admin.payment-methods.index') }}" class="btn btn-outline-secondary rounded-pill">{{ $strings::CANCEL }}</a>
            </div>
        </form>
    </div>
</x-layouts.admin>
