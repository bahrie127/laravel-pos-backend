@props([
    'variant' => 'primary',
    'size' => null,
    'icon' => null,
    'href' => null,
    'type' => 'button',
    'loading' => false,
])

@php
    $base = 'btn btn-' . $variant;
    $sizeClass = $size ? ' btn-' . $size : '';
    $classes = $base . $sizeClass;
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon)<i class="fas fa-{{ $icon }} mr-1"></i>@endif
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }} @if ($loading) disabled @endif>
        @if ($loading)
            <i class="fas fa-spinner fa-spin mr-1"></i>
        @elseif ($icon)
            <i class="fas fa-{{ $icon }} mr-1"></i>
        @endif
        {{ $slot }}
    </button>
@endif
