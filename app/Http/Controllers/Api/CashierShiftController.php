<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CashierShift;
use App\Models\Order;

class CashierShiftController extends Controller
{


    public function openShift(Request $request)
    {
        $request->validate([
            'open_amount' => 'required|numeric|min:0',
            'user_id' => 'required',
        ]);

        $cashierId = $request->user_id;

        // Check if there's already an open shift
        if (CashierShift::where('cashier_id', $cashierId)->where('status', 'open')->exists()) {
            return response()->json(['message' => 'You already have an open shift.'], 400);
        }

        $shift = CashierShift::create([
            'cashier_id' => $cashierId,
            'open_amount' => $request->open_amount,
            'opened_at' => now(),
            'status' => 'open',
        ]);

        return response()->json(['message' => 'Shift opened successfully.', 'shift' => $shift]);
}

    public function closeShift(Request $request)
    {
        $request->validate([
            'close_amount' => 'required|numeric|min:0',
            'user_id' => 'required',
        ]);

        $cashierId = $request->user_id;

        $shift = CashierShift::where('cashier_id', $cashierId)->where('status', 'open')->first();

        if (!$shift) {
            return response()->json(['message' => 'No open shift found.'], 404);
        }
        $orders = Order::where('kasir_id', $cashierId)
            ->where('payment_method', 'Tunai')
            ->whereBetween('created_at', [$shift->opened_at, now()])
            ->get();

        $shift->update([
            'close_amount' => $request->close_amount,
            'cash_sales' => $orders->sum('total_price'),
            'closed_at' => now(),
            'status' => 'closed',
        ]);

        return response()->json([
            'message' => 'Shift closed successfully.',
            'shift' => $shift,
            'difference' => $shift->difference
        ]);
    }


    public function getShiftOpen(Request $request)
    {
        $cashierId = $request->user_id;
        $shift = CashierShift::where('cashier_id', $cashierId)
            ->where('status', 'open')
            ->first();

        if (!$shift) {
            return response()->json([
                'shift' => null,
                'message' => 'No open shift found.'], 404);
        }

        return response()->json(['shift' => $shift]);
    }

    // get shift by id
    public function getShiftById($id)
    {
        $shift = CashierShift::find($id);

        if (!$shift) {
            return response()->json(['message' => 'Shift not found.'], 404);
        }

        return response()->json(['shift' => $shift]);
    }


}
