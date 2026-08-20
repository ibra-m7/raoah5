<x-layouts.admin :title="$title">
    <div class="page-card p-4 p-md-5" style="max-width: 980px">
        <x-admin.help-note>هذا القسم يظهر كصف أفقي في الرئيسية. اختر المنتجات التي تريد عرضها فيه.</x-admin.help-note>
        <form method="POST" action="{{ route('admin.home-sections.store') }}" class="mt-3">
            @csrf
            @include('admin.home-sections._form')
            <div class="d-flex gap-2">
                <button class="btn btn-brand">{{ $strings::SAVE }}</button>
                <a href="{{ route('admin.home-sections.index') }}" class="btn btn-outline-secondary rounded-pill">{{ $strings::CANCEL }}</a>
            </div>
        </form>
    </div>
</x-layouts.admin>
