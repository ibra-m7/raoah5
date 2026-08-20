@props(['icon' => 'bi-inbox', 'message' => null, 'action' => null, 'actionLabel' => null, 'modal' => false])

<div class="text-center py-5 text-muted">
    <i class="bi {{ $icon }} fs-1 d-block mb-2"></i>
    <div>{{ $message ?? $strings::EMPTY }}</div>
    @if ($action)
        @if ($modal)
            <button type="button" class="btn btn-brand mt-3" data-bs-toggle="modal" data-bs-target="{{ $action }}" {{ $attributes }}>
                {{ $actionLabel ?? $strings::ADD }}
            </button>
        @else
            <a href="{{ $action }}" class="btn btn-brand mt-3">{{ $actionLabel ?? $strings::ADD }}</a>
        @endif
    @endif
</div>
