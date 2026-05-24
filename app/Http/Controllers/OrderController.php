<?php

namespace App\Http\Controllers;

use App\Exports\OrdersExport;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::query()
            ->with('kasir:id,name')
            ->when($request->filled('q'), function ($q) use ($request) {
                $q->where(function ($qq) use ($request) {
                    $term = '%' . $request->q . '%';
                    $qq->where('order_number', 'like', $term)
                       ->orWhere('customer_name', 'like', $term);
                });
            })
            ->when($request->filled('date_from'), fn ($q) => $q->where('transaction_time', '>=', Carbon::parse($request->date_from)->startOfDay()))
            ->when($request->filled('date_to'), fn ($q) => $q->where('transaction_time', '<=', Carbon::parse($request->date_to)->endOfDay()))
            ->when($request->filled('payment_method'), fn ($q) => $q->where('payment_method', $request->payment_method))
            ->when($request->filled('kasir_id'), fn ($q) => $q->where('kasir_id', $request->kasir_id))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status));

        // Kasir hanya lihat order miliknya
        if (auth()->user() && ! auth()->user()->isAdmin()) {
            $query->where('kasir_id', auth()->id());
        }

        $orders = (clone $query)->latest('transaction_time')->paginate(15)->withQueryString();
        $totalRevenue = (float) (clone $query)->sum('total_price');
        $totalOrders = (clone $query)->count();

        $kasirList = User::orderBy('name')->get(['id', 'name']);
        $paymentMethods = Order::query()
            ->whereNotNull('payment_method')
            ->distinct()
            ->pluck('payment_method')
            ->filter()
            ->values();

        return view('pages.orders.index', compact('orders', 'totalRevenue', 'totalOrders', 'kasirList', 'paymentMethods'));
    }

    public function show(Order $order)
    {
        $this->authorize('view', $order);
        $order->load('kasir:id,name');
        $orderItems = OrderItem::with('product')->where('order_id', $order->id)->get();

        return view('pages.orders.view', compact('order', 'orderItems'));
    }

    public function destroy(Order $order)
    {
        $this->authorize('delete', $order);
        $order->delete();

        return redirect()->route('order.index')->with('success', 'Pesanan berhasil dihapus.');
    }

    public function receipt(Order $order)
    {
        $this->authorize('view', $order);
        $order->load('kasir:id,name');
        $orderItems = OrderItem::with('product')->where('order_id', $order->id)->get();

        return view('pages.orders.receipt', compact('order', 'orderItems'));
    }

    public function invoicePdf(Order $order)
    {
        $this->authorize('view', $order);
        $order->load('kasir:id,name');
        $orderItems = OrderItem::with('product')->where('order_id', $order->id)->get();

        $pdf = Pdf::loadView('pages.orders.invoice-pdf', compact('order', 'orderItems'))
            ->setPaper('a4');

        return $pdf->download($order->order_number . '.pdf');
    }

    public function export(Request $request)
    {
        $filters = $request->only(['q', 'date_from', 'date_to', 'payment_method', 'kasir_id', 'status']);
        $filename = 'orders-' . now()->format('Ymd-His') . '.xlsx';

        return Excel::download(new OrdersExport($filters), $filename);
    }
}
