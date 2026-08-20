<x-layouts.admin :title="$title">
    <x-admin.page-head :title="$title" subtitle="التعديل يظهر فوراً في حساب الطلبات الجديدة" />
    <div class="page-card p-4 p-md-5" style="max-width: 720px">
        <x-admin.help-note>التعديل يظهر فوراً في حساب رسوم الطلبات الجديدة.</x-admin.help-note>
        <form method="POST" action="{{ route('admin.delivery.rules.update', $rule) }}" class="mt-3">
            @csrf
            @method('PUT')
            @include('admin.delivery._form')
            <div class="d-flex gap-2">
                <button class="btn btn-brand">{{ $strings::SAVE }}</button>
                <a href="{{ route('admin.delivery.index') }}" class="btn btn-outline-secondary rounded-pill">{{ $strings::CANCEL }}</a>
            </div>
        </form>
    </div>
</x-layouts.admin>
