<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Resources\UserResource;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use Illuminate\Http\Request;
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
}
