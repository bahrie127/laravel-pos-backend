<?php

namespace App\Exports;

use App\Models\Order;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OrdersExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    public function __construct(private array $filters = [])
    {
    }

    public function query()
    {
        return Order::query()
            ->with('kasir:id,name')
            ->when(! empty($this->filters['q']), function ($q) {
                $q->where(function ($qq) {
                    $term = '%' . $this->filters['q'] . '%';
                    $qq->where('order_number', 'like', $term)
                       ->orWhere('customer_name', 'like', $term);
                });
            })
            ->when(! empty($this->filters['date_from']), fn ($q) => $q->where('transaction_time', '>=', Carbon::parse($this->filters['date_from'])->startOfDay()))
            ->when(! empty($this->filters['date_to']), fn ($q) => $q->where('transaction_time', '<=', Carbon::parse($this->filters['date_to'])->endOfDay()))
            ->when(! empty($this->filters['payment_method']), fn ($q) => $q->where('payment_method', $this->filters['payment_method']))
            ->when(! empty($this->filters['kasir_id']), fn ($q) => $q->where('kasir_id', $this->filters['kasir_id']))
            ->when(! empty($this->filters['status']), fn ($q) => $q->where('status', $this->filters['status']))
            ->latest('transaction_time');
    }

    public function headings(): array
    {
        return ['Order #', 'Tanggal', 'Kasir', 'Customer', 'Item', 'Subtotal', 'Diskon', 'Pajak', 'Total', 'Pembayaran', 'Status'];
    }

    public function map($order): array
    {
        return [
            $order->order_number,
            optional($order->transaction_time)->format('d/m/Y H:i'),
            $order->kasir->name ?? '—',
            $order->customer_name ?? '—',
            $order->total_item,
            (int) $order->subtotal,
            (int) $order->discount,
            (int) $order->tax,
            (int) $order->total_price,
            strtoupper($order->payment_method ?? '—'),
            ucfirst($order->status ?? '—'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
