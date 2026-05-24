@props([
    'icon' => 'inbox',
    'title' => 'Belum ada data',
    'description' => null,
    'actionLabel' => null,
    'actionUrl' => null,
])

<div {{ $attributes->merge(['class' => 'empty-state']) }}>
    <div class="empty-icon">
        <i class="fas fa-{{ $icon }}"></i>
    </div>
    <div class="empty-title">{{ $title }}</div>
    @if ($description)
        <div class="empty-description">{{ $description }}</div>
    @endif
    @if ($actionLabel && $actionUrl)
        <a href="{{ $actionUrl }}" class="btn btn-primary">
            <i class="fas fa-plus mr-1"></i> {{ $actionLabel }}
        </a>
    @endif
</div>
