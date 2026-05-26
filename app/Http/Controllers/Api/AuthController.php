<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Resources\UserResource;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return ApiResponse::error('Email atau password salah.', 401);
        }

        if (! $user->is_active) {
            return ApiResponse::error('Akun Anda dinonaktifkan.', 403);
        }

        $token = $user->createToken('mobile')->plainTextToken;

        // Kompatibel dengan FE: shape lama { user, token } di top-level
        return response()->json([
            'success' => true,
            'message' => 'Login berhasil.',
            'user' => new UserResource($user),
            'token' => $token,
            'token_type' => 'Bearer',
        ], 200);
    }

    public function logout(Request $request)
    {
        $request->user()?->currentAccessToken()?->delete();

        return ApiResponse::success(null, 'Logout berhasil.');
    }

    public function me(Request $request)
    {
        return ApiResponse::success(new UserResource($request->user()));
    }

    /**
     * Delete the authenticated user's account.
     *
     * Per Google Play account-deletion policy (July 2024), this endpoint must
     * remove PII and effectively deactivate the account. We anonymize in-place
     * so foreign keys (orders.kasir_id, cash_sessions.user_id) stay intact for
     * audit history, then soft-delete the row. Tokens are revoked so the app
     * is signed out immediately.
     */
    public function deleteAccount(Request $request)
    {
        $request->validate([
            'confirmation' => 'required|string|in:HAPUS AKUN',
        ]);

        $user = $request->user();
        if (! $user) {
            return ApiResponse::error('Tidak terautentikasi.', 401);
        }

        DB::transaction(function () use ($user) {
            $user->tokens()->delete();

            $user->forceFill([
                'name' => '[akun dihapus]',
                'email' => 'deleted-'.$user->id.'@deleted.local',
                'phone' => null,
                'avatar' => null,
                'is_active' => false,
            ])->saveQuietly();

            $user->delete();
        });

        return ApiResponse::success(null, 'Akun berhasil dihapus.');
    }
}
