<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Resolve a date-range filter from a request into [from, to, previousFrom, previousTo, label, preset].
 *
 * Supports presets:
 *   today, yesterday, 7d, 30d, this_month, last_month, custom
 *
 * Custom preset uses request fields date_from / date_to (Y-m-d).
 * Default = this_month.
 */
class DateRangeResolver
{
    public const PRESETS = [
        'today' => 'Hari Ini',
        'yesterday' => 'Kemarin',
        '7d' => '7 Hari Terakhir',
        '30d' => '30 Hari Terakhir',
        'this_month' => 'Bulan Ini',
        'last_month' => 'Bulan Lalu',
        'custom' => 'Custom',
    ];

    public Carbon $from;
    public Carbon $to;
    public Carbon $previousFrom;
    public Carbon $previousTo;
    public string $label;
    public string $preset;

    public function __construct(Request $request, string $default = 'this_month')
    {
        $preset = $request->get('preset', $default);
        if (! array_key_exists($preset, self::PRESETS)) {
            $preset = $default;
        }

        $now = now();

        [$from, $to] = match ($preset) {
            'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'yesterday' => [
                $now->copy()->subDay()->startOfDay(),
                $now->copy()->subDay()->endOfDay(),
            ],
            '7d' => [$now->copy()->subDays(6)->startOfDay(), $now->copy()->endOfDay()],
            '30d' => [$now->copy()->subDays(29)->startOfDay(), $now->copy()->endOfDay()],
            'this_month' => [$now->copy()->startOfMonth(), $now->copy()->endOfDay()],
            'last_month' => [
                $now->copy()->subMonthNoOverflow()->startOfMonth(),
                $now->copy()->subMonthNoOverflow()->endOfMonth(),
            ],
            'custom' => [
                $request->filled('date_from')
                    ? Carbon::parse($request->date_from)->startOfDay()
                    : $now->copy()->startOfMonth(),
                $request->filled('date_to')
                    ? Carbon::parse($request->date_to)->endOfDay()
                    : $now->copy()->endOfDay(),
            ],
            default => [$now->copy()->startOfMonth(), $now->copy()->endOfDay()],
        };

        $this->from = $from;
        $this->to = $to;
        $this->preset = $preset;
        $this->label = self::PRESETS[$preset];

        $lengthSeconds = max(1, $from->diffInSeconds($to));
        $this->previousTo = $from->copy()->subSecond();
        $this->previousFrom = $this->previousTo->copy()->subSeconds($lengthSeconds);
    }

    public static function fromRequest(Request $request, string $default = 'this_month'): self
    {
        return new self($request, $default);
    }

    public function toArray(): array
    {
        return [
            'from' => $this->from->toDateTimeString(),
            'to' => $this->to->toDateTimeString(),
            'previous_from' => $this->previousFrom->toDateTimeString(),
            'previous_to' => $this->previousTo->toDateTimeString(),
            'preset' => $this->preset,
            'label' => $this->label,
        ];
    }
}
