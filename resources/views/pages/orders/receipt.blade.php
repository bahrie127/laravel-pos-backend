<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Struk {{ $order->order_number ?? '#' . $order->id }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Courier New', Consolas, monospace;
            font-size: 12px;
            line-height: 1.4;
            max-width: 80mm;
            margin: 0 auto;
            padding: 8px;
            color: #000;
        }
        .center { text-align: center; }
        .right { text-align: right; }
        hr { border: 0; border-top: 1px dashed #000; margin: 6px 0; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 2px 0; vertical-align: top; }
        .item-row td { padding-bottom: 4px; }
        h1 { font-size: 14px; margin: 0; }
        .small { font-size: 10px; }
        .actions { padding: 8px; text-align: center; }
        @media print {
            .actions { display: none; }
            body { padding: 0; }
            @page { margin: 0; size: 80mm auto; }
        }
    </style>
</head>
<body>
    <div class="actions no-print">
        <button onclick="window.print()" style="padding:8px 16px;">Cetak</button>
        <button onclick="window.close()" style="padding:8px 16px;">Tutup</button>
    </div>

    <div class="center">
        <h1>{{ config('app.name') }}</h1>
        <div class="small">Struk Pembelian</div>
    </div>
    <hr>

    <table>
        <tr>
            <td>No.</td>
            <td class="right">{{ $order->order_number ?? '#' . $order->id }}</td>
        </tr>
        <tr>
            <td>Waktu</td>
            <td class="right">{{ formatDate($order->transaction_time, 'd/m/Y H:i') }}</td>
        </tr>
        <tr>
            <td>Kasir</td>
            <td class="right">{{ $order->kasir->name ?? '—' }}</td>
        </tr>
        @if ($order->customer_name)
            <tr>
                <td>Customer</td>
                <td class="right">{{ $order->customer_name }}</td>
            </tr>
        @endif
    </table>
    <hr>

    @foreach ($orderItems as $item)
        <table class="item-row">
            <tr>
                <td colspan="2"><strong>{{ $item->product->name ?? '—' }}</strong></td>
            </tr>
            <tr>
                <td>{{ $item->quantity }} x {{ rupiah($item->product->price ?? 0, false) }}</td>
                <td class="right">{{ rupiah($item->total_price, false) }}</td>
            </tr>
        </table>
    @endforeach
    <hr>

    <table>
        <tr>
            <td>Subtotal</td>
            <td class="right">{{ rupiah($order->subtotal ?? $order->total_price, false) }}</td>
        </tr>
        @if ($order->discount > 0)
            <tr>
                <td>Diskon</td>
                <td class="right">-{{ rupiah($order->discount, false) }}</td>
            </tr>
        @endif
        @if ($order->tax > 0)
            <tr>
                <td>Pajak</td>
                <td class="right">{{ rupiah($order->tax, false) }}</td>
            </tr>
        @endif
        <tr style="font-size:14px;font-weight:bold;">
            <td>TOTAL</td>
            <td class="right">{{ rupiah($order->total_price, false) }}</td>
        </tr>
        <tr>
            <td>Pembayaran</td>
            <td class="right">{{ strtoupper($order->payment_method ?? '—') }}</td>
        </tr>
        @if ($order->amount_paid > 0)
            <tr>
                <td>Bayar</td>
                <td class="right">{{ rupiah($order->amount_paid, false) }}</td>
            </tr>
            <tr>
                <td>Kembali</td>
                <td class="right">{{ rupiah($order->change_amount, false) }}</td>
            </tr>
        @endif
    </table>
    <hr>

    <div class="center">
        Terima kasih atas kunjungan Anda!
        <div class="small">{{ $order->order_number ?? '' }}</div>
    </div>

    <script>
        // Auto print kalau buka via cetak
        window.addEventListener('load', () => {
            if (window.location.search.includes('autoprint')) {
                setTimeout(() => window.print(), 200);
            }
        });
    </script>
</body>
</html>
