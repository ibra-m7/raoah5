<x-layouts.admin :title="$title">
    <div class="page-card p-4 p-md-5" style="max-width: 980px">
        <form method="POST" action="{{ route('admin.home-sections.update', $section) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            @include('admin.home-sections._form')
            <div class="d-flex gap-2">
                <button class="btn btn-brand">{{ $strings::SAVE }}</button>
                <a href="{{ route('admin.home-sections.index') }}" class="btn btn-outline-secondary rounded-pill">{{ $strings::CANCEL }}</a>
            </div>
        </form>
    </div>

    @if ($section->showsBundles())
        @include('admin.home-sections._bundles-manager', ['sectionBundles' => $sectionBundles ?? collect()])
    @endif
</x-layouts.admin>
