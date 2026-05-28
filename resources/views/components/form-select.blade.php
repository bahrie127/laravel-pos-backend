@props([
    'name',
    'label' => null,
    'options' => [],
    'value' => null,
    'placeholder' => null,
    'required' => false,
    'help' => null,
])

@php
    $id = $attributes->get('id') ?? $name;
    $val = old($name, $value);
    $hasError = $errors->has($name);
@endphp

<div class="form-group">
    @if ($label)
        <label for="{{ $id }}" class="form-label">
            {{ $label }}
            @if ($required)<span class="text-danger">*</span>@endif
        </label>
    @endif

    <select
        name="{{ $name }}"
        id="{{ $id }}"
        @if ($required) required @endif
        {{ $attributes->merge(['class' => 'form-control' . ($hasError ? ' is-invalid' : '')]) }}
    >
        @if ($placeholder)
            <option value="">{{ $placeholder }}</option>
        @endif

        @foreach ($options as $optValue => $optLabel)
            <option value="{{ $optValue }}" @selected((string) $val === (string) $optValue)>{{ $optLabel }}</option>
        @endforeach
    </select>

    @if ($hasError)
        <div class="invalid-feedback d-block">{{ $errors->first($name) }}</div>
    @elseif ($help)
        <small class="form-text text-muted">{{ $help }}</small>
    @endif
</div>
