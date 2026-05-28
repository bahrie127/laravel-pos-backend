@props([
    'id',
    'title' => null,
    'size' => null,
    'static' => false,
])

@php
    $sizeClass = $size ? ' modal-' . $size : '';
@endphp

<div class="modal fade"
     id="{{ $id }}"
     tabindex="-1"
     role="dialog"
     aria-labelledby="{{ $id }}-title"
     aria-hidden="true"
     @if ($static) data-backdrop="static" data-keyboard="false" @endif>
    <div class="modal-dialog{{ $sizeClass }}" role="document">
        <div class="modal-content" style="border:none;border-radius:12px;box-shadow:0 10px 25px rgba(0,0,0,.1);">
            @if ($title || isset($header))
                <div class="modal-header" style="border-bottom:1px solid #e5e7eb;">
                    <h5 class="modal-title font-weight-bold" id="{{ $id }}-title">
                        @isset($header){{ $header }}@else{{ $title }}@endisset
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            <div class="modal-body">
                {{ $slot }}
            </div>

            @isset($footer)
                <div class="modal-footer" style="border-top:1px solid #e5e7eb;">
                    {{ $footer }}
                </div>
            @endisset
        </div>
    </div>
</div>
