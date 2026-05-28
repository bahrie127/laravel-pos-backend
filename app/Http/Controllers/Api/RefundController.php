<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Http\Responses\ApiResponse;
use App\Models\CashSession;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * V1 refund flow (full-only).
 *
 * Rules:
 *  - Order must currently be status = paid (no double-refund, no pending).
 *  - Reason is required (controlled vocab + free-text note).
 *  - Marks the order refunded; does NOT delete (audit trail).
 *  - Restores product stock for each line item.
 *  - Bumps the original cash session's cash_out so the shift recap balances.
 */
class RefundController extends Controller
{
    public function store(Request $request, Order $order)
    {
        $validated = $request->validate([
            'reason' => ['required', Rule::in([
                'salah_pesan',
                'pesanan_tidak_sesuai',
                'pelanggan_batal',
                'item_habis',
                'lainnya',
            ])],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        if ($order->status !== Order::STATUS_PAID) {
            return ApiResponse::error(
                'Transaksi ini tidak bisa di-refund (status: ' . $order->status . ').',
                422,
            );
        }

        $user = $request->user();

        $refunded = DB::transaction(function () use ($order, $validated, $user) {
            // 1) Mark the order.
            $order->update([
                'status' => Order::STATUS_REFUNDED,
                'refunded_at' => now(),
                'refund_reason' => $validated['reason'],
                'refund_note' => $validated['note'] ?? null,
                'refund_amount' => $order->total_price,
                'refunded_by_user_id' => $user?->id,
            ]);

            // 2) Restore stock for each line item.
            $items = $order->orderItems()->get();
            foreach ($items as $item) {
                if ($item->product_id !== null && $item->quantity > 0) {
                    Product::where('id', $item->product_id)
                        ->increment('stock', $item->quantity);
                }
            }

            // 3) Bump cash_out on the originating shift (if any) so the
            //    closing recap reflects money leaving the drawer.
            if ($order->cash_session_id) {
                CashSession::where('id', $order->cash_session_id)
                    ->increment('cash_out', (int) round((float) $order->total_price));
            }

            return $order->fresh(['orderItems.product', 'kasir']);
        });

        return ApiResponse::success(
            new OrderResource($refunded),
            'Refund berhasil diproses.',
        );
    }
}
