@props([
    'name',
    'label' => null,
    'value' => null,
    'placeholder' => null,
    'rows' => 3,
    'required' => false,
    'maxlength' => null,
    'counter' => false,
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

    <textarea
        name="{{ $name }}"
        id="{{ $id }}"
        rows="{{ $rows }}"
        @if ($placeholder) placeholder="{{ $placeholder }}" @endif
        @if ($required) required @endif
        @if ($maxlength) maxlength="{{ $maxlength }}" @endif
        {{ $attributes->merge(['class' => 'form-control' . ($hasError ? ' is-invalid' : '')]) }}
    >{{ $val }}</textarea>

    @if ($counter && $maxlength)
        <small class="form-text text-muted text-right d-block" id="{{ $id }}-counter">
            <span class="char-count">0</span> / {{ $maxlength }}
        </small>
        @push('scripts')
            <script>
                (function () {
                    var el = document.getElementById('{{ $id }}');
                    var counter = document.querySelector('#{{ $id }}-counter .char-count');
                    if (!el || !counter) return;
                    var update = function () { counter.textContent = el.value.length; };
                    update();
                    el.addEventListener('input', update);
                })();
            </script>
        @endpush
    @endif

    @if ($hasError)
        <div class="invalid-feedback d-block">{{ $errors->first($name) }}</div>
    @elseif ($help)
        <small class="form-text text-muted">{{ $help }}</small>
    @endif
</div>
