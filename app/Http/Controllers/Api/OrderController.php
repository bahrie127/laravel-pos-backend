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
        $user = $request->user();

        $query = Order::query()
            ->with('kasir:id,name')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status));

        // Kasir (non-admin) hanya lihat order miliknya. Admin/Owner bisa filter via ?kasir_id.
        if (! $user->isAdmin()) {
            $query->where('kasir_id', $user->id);
        } elseif ($request->filled('kasir_id')) {
            $query->where('kasir_id', $request->kasir_id);
        }

        $orders = $query->latest('transaction_time')->get();

        return ApiResponse::success(
            OrderResource::collection($orders),
            'List pesanan berhasil dimuat.'
        );
    }

    public function show(Request $request, Order $order)
    {
        // OrderPolicy::view → admin lihat semua, kasir hanya lihat order miliknya.
        if (! $request->user()->can('view', $order)) {
            return ApiResponse::error('Anda tidak memiliki izin melihat pesanan ini.', 403);
        }

        $order->load(['kasir:id,name', 'orderItems.product']);

        return ApiResponse::success(new OrderResource($order), 'Detail pesanan.');
    }

    public function store(ApiOrderStoreRequest $request)
    {
        $data = $request->validated();
        $items = $data['order_items'];
        unset($data['order_items']);

        // === Idempotency check ===
        // Kalau Flutter retry karena network blip, payload sama dengan client_uuid sama.
        // Return existing order tanpa double-process. Kasir tidak akan kehilangan
        // order, BE tidak duplikat.
        $clientUuid = $data['client_uuid'] ?? null;
        if ($clientUuid) {
            $existing = Order::where('client_uuid', $clientUuid)->first();
            if ($existing) {
                $existing->load(['kasir:id,name', 'orderItems.product']);
                return ApiResponse::success(
                    new OrderResource($existing),
                    'Pesanan sudah ada (idempotent).',
                    200
                );
            }
        }

        // Auto-attach the cashier's open shift. If the client already
        // supplies a cash_session_id (e.g. syncing orders after close),
        // trust it — the order was created during that shift locally.
        $session = CashSession::currentFor((int) $data['kasir_id']);
        if (! $session && ! empty($data['cash_session_id'])) {
            $session = CashSession::find($data['cash_session_id']);
        }
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

        $order = DB::transaction(function () use ($data, $items, $session, $promoId, $discountAmount, $clientUuid) {
            // Lock semua produk yang dibeli supaya tidak ada race condition
            // (2 order paralel ambil produk sama → stock minus).
            $productIds = collect($items)->pluck('product_id')->filter()->unique()->values()->all();
            if (! empty($productIds)) {
                Product::whereIn('id', $productIds)->lockForUpdate()->get();
            }

            $order = Order::create([
                'client_uuid' => $clientUuid,
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

                // Stock decrement di dalam transaction + lockForUpdate di atas
                // memastikan tidak ada race condition.
                // Note: tetap tidak block kalau stok < qty (V1 — offline-first
                // tidak bisa sync block; report akan surface kasus stok minus).
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

    public function getByKasirId(Request $request, $kasirId)
    {
        $user = $request->user();
        // Kasir hanya boleh akses dirinya sendiri; admin/owner bebas.
        if (! $user->isAdmin() && (int) $kasirId !== (int) $user->id) {
            return ApiResponse::error('Anda tidak memiliki izin akses data kasir lain.', 403);
        }

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
