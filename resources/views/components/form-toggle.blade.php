@props([
    'name',
    'label' => null,
    'checked' => false,
    'value' => '1',
    'help' => null,
])

@php
    $id = $attributes->get('id') ?? $name;
    $isChecked = old($name, $checked) ? true : false;
@endphp

<div class="form-group">
    <input type="hidden" name="{{ $name }}" value="0">
    <label class="custom-switch d-inline-flex align-items-center mb-0" for="{{ $id }}" style="cursor:pointer;">
        <input
            type="checkbox"
            name="{{ $name }}"
            id="{{ $id }}"
            value="{{ $value }}"
            class="custom-switch-input"
            @checked($isChecked)
            {{ $attributes }}
        >
        <span class="custom-switch-indicator"></span>
        @if ($label)
            <span class="custom-switch-description ml-2">{{ $label }}</span>
        @endif
    </label>
    @if ($help)
        <small class="form-text text-muted d-block mt-1">{{ $help }}</small>
    @endif
</div>
