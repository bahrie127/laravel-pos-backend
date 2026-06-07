<?php

namespace App\Http\Controllers;

use App\Models\CashSession;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CashSessionController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = CashSession::query()->with('user:id,name')->withCount('orders');

        // Non-admin hanya bisa lihat shift sendiri
        if (! $user->isAdmin()) {
            $query->forUser($user->id);
        } elseif ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('status')) {
            $request->status === 'open' ? $query->open() : $query->whereNotNull('closed_at');
        }

        if ($request->filled('date_from')) {
            $query->whereDate('opened_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('opened_at', '<=', $request->date_to);
        }

        $sessions = $query->latest('opened_at')->paginate(20)->withQueryString();

        // Stat cards: shift aktif sekarang + summary periode
        $stats = [
            'open_now' => CashSession::open()->count(),
            'closed_today' => CashSession::whereNotNull('closed_at')->whereDate('closed_at', today())->count(),
            'variance_today_sum' => (int) CashSession::whereNotNull('closed_at')
                ->whereDate('closed_at', today())
                ->sum('variance'),
            'cash_revenue_today' => (int) Order::whereDate('transaction_time', today())
                ->where('payment_method', 'Tunai')
                ->where('status', Order::STATUS_PAID)
                ->sum('total_price'),
        ];

        $kasirList = $user->isAdmin() ? User::orderBy('name')->get(['id', 'name']) : collect();

        $mySession = CashSession::currentFor($user->id);

        return view('pages.cash-sessions.index', compact('sessions', 'stats', 'kasirList', 'mySession'));
    }

    /**
     * Buka shift baru untuk user yang login (normal flow, bukan admin override).
     * Sama dengan API CashSessionController::open tapi return redirect.
     */
    public function open(Request $request)
    {
        $user = $request->user();

        if (CashSession::currentFor($user->id)) {
            return back()->with('error', 'Anda masih punya shift aktif. Tutup dulu sebelum buka baru.');
        }

        $validated = $request->validate([
            'shift_label' => ['required', 'string', Rule::in(CashSession::SHIFTS)],
            'opening_float' => ['required', 'integer', 'min:0'],
            'opening_note' => ['nullable', 'string', 'max:500'],
        ]);

        $session = CashSession::create([
            'user_id' => $user->id,
            'shift_label' => $validated['shift_label'],
            'opening_float' => $validated['opening_float'],
            'opening_note' => $validated['opening_note'] ?? null,
            'opened_at' => now(),
        ]);

        return redirect()->route('cash-session.show', $session->id)
            ->with('success', 'Shift dibuka. Selamat bekerja!');
    }

    /**
     * Tutup shift milik sendiri (bukan force-close admin). Server menghitung
     * expected & variance dari cash revenue tercatat — client tidak diizinkan
     * setting expected sendiri biar rekonsiliasi tidak bisa dimanipulasi.
     */
    public function close(Request $request, CashSession $cashSession)
    {
        $user = $request->user();

        if ($cashSession->user_id !== $user->id) {
            abort(403, 'Bukan shift milik Anda.');
        }
        if (! $cashSession->is_open) {
            return back()->with('error', 'Shift ini sudah ditutup.');
        }

        $validated = $request->validate([
            'physical_count' => ['required', 'integer', 'min:0'],
            'cash_in' => ['nullable', 'integer', 'min:0'],
            'cash_out' => ['nullable', 'integer', 'min:0'],
            'closing_note' => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($cashSession, $validated) {
            $cashIn = (int) ($validated['cash_in'] ?? 0);
            $cashOut = (int) ($validated['cash_out'] ?? 0);
            $cashRevenue = $cashSession->cashRevenue();

            $expected = $cashSession->opening_float + $cashIn - $cashOut + $cashRevenue;
            $physical = (int) $validated['physical_count'];

            $cashSession->update([
                'cash_in' => $cashIn,
                'cash_out' => $cashOut,
                'expected_cash' => $expected,
                'physical_count' => $physical,
                'variance' => $physical - $expected,
                'closing_note' => $validated['closing_note'] ?? null,
                'closed_at' => now(),
            ]);
        });

        return redirect()->route('cash-session.show', $cashSession->id)
            ->with('success', 'Shift berhasil ditutup.');
    }

    public function show(CashSession $cashSession)
    {
        $this->authorize('view', $cashSession);

        $session = $cashSession->load('user:id,name');

        $orders = $session->orders()
            ->where('status', Order::STATUS_PAID)
            ->latest('transaction_time')
            ->get();

        $byMethod = $session->revenueByMethod();
        $cashRevenue = $session->cashRevenue();

        // Recompute live so late-synced orders are reflected correctly.
        $expectedCash = $session->opening_float
            + (int) $session->cash_in
            - (int) $session->cash_out
            + $cashRevenue;

        $liveVariance = $session->physical_count !== null
            ? (int) $session->physical_count - $expectedCash
            : null;

        return view('pages.cash-sessions.show', compact(
            'session', 'orders', 'byMethod', 'cashRevenue', 'expectedCash', 'liveVariance'
        ));
    }

    /**
     * Admin force-close untuk shift yang lupa ditutup kasir.
     * Mirip API close() tapi tanpa user-id check (admin override).
     */
    public function forceClose(Request $request, CashSession $cashSession)
    {
        $this->authorize('forceClose', $cashSession);

        $validated = $request->validate([
            'physical_count' => ['required', 'integer', 'min:0'],
            'cash_in' => ['nullable', 'integer', 'min:0'],
            'cash_out' => ['nullable', 'integer', 'min:0'],
            'closing_note' => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($cashSession, $validated, $request) {
            $cashIn = (int) ($validated['cash_in'] ?? 0);
            $cashOut = (int) ($validated['cash_out'] ?? 0);
            $cashRevenue = $cashSession->cashRevenue();

            $expected = $cashSession->opening_float + $cashIn - $cashOut + $cashRevenue;
            $physical = (int) $validated['physical_count'];

            $note = trim($validated['closing_note'] ?? '');
            $note = $note === '' ? null : $note;
            $forcedTag = "[Force-closed oleh {$request->user()->name}]";
            $note = $note ? $forcedTag . ' ' . $note : $forcedTag;

            $cashSession->update([
                'cash_in' => $cashIn,
                'cash_out' => $cashOut,
                'expected_cash' => $expected,
                'physical_count' => $physical,
                'variance' => $physical - $expected,
                'closing_note' => $note,
                'closed_at' => now(),
            ]);
        });

        return redirect()->route('cash-session.show', $cashSession->id)
            ->with('success', 'Shift berhasil di-force-close.');
    }
}
