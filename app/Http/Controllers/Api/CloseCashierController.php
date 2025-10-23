<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Mail\OrderShipped;
use Resend\Laravel\Facades\Resend;
use App\Models\User;
use Illuminate\Support\Facades\DB;


class CloseCashierController extends Controller
{
    //close
    public function close(Request $request)
    {
        //report order today
        $start_date = date('Y-m-d 00:00:00');
        $end_date = date('Y-m-d 23:59:59');
        $query = Order::query()
            ->whereBetween('created_at', [$start_date, $end_date]);


        $orders = $query->get();

        $totalRevenue = $orders->sum('total_price');
        $totalDiscount = 0;
        $totalTax = 0;
        $totalServiceCharge = 0;
        $totalSubtotal = $orders->sum('total_price');
        $total = $totalSubtotal - $totalDiscount + $totalTax + $totalServiceCharge;
        $totalSoldQuantity = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereBetween('orders.created_at', [$start_date, $end_date])
            ->sum('order_items.quantity');
        $data = [
            'total_revenue' => $totalRevenue,
            'total_discount' => $totalDiscount,
            'total_tax' => $totalTax,
            'total_service_charge' => $totalServiceCharge,
            'total_subtotal' => $totalSubtotal,
            'total' => $total,
            'total_sold_quantity' => $totalSoldQuantity
        ];

        // $start_date = Carbon::parse($request->start_date)->startOfDay();
        // $end_date = Carbon::parse($request->end_date)->endOfDay();

        $query = OrderItem::select(
            'products.id as product_id',
            'products.name as product_name',
            'products.price as product_price',
            DB::raw('SUM(order_items.quantity) as total_quantity'),
            DB::raw('SUM(order_items.total_price) as total_price')
        )
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->whereBetween(DB::raw('DATE(order_items.created_at)'), [$start_date, $end_date])
            ->groupBy('products.id', 'products.name', 'products.price')
            ->orderBy('total_quantity', 'desc');

        $totalProductSold = $query->get();

        $admin = User::where('roles', 'admin')->first();

        Resend::emails()->send([
            'from' => 'Code with Bahri <onboarding@resend.dev>',
            'to' => [$admin->email],
            'subject' => 'Report Order Today ' . date('Y-m-d'),
            'html' => (new OrderShipped($data, $totalProductSold))->render(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cashier closed successfully',

        ], 200);
    }
}
