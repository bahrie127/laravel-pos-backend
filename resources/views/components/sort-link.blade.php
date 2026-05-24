@props(['column', 'current' => null, 'direction' => 'desc'])

@php
    $current = $current ?? request('sort');
    $direction = $direction ?? request('direction', 'desc');
    $isActive = $current === $column;
    $newDirection = $isActive && $direction === 'asc' ? 'desc' : 'asc';
    $query = array_merge(request()->query(), ['sort' => $column, 'direction' => $newDirection]);
@endphp

<a href="{{ url()->current() . '?' . http_build_query($query) }}"
   class="text-reset text-decoration-none d-inline-flex align-items-center"
   style="white-space:nowrap;">
    {{ $slot }}
    @if ($isActive)
        <i class="fas fa-arrow-{{ $direction === 'asc' ? 'up' : 'down' }} ml-1" style="font-size:11px;"></i>
    @else
        <i class="fas fa-sort ml-1" style="font-size:11px;opacity:.3;"></i>
    @endif
</a>
