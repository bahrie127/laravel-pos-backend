@props([
    'title' => null,
    'subtitle' => null,
    'noPadding' => false,
])

<div {{ $attributes->merge(['class' => 'card-clean' . ($noPadding ? ' p-0' : '')]) }}>
    @if ($title || $subtitle || isset($actions))
        <div class="d-flex justify-content-between align-items-start mb-3 {{ $noPadding ? 'px-3 pt-3' : '' }}">
            <div>
                @if ($title)
                    <h6 class="mb-0 font-weight-bold">{{ $title }}</h6>
                @endif
                @if ($subtitle)
                    <small class="text-muted">{{ $subtitle }}</small>
                @endif
            </div>
            @isset($actions)
                <div class="d-flex" style="gap:.5rem;">{{ $actions }}</div>
            @endisset
        </div>
    @endif

    {{ $slot }}
</div>
