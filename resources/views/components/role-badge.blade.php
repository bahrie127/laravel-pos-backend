@props(['role'])

@php
    $r = $role instanceof \BackedEnum ? $role->value : (string) $role;

    [$class, $label] = match ($r) {
        'owner' => ['badge-soft-primary', 'Pemilik'],
        'admin' => ['badge-soft-info', 'Admin'],
        'kasir' => ['badge-soft-success', 'Kasir'],
        default => ['badge-soft-secondary', $r ?: '—'],
    };
@endphp

<span {{ $attributes->merge(['class' => 'badge ' . $class]) }}>{{ $label }}</span>
