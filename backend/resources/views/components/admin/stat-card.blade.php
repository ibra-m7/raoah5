@props(['icon', 'label', 'value'])

<div class="col-md-6 col-xl-3">
    <div class="stat-card p-4 h-100">
        <div class="d-flex align-items-center justify-content-between">
            <div>
                <div class="text-muted small mb-1">{{ $label }}</div>
                <div class="fs-4 fw-bold" style="color: var(--color-dark-text)">{{ $value }}</div>
            </div>
            <div class="stat-icon"><i class="bi {{ $icon }}"></i></div>
        </div>
    </div>
</div>
