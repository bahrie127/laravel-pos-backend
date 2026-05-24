<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ApiOrderStoreRequest;
use App\Http\Resources\OrderResource;
use App\Http\Responses\ApiResponse;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = Order::query()
            ->with('kasir:id,name')
            ->when($request->filled('kasir_id'), fn ($q) => $q->where('kasir_id', $request->kasir_id))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest('transaction_time')
            ->get();

        return ApiResponse::success(
            OrderResource::collection($orders),
            'List pesanan berhasil dimuat.'
        );
    }

    public function show(Order $order)
    {
        $order->load(['kasir:id,name', 'orderItems.product']);

        return ApiResponse::success(new OrderResource($order), 'Detail pesanan.');
    }

    public function store(ApiOrderStoreRequest $request)
    {
        $data = $request->validated();
        $items = $data['order_items'];
        unset($data['order_items']);

        $order = DB::transaction(function () use ($data, $items) {
            $order = Order::create([
                'transaction_time' => $data['transaction_time'],
                'kasir_id' => $data['kasir_id'],
                'total_price' => $data['total_price'],
                'total_item' => $data['total_item'],
                'payment_method' => $data['payment_method'] ?? null,
                'subtotal' => $data['subtotal'] ?? $data['total_price'],
                'discount' => $data['discount'] ?? 0,
                'tax' => $data['tax'] ?? 0,
                'amount_paid' => $data['amount_paid'] ?? 0,
                'change_amount' => $data['change_amount'] ?? 0,
                'customer_name' => $data['customer_name'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($items as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'total_price' => $item['total_price'],
                ]);
            }

            return $order;
        });

        $order->load(['kasir:id,name', 'orderItems.product']);

        return ApiResponse::success(new OrderResource($order), 'Pesanan berhasil dibuat.', 201);
    }

    public function getByKasirId($kasirId)
    {
        $orders = Order::where('kasir_id', $kasirId)
            ->with('kasir:id,name')
            ->latest('transaction_time')
            ->get();

        return ApiResponse::success(
            OrderResource::collection($orders),
            'List pesanan kasir berhasil dimuat.'
        );
    }
}
