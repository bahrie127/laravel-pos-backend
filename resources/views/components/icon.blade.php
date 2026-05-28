@props(['name', 'set' => 'fas'])

<i {{ $attributes->merge(['class' => $set . ' fa-' . $name]) }}></i>
