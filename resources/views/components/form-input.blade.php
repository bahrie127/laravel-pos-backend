@props([
    'name',
    'label' => null,
    'type' => 'text',
    'value' => null,
    'placeholder' => null,
    'required' => false,
    'readonly' => false,
    'help' => null,
    'icon' => null,
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

    @if ($icon)
        <div class="input-group">
            <div class="input-group-prepend">
                <span class="input-group-text"><i class="fas fa-{{ $icon }}"></i></span>
            </div>
            <input
                type="{{ $type }}"
                name="{{ $name }}"
                id="{{ $id }}"
                value="{{ $val }}"
                @if ($placeholder) placeholder="{{ $placeholder }}" @endif
                @if ($required) required @endif
                @if ($readonly) readonly @endif
                {{ $attributes->merge(['class' => 'form-control' . ($hasError ? ' is-invalid' : '')]) }}
            >
        </div>
    @else
        <input
            type="{{ $type }}"
            name="{{ $name }}"
            id="{{ $id }}"
            value="{{ $val }}"
            @if ($placeholder) placeholder="{{ $placeholder }}" @endif
            @if ($required) required @endif
            @if ($readonly) readonly @endif
            {{ $attributes->merge(['class' => 'form-control' . ($hasError ? ' is-invalid' : '')]) }}
        >
    @endif

    @if ($hasError)
        <div class="invalid-feedback d-block">{{ $errors->first($name) }}</div>
    @elseif ($help)
        <small class="form-text text-muted">{{ $help }}</small>
    @endif
</div>
