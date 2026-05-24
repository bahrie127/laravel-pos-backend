<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Requests\UserStoreRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', User::class);

        $users = User::query()
            ->when($request->filled('q'), function ($q) use ($request) {
                $q->where(function ($qq) use ($request) {
                    $qq->where('name', 'like', '%' . $request->q . '%')
                       ->orWhere('email', 'like', '%' . $request->q . '%');
                });
            })
            ->when($request->filled('role'), fn ($q) => $q->where('roles', $request->role))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $roleOptions = UserRole::options();

        return view('pages.users.index', compact('users', 'roleOptions'));
    }

    public function create()
    {
        $this->authorize('create', User::class);

        return view('pages.users.create', ['roleOptions' => UserRole::options()]);
    }

    public function store(UserStoreRequest $request)
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        if ($request->hasFile('avatar')) {
            $filename = time() . '_' . $request->avatar->getClientOriginalName();
            $request->avatar->storeAs('public/avatars', $filename);
            $data['avatar'] = $filename;
        }

        User::create($data);

        return redirect()->route('user.index')->with('success', 'Pengguna berhasil ditambahkan.');
    }

    public function edit(User $user)
    {
        $this->authorize('update', $user);

        return view('pages.users.edit', ['user' => $user, 'roleOptions' => UserRole::options()]);
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');

        if (empty($data['password'])) {
            unset($data['password']);
        }

        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                Storage::delete('public/avatars/' . $user->avatar);
            }
            $filename = time() . '_' . $request->avatar->getClientOriginalName();
            $request->avatar->storeAs('public/avatars', $filename);
            $data['avatar'] = $filename;
        }

        $user->update($data);

        return redirect()->route('user.index')->with('success', 'Pengguna berhasil diperbarui.');
    }

    public function destroy(User $user)
    {
        $this->authorize('delete', $user);

        if ($user->avatar) {
            Storage::delete('public/avatars/' . $user->avatar);
        }
        $user->delete();

        return redirect()->route('user.index')->with('success', 'Pengguna berhasil dihapus.');
    }
}
