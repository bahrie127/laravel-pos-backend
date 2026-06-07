<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        if (! $request->user()->can('viewAny', User::class)) {
            return ApiResponse::error('Anda tidak memiliki izin melihat daftar pengguna.', 403);
        }

        $users = User::query()
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = $request->q;
                $q->where(function ($w) use ($term) {
                    $w->where('name', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%");
                });
            })
            ->when($request->filled('role'), fn ($q) => $q->where('roles', $request->role))
            ->orderBy('name')
            ->get();

        return ApiResponse::success(
            UserResource::collection($users),
            'List pengguna berhasil dimuat.'
        );
    }

    public function store(Request $request)
    {
        if (! $request->user()->can('create', User::class)) {
            return ApiResponse::error('Anda tidak memiliki izin menambah pengguna.', 403);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'email' => ['required', 'email', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:8'],
            'roles' => ['required', Rule::in(['owner', 'admin', 'kasir'])],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
            'roles' => $data['roles'],
            'is_active' => $data['is_active'] ?? true,
        ]);

        return ApiResponse::success(
            new UserResource($user),
            'Pengguna berhasil dibuat.',
            201
        );
    }

    public function update(Request $request, User $user)
    {
        if (! $request->user()->can('update', $user)) {
            return ApiResponse::error('Anda tidak memiliki izin mengubah pengguna ini.', 403);
        }

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'min:2', 'max:100'],
            'email' => ['sometimes', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['nullable', 'string', 'min:8'],
            'roles' => ['sometimes', Rule::in(['owner', 'admin', 'kasir'])],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return ApiResponse::success(
            new UserResource($user->fresh()),
            'Pengguna diperbarui.'
        );
    }

    public function destroy(Request $request, User $user)
    {
        if (! $request->user()->can('delete', $user)) {
            return ApiResponse::error('Anda tidak memiliki izin menghapus pengguna ini.', 403);
        }

        // Cegah owner terakhir.
        if ($user->isOwner() && User::where('roles', 'owner')->where('id', '!=', $user->id)->count() === 0) {
            return ApiResponse::error('Tidak bisa menghapus owner terakhir.', 422);
        }

        $user->delete(); // soft delete

        return ApiResponse::success(null, 'Pengguna dihapus.');
    }
}
