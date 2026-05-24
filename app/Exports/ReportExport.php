<?php

namespace App\Exports;

use App\Models\Order;
use App\Models\OrderItem;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReportExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize
{
    public function __construct(
        private string $type,
        private array $filters
    ) {
    }

    public function collection()
    {
        $from = Carbon::parse($this->filters['from']);
        $to = Carbon::parse($this->filters['to']);

        return match ($this->type) {
            'summary' => $this->summaryRows($from, $to),
            'product-sales' => $this->productSalesRows($from, $to),
            'close-cashier' => $this->closeCashierRows($from, $to),
            default => collect(),
        };
    }

    public function headings(): array
    {
        return match ($this->type) {
            'summary' => ['Tanggal', 'Jumlah Order', 'Total Pendapatan'],
            'product-sales' => ['Rank', 'Produk', 'Kategori', 'Qty Terjual', 'Pendapatan', '% Total'],
            'close-cashier' => ['Pembayaran', 'Jumlah Order', 'Total'],
            default => [],
        };
    }

    public function styles(Worksheet $sheet)
    {
        return [1 => ['font' => ['bold' => true]]];
    }

    private function summaryRows(Carbon $from, Carbon $to)
    {
        $query = Order::query()
            ->whereBetween('transaction_time', [$from, $to])
            ->when(! empty($this->filters['kasir_id']), fn ($q) => $q->where('kasir_id', $this->filters['kasir_id']))
            ->selectRaw('DATE(transaction_time) as date, COUNT(*) as orders, SUM(total_price) as total')
            ->groupBy('date')
            ->orderBy('date');

        return $query->get()->map(fn ($r) => [
            $r->date,
            $r->orders,
            (int) $r->total,
        ]);
    }

    private function productSalesRows(Carbon $from, Carbon $to)
    {
        $items = OrderItem::query()
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->whereBetween('orders.transaction_time', [$from, $to])
            ->selectRaw('products.name, products.category, SUM(order_items.quantity) as qty, SUM(order_items.total_price) as revenue')
            ->groupBy('products.id', 'products.name', 'products.category')
            ->orderByDesc('revenue')
            ->get();

        $total = (float) $items->sum('revenue');

        return $items->values()->map(fn ($r, $i) => [
            $i + 1,
            $r->name,
            $r->category ?? '—',
            (int) $r->qty,
            (int) $r->revenue,
            $total > 0 ? round(($r->revenue / $total) * 100, 1) . '%' : '0%',
        ]);
    }

    private function closeCashierRows(Carbon $from, Carbon $to)
    {
        if (empty($this->filters['kasir_id'])) {
            return collect();
        }

        return Order::where('kasir_id', $this->filters['kasir_id'])
            ->whereBetween('transaction_time', [$from, $to])
            ->selectRaw('payment_method, COUNT(*) as orders, SUM(total_price) as total')
            ->groupBy('payment_method')
            ->get()
            ->map(fn ($r) => [
                strtoupper($r->payment_method ?? '—'),
                $r->orders,
                (int) $r->total,
            ]);
    }
}
