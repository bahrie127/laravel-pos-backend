<?php

if (! function_exists('rupiah')) {
    function rupiah(int|float|null $amount, bool $withPrefix = true): string
    {
        $formatted = number_format((float) ($amount ?? 0), 0, ',', '.');

        return $withPrefix ? 'Rp ' . $formatted : $formatted;
    }
}

if (! function_exists('formatDate')) {
    function formatDate(mixed $value, string $format = 'd M Y H:i', string $fallback = '—'): string
    {
        if (! $value) {
            return $fallback;
        }

        $carbon = $value instanceof \Carbon\Carbon
            ? $value
            : \Carbon\Carbon::parse($value);

        return $carbon->translatedFormat($format);
    }
}

if (! function_exists('initials')) {
    function initials(?string $name): string
    {
        if (! $name) {
            return '?';
        }

        $parts = preg_split('/\s+/', trim($name));
        $first = mb_substr($parts[0] ?? '', 0, 1);
        $second = mb_substr($parts[1] ?? '', 0, 1);

        return mb_strtoupper($first . $second);
    }
}
