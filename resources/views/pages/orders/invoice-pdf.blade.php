<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $order->order_number }}</title>
    <style>
        @page { margin: 24mm 18mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; padding-bottom: 12px; border-bottom: 2px solid #3B82F6; }
        .brand { font-size: 18px; font-weight: bold; color: #3B82F6; }
        .invoice-title { font-size: 20px; font-weight: bold; text-align: right; }
        .meta-table { width: 100%; margin-bottom: 20px; }
        .meta-table td { padding: 3px 0; vertical-align: top; }
        .meta-label { color: #6B7280; font-size: 10px; text-transform: uppercase; }
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        .items-table th { background: #F3F4F6; padding: 8px; text-align: left; font-weight: bold; border-bottom: 2px solid #E5E7EB; }
        .items-table td { padding: 8px; border-bottom: 1px solid #E5E7EB; }
        .right { text-align: right; }
        .center { text-align: center; }
        .summary-table { width: 280px; margin-left: auto; }
        .summary-table td { padding: 4px 0; }
        .summary-total { border-top: 2px solid #111; font-size: 14px; font-weight: bold; }
        .footer { margin-top: 40px; text-align: center; color: #6B7280; font-size: 10px; }
        .badge { display: inline-block; padding: 3px 8px; border-radius: 12px; font-size: 9px; font-weight: bold; }
        .badge-paid { background: #D1FAE5; color: #047857; }
        .badge-pending { background: #FEF3C7; color: #B45309; }
        .badge-cancelled { background: #FEE2E2; color: #B91C1C; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <div class="brand">{{ config('app.name') }}</div>
            <div style="color:#6B7280;margin-top:4px;">Sistem Point of Sale</div>
        </div>
        <div>
            <div class="invoice-title">INVOICE</div>
            <div style="margin-top:4px;color:#6B7280;">{{ $order->order_number }}</div>
        </div>
    </div>

    <table class="meta-table">
        <tr>
            <td style="width:50%;">
                <div class="meta-label">Tanggal Transaksi</div>
                <div><strong>{{ formatDate($order->transaction_time, 'd F Y H:i') }}</strong></div>
            </td>
            <td>
                <div class="meta-label">Status</div>
                <div>
                    @php $bg = match($order->status){'paid'=>'badge-paid','pending'=>'badge-pending','cancelled'=>'badge-cancelled',default=>'badge-paid'}; @endphp
                    <span class="badge {{ $bg }}">{{ strtoupper($order->statusLabel()) }}</span>
                </div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="meta-label">Kasir</div>
                <div><strong>{{ $order->kasir->name ?? '—' }}</strong></div>
            </td>
            <td>
                <div class="meta-label">Customer</div>
                <div><strong>{{ $order->customer_name ?? '—' }}</strong></div>
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width:50px;">No</th>
                <th>Produk</th>
                <th class="right" style="width:90px;">Harga</th>
                <th class="center" style="width:60px;">Qty</th>
                <th class="right" style="width:100px;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($orderItems as $i => $item)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td><strong>{{ $item->product->name ?? '—' }}</strong></td>
                    <td class="right">{{ rupiah($item->product->price ?? 0) }}</td>
                    <td class="center">{{ $item->quantity }}</td>
                    <td class="right"><strong>{{ rupiah($item->total_price) }}</strong></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="summary-table">
        <tr>
            <td>Subtotal</td>
            <td class="right">{{ rupiah($order->subtotal ?? $order->total_price) }}</td>
        </tr>
        @if ($order->discount > 0)
            <tr>
                <td>Diskon</td>
                <td class="right" style="color:#DC2626;">-{{ rupiah($order->discount) }}</td>
            </tr>
        @endif
        @if ($order->tax > 0)
            <tr>
                <td>Pajak</td>
                <td class="right">{{ rupiah($order->tax) }}</td>
            </tr>
        @endif
        <tr class="summary-total">
            <td>TOTAL</td>
            <td class="right">{{ rupiah($order->total_price) }}</td>
        </tr>
        <tr>
            <td style="padding-top:8px;">Pembayaran</td>
            <td class="right" style="padding-top:8px;">{{ strtoupper($order->payment_method ?? '—') }}</td>
        </tr>
        @if ($order->amount_paid > 0)
            <tr>
                <td>Dibayar</td>
                <td class="right">{{ rupiah($order->amount_paid) }}</td>
            </tr>
            <tr>
                <td>Kembalian</td>
                <td class="right">{{ rupiah($order->change_amount) }}</td>
            </tr>
        @endif
    </table>

    @if ($order->notes)
        <div style="margin-top:24px;padding:12px;background:#F9FAFB;border-radius:6px;">
            <div class="meta-label">Catatan</div>
            <div>{{ $order->notes }}</div>
        </div>
    @endif

    <div class="footer">
        Dicetak: {{ now()->translatedFormat('d F Y H:i') }} &mdash; {{ config('app.name') }}
    </div>
</body>
</html>
