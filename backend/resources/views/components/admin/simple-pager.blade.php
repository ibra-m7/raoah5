@props(['paginator', 'fragment' => null])

@php
    if ($fragment) {
        $paginator = $paginator->fragment($fragment);
    }
@endphp

@if ($paginator->hasPages())
    <nav {{ $attributes->class(['simple-pager d-flex justify-content-between align-items-center gap-2 mt-3']) }} aria-label="صفحات">
        @if ($paginator->onFirstPage())
            <span class="btn btn-sm btn-outline-secondary rounded-pill disabled">{{ $strings::PREVIOUS }}</span>
        @else
            <a class="btn btn-sm btn-outline-success rounded-pill" href="{{ $paginator->previousPageUrl() }}">{{ $strings::PREVIOUS }}</a>
        @endif

        <span class="text-muted small">{{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</span>

        @if ($paginator->hasMorePages())
            <a class="btn btn-sm btn-outline-success rounded-pill" href="{{ $paginator->nextPageUrl() }}">{{ $strings::NEXT }}</a>
        @else
            <span class="btn btn-sm btn-outline-secondary rounded-pill disabled">{{ $strings::NEXT }}</span>
        @endif
    </nav>
@endif
