<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{ $data['title'] }}</title>
    <style>
        @page { margin: 20mm 14mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111827; }
        h1 { font-size: 16px; margin: 0 0 4px 0; }
        .meta { color: #6B7280; font-size: 10px; margin-bottom: 12px; }
        .summary { background: #F9FAFB; border: 1px solid #E5E7EB; padding: 8px 12px; margin-bottom: 12px; }
        .summary table { width: 100%; }
        .summary td { padding: 2px 4px; }
        .summary .lbl { color: #6B7280; }
        .summary .val { text-align: right; font-weight: bold; }
        table.data { width: 100%; border-collapse: collapse; }
        table.data th { background: #F3F4F6; text-align: left; padding: 6px 8px; border-bottom: 1px solid #D1D5DB; font-size: 10px; }
        table.data td { padding: 5px 8px; border-bottom: 1px solid #F3F4F6; font-size: 10px; }
        table.data tr:nth-child(even) td { background: #FAFAFB; }
        .right { text-align: right; }
        .footer { margin-top: 16px; color: #9CA3AF; font-size: 9px; text-align: center; }
        .empty { padding: 20px; text-align: center; color: #9CA3AF; }
    </style>
</head>
<body>
    <h1>{{ $data['title'] }}</h1>
    <div class="meta">
        Periode: <strong>{{ $from->translatedFormat('d M Y') }}</strong>
        sampai <strong>{{ $to->translatedFormat('d M Y') }}</strong>
        @if (! empty($filters['kasir_id']))
            · Kasir ID #{{ $filters['kasir_id'] }}
        @endif
        @if (! empty($filters['payment_method']))
            · Pembayaran: {{ $filters['payment_method'] }}
        @endif
        @if (! empty($filters['category_id']))
            · Kategori ID #{{ $filters['category_id'] }}
        @endif
        @if (! empty($filters['status']))
            · Status: {{ $filters['status'] }}
        @endif
    </div>

    @if (! empty($data['summary']))
        <div class="summary">
            <table>
                @foreach ($data['summary'] as $lbl => $val)
                    <tr>
                        <td class="lbl">{{ $lbl }}</td>
                        <td class="val">
                            @if (str_contains(strtolower($lbl), 'pendapatan') || str_contains(strtolower($lbl), 'revenue') || str_contains(strtolower($lbl), 'nilai') || str_contains(strtolower($lbl), 'diskon'))
                                Rp {{ number_format((float) $val, 0, ',', '.') }}
                            @else
                                {{ is_numeric($val) ? number_format($val, 0, ',', '.') : $val }}
                            @endif
                        </td>
                    </tr>
                @endforeach
            </table>
        </div>
    @endif

    <table class="data">
        <thead>
            <tr>
                @foreach ($data['headings'] as $h)
                    <th>{{ $h }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($data['rows'] as $row)
                <tr>
                    @foreach ($row as $i => $cell)
                        <td class="{{ is_numeric($cell) && $i > 0 ? 'right' : '' }}">
                            @if (is_numeric($cell) && $i > 0 && (str_contains(strtolower($data['headings'][$i] ?? ''), 'pendapatan') || str_contains(strtolower($data['headings'][$i] ?? ''), 'total') || str_contains(strtolower($data['headings'][$i] ?? ''), 'harga') || str_contains(strtolower($data['headings'][$i] ?? ''), 'diskon') || str_contains(strtolower($data['headings'][$i] ?? ''), 'revenue') || str_contains(strtolower($data['headings'][$i] ?? ''), 'nilai')))
                                Rp {{ number_format((float) $cell, 0, ',', '.') }}
                            @elseif (is_numeric($cell))
                                {{ number_format((float) $cell, 0, ',', '.') }}
                            @else
                                {{ $cell }}
                            @endif
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($data['headings']) }}" class="empty">Tidak ada data untuk periode ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Dicetak: {{ now()->translatedFormat('d M Y H:i') }} &middot; {{ config('app.name') }}
    </div>
</body>
</html>
