<x-layouts.admin :title="$title">
    <div class="page-card p-4 p-md-5" style="max-width: 860px">
        <x-admin.help-note>يظهر القسم في التطبيق فوراً بعد الحفظ. الأقسام الرئيسية بدون أب، والفرعية تحت قسم أب.</x-admin.help-note>
        <form method="POST" action="{{ route('admin.categories.store') }}" enctype="multipart/form-data" class="mt-3">
            @csrf
            @include('admin.categories._form')
            <div class="d-flex gap-2">
                <button class="btn btn-brand">{{ $strings::SAVE }}</button>
                <a href="{{ route('admin.categories.index') }}" class="btn btn-outline-secondary rounded-pill">{{ $strings::CANCEL }}</a>
            </div>
        </form>
    </div>
</x-layouts.admin>
