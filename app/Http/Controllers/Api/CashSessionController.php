<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CashSessionCloseRequest;
use App\Http\Requests\Api\CashSessionOpenRequest;
use App\Http\Resources\CashSessionResource;
use App\Http\Responses\ApiResponse;
use App\Models\CashSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CashSessionController extends Controller
{
    /**
     * Paginated history for the auth user (or all users if ?all=1 by admin).
     * Optional filters: ?status=open|closed, ?from=YYYY-MM-DD, ?to=YYYY-MM-DD
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = CashSession::query()->with('user:id,name');

        if (! $request->boolean('all')) {
            $query->forUser($user->id);
        }

        if ($request->filled('status')) {
            if ($request->status === 'open') {
                $query->whereNull('closed_at');
            } elseif ($request->status === 'closed') {
                $query->whereNotNull('closed_at');
            }
        }

        if ($request->filled('from')) {
            $query->whereDate('opened_at', '>=', $request->date('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('opened_at', '<=', $request->date('to'));
        }

        $sessions = $query->latest('opened_at')
            ->limit((int) $request->integer('limit', 50))
            ->get();

        return ApiResponse::success(
            CashSessionResource::collection($sessions),
            'Riwayat shift berhasil dimuat.'
        );
    }

    /**
     * Return the current (open) session for the auth user, or null.
     */
    public function current(Request $request)
    {
        $session = CashSession::currentFor($request->user()->id);
        if ($session) $session->load('user:id,name');

        return ApiResponse::success(
            $session ? new CashSessionResource($session) : null,
            $session ? 'Shift aktif ditemukan.' : 'Belum ada shift aktif.'
        );
    }

    public function show(int $id)
    {
        $session = CashSession::with('user:id,name')->findOrFail($id);

        return ApiResponse::success(
            new CashSessionResource($session),
            'Detail shift.'
        );
    }

    /**
     * Open a new shift. Enforces one open session per user.
     */
    public function open(CashSessionOpenRequest $request)
    {
        $user = $request->user();

        if (CashSession::currentFor($user->id)) {
            return ApiResponse::error(
                'Masih ada shift aktif. Tutup shift sebelumnya dulu.',
                409
            );
        }

        $session = CashSession::create([
            'user_id' => $user->id,
            'shift_label' => $request->shift_label,
            'opening_float' => $request->opening_float,
            'opening_note' => $request->opening_note,
            'opened_at' => now(),
        ]);

        $session->load('user:id,name');

        return ApiResponse::success(
            new CashSessionResource($session),
            'Shift dibuka.',
            201
        );
    }

    /**
     * Close a shift. Computes expected_cash + variance server-side from the
     * recorded cash revenue, so the client cannot fudge reconciliation.
     */
    public function close(CashSessionCloseRequest $request, int $id)
    {
        $user = $request->user();
        $session = CashSession::findOrFail($id);

        if ($session->user_id !== $user->id) {
            return ApiResponse::error('Bukan shift milik kamu.', 403);
        }
        if (! $session->is_open) {
            return ApiResponse::error('Shift ini sudah ditutup.', 409);
        }

        DB::transaction(function () use ($session, $request) {
            $cashIn = (int) $request->input('cash_in', 0);
            $cashOut = (int) $request->input('cash_out', 0);
            $cashRevenue = $session->cashRevenue();

            $expected = $session->opening_float + $cashIn - $cashOut + $cashRevenue;
            $physical = (int) $request->physical_count;
            $variance = $physical - $expected;

            $session->update([
                'cash_in' => $cashIn,
                'cash_out' => $cashOut,
                'expected_cash' => $expected,
                'physical_count' => $physical,
                'variance' => $variance,
                'closing_note' => $request->closing_note,
                'closed_at' => now(),
            ]);
        });

        $session->refresh()->load('user:id,name');

        return ApiResponse::success(
            new CashSessionResource($session),
            'Shift ditutup.'
        );
    }

    /**
     * Admin/owner-only: force-close shift orang lain (kalau kasir lupa
     * tutup shift sebelum pulang). Variance dianggap 0 (balanced), closing
     * note di-tag "[Force-closed oleh {nama admin}]" untuk audit trail.
     */
    public function forceClose(Request $request, int $id)
    {
        $session = CashSession::findOrFail($id);

        if (! $request->user()->can('forceClose', $session)) {
            return ApiResponse::error(
                'Hanya admin/owner yang bisa force-close shift, dan shift harus masih terbuka.',
                403
            );
        }

        DB::transaction(function () use ($session, $request) {
            $cashRevenue = $session->cashRevenue();
            $expected = $session->opening_float
                + (int) $session->cash_in
                - (int) $session->cash_out
                + $cashRevenue;

            $session->update([
                'physical_count' => $expected,  // assume balanced
                'expected_cash' => $expected,
                'variance' => 0,
                'closing_note' => '[Force-closed oleh ' . ($request->user()->name ?? 'admin') . ']',
                'closed_at' => now(),
            ]);
        });

        $session->refresh()->load('user:id,name');

        return ApiResponse::success(
            new CashSessionResource($session),
            'Shift di-force close.'
        );
    }

    /**
     * Aggregate summary for the close-shift reconciliation card:
     * order count, items sold, revenue per method, cash revenue.
     * Convenience endpoint so the client doesn't re-aggregate locally.
     */
    public function summary(Request $request, int $id)
    {
        $user = $request->user();
        $session = CashSession::findOrFail($id);

        if ($session->user_id !== $user->id) {
            return ApiResponse::error('Bukan shift milik kamu.', 403);
        }

        $orders = $session->orders()->where('status', 'paid');

        $summary = [
            'order_count' => (int) $orders->count(),
            'items_sold' => (int) $orders->sum('total_item'),
            'gross_revenue' => (int) $orders->sum('total_price'),
            'cash_revenue' => $session->cashRevenue(),
            'by_method' => $session->revenueByMethod(),
            'expected_cash' => $session->opening_float
                + (int) $session->cash_in
                - (int) $session->cash_out
                + $session->cashRevenue(),
        ];

        return ApiResponse::success($summary, 'Ringkasan shift.');
    }
}
