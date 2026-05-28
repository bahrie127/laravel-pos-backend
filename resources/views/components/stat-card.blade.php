@props([
    'label',
    'value',
    'icon' => null,
    'color' => 'primary',
    'delta' => null,
    'deltaLabel' => 'vs kemarin',
    'href' => null,
])

@php
    $iconBg = match ($color) {
        'success' => 'bg-success-soft',
        'warning' => 'bg-warning-soft',
        'danger'  => 'bg-danger-soft',
        'info'    => 'bg-info-soft',
        default   => 'bg-primary-soft',
    };

    $deltaClass = $delta === null
        ? ''
        : ($delta >= 0 ? 'up' : 'down');
    $deltaArrow = $delta === null ? '' : ($delta >= 0 ? '↑' : '↓');
@endphp

@if ($href)
    <a href="{{ $href }}" class="stat-card" style="text-decoration:none;color:inherit;display:block;">
@else
    <div class="stat-card">
@endif
    @if ($icon)
        <div class="stat-icon {{ $iconBg }}">
            <i class="fas fa-{{ $icon }}"></i>
        </div>
    @endif
    <div class="stat-label">{{ $label }}</div>
    <div class="stat-value">{{ $value }}</div>
    @if ($delta !== null)
        <div class="stat-delta {{ $deltaClass }}">
            {{ $deltaArrow }} {{ abs($delta) }}% <span class="text-muted">{{ $deltaLabel }}</span>
        </div>
    @endif
@if ($href)
    </a>
@else
    </div>
@endif
