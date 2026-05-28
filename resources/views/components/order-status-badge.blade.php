@props(['status'])

@php
    $s = (string) $status;

    [$class, $label] = match ($s) {
        'paid'      => ['badge-soft-success', 'Lunas'],
        'pending'   => ['badge-soft-warning', 'Menunggu'],
        'cancelled' => ['badge-soft-danger',  'Dibatalkan'],
        'refunded'  => ['badge-soft-info',    'Refund'],
        default     => ['badge-soft-secondary', $s ?: '—'],
    };
@endphp

<span {{ $attributes->merge(['class' => 'badge ' . $class]) }}>{{ $label }}</span>
