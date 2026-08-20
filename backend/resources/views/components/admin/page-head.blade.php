@props(['title', 'subtitle' => null, 'create' => null, 'createLabel' => null, 'createModal' => null])

<div class="page-head">
    <div>
        <h1 class="page-head-title">{{ $title }}</h1>
        @if ($subtitle)
            <p class="page-head-sub mb-0">{{ $subtitle }}</p>
        @endif
    </div>
    @if ($createModal)
        <button type="button" class="btn btn-brand" data-bs-toggle="modal" data-bs-target="{{ $createModal }}" {{ $attributes }}>
            <i class="bi bi-plus-lg ms-1"></i>
            {{ $createLabel ?? $strings::ADD }}
        </button>
    @elseif ($create)
        <a href="{{ $create }}" class="btn btn-brand">
            <i class="bi bi-plus-lg ms-1"></i>
            {{ $createLabel ?? $strings::ADD }}
        </a>
    @endif
</div>
