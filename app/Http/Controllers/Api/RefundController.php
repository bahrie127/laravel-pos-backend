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
        // Hanya Owner yang boleh refund (OrderPolicy::refund). Web admin & API mobile keduanya guarded.
        if (! $request->user()->can('refund', $order)) {
            return ApiResponse::error('Anda tidak memiliki izin untuk melakukan refund.', 403);
        }

        $validated = $request->validate([
            'reason' => ['required', Rule::in([
                'salah_pesan',
                'pesanan_tidak_sesuai',
                'pelanggan_batal',
                'item_habis',
                'lainnya',
            ])],
            'note' => ['nullable', 'string', 'max:500'],
            // Flag dari Flutter: kalau FE sudah local-bump cash_out (offline path),
            // skip BE bump supaya tidak double-debit. Default false (BE yang bump).
            'cash_out_already_bumped' => ['nullable', 'boolean'],
            // Flag dari Flutter: kalau FE sudah local-restore stock, skip BE restore.
            'stock_already_restored' => ['nullable', 'boolean'],
        ]);

        if ($order->status !== Order::STATUS_PAID) {
            return ApiResponse::error(
                'Transaksi ini tidak bisa di-refund (status: ' . $order->status . ').',
                422,
            );
        }

        $user = $request->user();
        $skipStockRestore = (bool) ($validated['stock_already_restored'] ?? false);
        $skipCashOutBump = (bool) ($validated['cash_out_already_bumped'] ?? false);

        $refunded = DB::transaction(function () use ($order, $validated, $user, $skipStockRestore, $skipCashOutBump) {
            // 1) Mark the order.
            $order->update([
                'status' => Order::STATUS_REFUNDED,
                'refunded_at' => now(),
                'refund_reason' => $validated['reason'],
                'refund_note' => $validated['note'] ?? null,
                'refund_amount' => $order->total_price,
                'refunded_by_user_id' => $user?->id,
            ]);

            // 2) Restore stock — skip kalau FE sudah local-restore (avoid double-restore).
            if (! $skipStockRestore) {
                $items = $order->orderItems()->get();
                foreach ($items as $item) {
                    if ($item->product_id !== null && $item->quantity > 0) {
                        Product::where('id', $item->product_id)
                            ->increment('stock', $item->quantity);
                    }
                }
            }

            // 3) Bump cash_out on the originating shift — skip kalau:
            //    - FE sudah local-bump (avoid double-debit), atau
            //    - shift sudah closed (jangan corrupt variance shift lama).
            if (! $skipCashOutBump && $order->cash_session_id) {
                $session = CashSession::find($order->cash_session_id);
                if ($session && is_null($session->closed_at)) {
                    $session->increment('cash_out', (int) round((float) $order->total_price));
                }
                // Kalau shift sudah closed: refund tetap dicatat di order, tapi
                // variance shift lama tidak diubah. Owner perlu pisahkan refund
                // dari kas harian (lihat report refund).
            }

            return $order->fresh(['orderItems.product', 'kasir']);
        });

        return ApiResponse::success(
            new OrderResource($refunded),
            'Refund berhasil diproses.',
        );
    }
}
