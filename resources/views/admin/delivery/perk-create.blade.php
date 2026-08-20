<x-layouts.admin :title="$title">
    <x-admin.page-head :title="$title" subtitle="خصم أو توصيل مجاني حسب عدد طلبات العميل" />
    <div class="page-card p-4 p-md-5" style="max-width: 720px">
        <x-admin.help-note>العرض النشط الأعلى ترتيباً يُطبَّق أولاً بعد حساب المسافة.</x-admin.help-note>
        <form method="POST" action="{{ route('admin.delivery.perks.store') }}" class="mt-3">
            @csrf
            @include('admin.delivery._perk-form')
            <div class="d-flex gap-2">
                <button class="btn btn-brand">{{ $strings::SAVE }}</button>
                <a href="{{ route('admin.delivery.index') }}" class="btn btn-outline-secondary rounded-pill">{{ $strings::CANCEL }}</a>
            </div>
        </form>
    </div>
</x-layouts.admin>
