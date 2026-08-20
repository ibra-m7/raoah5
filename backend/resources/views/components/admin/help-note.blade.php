@props(['icon' => 'bi-phone'])

<div class="help-note">
    <i class="bi {{ $icon }}"></i>
    <div>{{ $slot }}</div>
</div>
