<x-layouts.admin :title="$title">
    <div class="page-card p-4 p-md-5" style="max-width: 980px">
        <form method="POST" action="{{ route('admin.dynamic-pages.store') }}" enctype="multipart/form-data">
            @csrf
            @include('admin.dynamic-pages._form')
            <div class="d-flex gap-2">
                <button class="btn btn-brand">{{ $strings::SAVE }}</button>
                <a href="{{ route('admin.dynamic-pages.index') }}" class="btn btn-outline-secondary rounded-pill">{{ $strings::CANCEL }}</a>
            </div>
        </form>
    </div>
</x-layouts.admin>
