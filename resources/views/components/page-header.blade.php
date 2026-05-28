@props([
    'title',
    'subtitle' => null,
    'breadcrumbs' => [],
])

<div class="page-header-block d-flex flex-wrap justify-content-between align-items-start mb-3" style="gap:1rem;">
    <div>
        @if (!empty($breadcrumbs))
            <nav aria-label="breadcrumb" class="mb-1">
                <ol class="breadcrumb" style="background:transparent;padding:0;margin:0;font-size:12px;">
                    @foreach ($breadcrumbs as $label => $url)
                        @if ($loop->last || $url === null)
                            <li class="breadcrumb-item active" aria-current="page">{{ is_int($label) ? $url : $label }}</li>
                        @else
                            <li class="breadcrumb-item"><a href="{{ $url }}" class="text-muted">{{ $label }}</a></li>
                        @endif
                    @endforeach
                </ol>
            </nav>
        @endif
        <h1 class="page-title m-0" style="font-size:24px;font-weight:700;color:var(--brand-gray-900);">
            {{ $title }}
        </h1>
        @if ($subtitle)
            <p class="text-muted m-0 mt-1" style="font-size:14px;">{{ $subtitle }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="d-flex flex-wrap align-items-center" style="gap:.5rem;">
            {{ $actions }}
        </div>
    @endisset
</div>
