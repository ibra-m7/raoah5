<x-layouts.admin :title="$title">
    <div class="page-card p-4 p-md-5" style="max-width: 860px">
        <x-admin.help-note>التعديل يظهر في التطبيق بعد الحفظ. المستوى يتحدد من «يقع تحت».</x-admin.help-note>
        <form method="POST" action="{{ route('admin.categories.update', $category) }}" enctype="multipart/form-data" class="mt-3">
            @csrf
            @method('PUT')
            @include('admin.categories._form')
            <div class="d-flex gap-2">
                <button class="btn btn-brand">{{ $strings::SAVE }}</button>
                <a href="{{ route('admin.categories.index') }}" class="btn btn-outline-secondary rounded-pill">{{ $strings::CANCEL }}</a>
            </div>
        </form>
    </div>
</x-layouts.admin>
