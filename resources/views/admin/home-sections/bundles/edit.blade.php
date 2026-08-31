<x-layouts.admin :title="$title">
    <div class="page-card p-4 p-md-5" style="max-width: 980px">
        <div class="mb-4">
            <a href="{{ route('admin.home-sections.edit', $section) }}" class="text-muted small text-decoration-none">
                <i class="bi bi-arrow-right"></i> العودة إلى {{ $section->title }}
            </a>
        </div>

        <form method="POST" action="{{ route('admin.home-sections.bundles.update', [$section, $bundle]) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            @include('admin.home-sections.bundles._form')
            <div class="d-flex gap-2">
                <button class="btn btn-brand">{{ $strings::SAVE }}</button>
                <a href="{{ route('admin.home-sections.edit', $section) }}" class="btn btn-outline-secondary rounded-pill">{{ $strings::CANCEL }}</a>
            </div>
        </form>
    </div>
</x-layouts.admin>
