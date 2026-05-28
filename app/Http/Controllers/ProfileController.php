<?php

namespace App\Http\Controllers;

use App\Models\CashSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function show()
    {
        return view('pages.profile.index', ['user' => auth()->user()]);
    }

    public function update(Request $request)
    {
        $user = auth()->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:20'],
            'avatar' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:1024'],
        ]);

        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                Storage::delete('public/avatars/' . $user->avatar);
            }
            $filename = time() . '_' . $request->avatar->getClientOriginalName();
            $request->avatar->storeAs('public/avatars', $filename);
            $data['avatar'] = $filename;
        }

        $user->update($data);

        return redirect()->route('profile.show')->with('success', 'Profil berhasil diperbarui.');
    }

    public function updatePassword(Request $request)
    {
        $user = auth()->user();

        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'current_password.current_password' => 'Password lama salah.',
        ]);

        $user->update(['password' => Hash::make($data['password'])]);

        return redirect()->route('profile.show')->with('success', 'Password berhasil diubah.');
    }

    /**
     * Self-delete current user's account. Mirrors API DELETE /api/account:
     * anonymize PII in-place so order/cash-session FKs survive for audit,
     * revoke Sanctum tokens, soft-delete the row, then logout web session.
     */
    public function destroy(Request $request)
    {
        $request->validate([
            'confirmation' => ['required', 'string', 'in:HAPUS AKUN'],
            'password' => ['required', 'current_password'],
        ], [
            'confirmation.in' => 'Ketik "HAPUS AKUN" persis untuk konfirmasi.',
            'password.current_password' => 'Password tidak cocok.',
        ]);

        $user = $request->user();

        // Cegah owner terakhir kehapus — kalau ini owner satu-satunya, tolak.
        if ($user->isOwner()) {
            $otherOwners = \App\Models\User::where('roles', \App\Enums\UserRole::Owner->value)
                ->where('id', '!=', $user->id)
                ->count();
            if ($otherOwners === 0) {
                return back()->withErrors([
                    'confirmation' => 'Tidak bisa menghapus akun owner terakhir. Tunjuk owner lain dulu.',
                ]);
            }
        }

        // Cegah hapus saat masih ada shift aktif (data integrity).
        if (CashSession::currentFor($user->id)) {
            return back()->withErrors([
                'confirmation' => 'Tutup shift aktif Anda dulu sebelum menghapus akun.',
            ]);
        }

        DB::transaction(function () use ($user) {
            $user->tokens()->delete();

            $user->forceFill([
                'name' => '[akun dihapus]',
                'email' => 'deleted-' . $user->id . '@deleted.local',
                'phone' => null,
                'avatar' => null,
                'is_active' => false,
            ])->saveQuietly();

            $user->delete();
        });

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('status', 'Akun Anda berhasil dihapus.');
    }
}
