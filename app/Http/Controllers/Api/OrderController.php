<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ApiOrderStoreRequest;
use App\Http\Resources\OrderResource;
use App\Http\Responses\ApiResponse;
use App\Models\CashSession;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Promo;
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

        // Auto-attach the cashier's open shift. Coffeeshops should not
        // accept new orders outside an open shift — block here.
        $session = CashSession::currentFor((int) $data['kasir_id']);
        if (! $session) {
            return ApiResponse::error(
                'Belum ada shift aktif untuk kasir ini. Buka kasir dulu.',
                409
            );
        }

        // Server-authoritative discount: if a promo_id is supplied, recompute
        // the discount from the BE-stored promo so the client can't fake a
        // bigger discount than the rules allow.
        $promoId = $data['promo_id'] ?? null;
        $subtotal = (int) ($data['subtotal'] ?? $data['total_price']);
        $discountAmount = (int) ($data['discount_amount'] ?? 0);
        if ($promoId) {
            $promo = Promo::find($promoId);
            if ($promo) {
                $serverDiscount = $promo->computeDiscount($subtotal);
                // Cap the client's claim at what the BE computes.
                $discountAmount = min($discountAmount, $serverDiscount);
            }
        }

        $order = DB::transaction(function () use ($data, $items, $session, $promoId, $discountAmount) {
            $order = Order::create([
                'transaction_time' => $data['transaction_time'],
                'kasir_id' => $data['kasir_id'],
                'cash_session_id' => $session->id,
                'promo_id' => $promoId,
                'total_price' => $data['total_price'],
                'total_item' => $data['total_item'],
                'payment_method' => $data['payment_method'] ?? null,
                'subtotal' => $data['subtotal'] ?? $data['total_price'],
                'discount' => $data['discount'] ?? 0,
                'discount_amount' => $discountAmount,
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

                // V1 stock decrement: mirror the FE-side decrement so the
                // catalog reflects sales. We don't block on negative stock
                // (race conditions are surfaced in reports rather than
                // failing transactions for the cashier).
                if (! empty($item['product_id']) && ($item['quantity'] ?? 0) > 0) {
                    Product::where('id', $item['product_id'])
                        ->decrement('stock', (int) $item['quantity']);
                }
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
