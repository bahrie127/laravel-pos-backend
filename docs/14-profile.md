# Step 14 — Halaman Profile + Hapus Akun

## Tujuan

User yang login bisa **kelola profile sendiri**: edit info (nama/email/phone/avatar), ganti password, dan hapus akun (dengan safeguard).

## Prasyarat

- Step 13 selesai

## Konteks

Tanpa profile page, user harus minta admin untuk update profile. Tidak skalable. Page profile = self-service.

Hapus akun = compliance requirement (GDPR, Play Store policy). Pakai anonymization + soft delete supaya FK integrity terjaga.

## Prompt untuk AI

````
Project Laravel POS sudah punya CRUD User. Sekarang buat halaman Profile self-service dengan 3 tab.

A. ROUTE

1. `routes/web.php` (dalam group auth):
```php
Route::prefix('profile')->name('profile.')->group(function () {
    Route::get('/', [\App\Http\Controllers\ProfileController::class, 'show'])->name('show');
    Route::put('/', [\App\Http\Controllers\ProfileController::class, 'update'])->name('update');
    Route::put('/password', [\App\Http\Controllers\ProfileController::class, 'updatePassword'])->name('password');
    Route::delete('/', [\App\Http\Controllers\ProfileController::class, 'destroy'])->name('destroy');
});
```

B. CONTROLLER

2. `app/Http/Controllers/ProfileController.php`:
```php
namespace App\Http\Controllers;

use App\Models\User;
use App\Models\CashSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProfileController extends Controller {
    public function show() {
        return view('pages.profile.index', ['user' => auth()->user()]);
    }

    public function update(Request $request) {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required','string','min:2','max:100'],
            'email' => ['required','email', Rule::unique('users','email')->ignore($user->id)],
            'phone' => ['nullable','string','max:30'],
            'avatar' => ['nullable','image','mimes:jpg,jpeg,png,webp','max:1024'],
        ]);
        if ($request->hasFile('avatar')) {
            if ($user->avatar) Storage::disk('public')->delete('avatars/'.$user->avatar);
            $data['avatar'] = basename($request->file('avatar')->store('avatars','public'));
        }
        $user->update($data);
        return back()->with('success', __('Profil berhasil diperbarui.'));
    }

    public function updatePassword(Request $request) {
        $request->validate([
            'current_password' => ['required','current_password'],
            'password' => ['required','string','min:8','confirmed'],
        ]);
        $request->user()->update(['password' => $request->password]);
        return back()->with('success', __('Password berhasil diubah.'));
    }

    public function destroy(Request $request) {
        $request->validate([
            'confirmation' => ['required','in:HAPUS AKUN'],
            'password' => ['required','current_password'],
        ]);
        $user = $request->user();

        // Safeguard 1: cegah owner terakhir delete
        if ($user->isOwner() && User::where('roles','owner')->where('id','!=',$user->id)->count() === 0) {
            return back()->with('error', __('Tidak bisa menghapus owner terakhir.'));
        }

        // Safeguard 2: cegah delete kalau ada shift aktif
        if (CashSession::open()->forUser($user->id)->exists()) {
            return back()->with('error', __('Tutup shift Anda terlebih dahulu sebelum menghapus akun.'));
        }

        DB::transaction(function () use ($user) {
            // Anonymize
            $user->forceFill([
                'name' => '[akun dihapus]',
                'email' => 'deleted-'.$user->id.'@deleted.local',
                'phone' => null,
                'avatar' => null,
                'is_active' => false,
            ])->saveQuietly();

            // Revoke tokens
            $user->tokens()->delete();

            // Soft delete
            $user->delete();
        });

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login')->with('status', __('Akun Anda telah dihapus.'));
    }
}
```

C. VIEW

3. `resources/views/pages/profile/index.blade.php` dengan 3 tab Bootstrap:

```blade
@extends('layouts.app')
@section('title', __('Profil Saya'))
@section('main')
<section class="section">
    <x-page-header title="{{ __('Profil Saya') }}"
        :breadcrumbs="[['label'=>__('Profil')]]"/>

    <div class="card-clean mb-4">
        <div class="d-flex align-items-center">
            <img src="{{ $user->avatar_url }}" class="rounded-circle me-3" style="width:80px;height:80px;object-fit:cover">
            <div>
                <h4 class="mb-0">{{ $user->name }}</h4>
                <div class="text-muted">{{ $user->email }}</div>
                <div class="mt-1"><x-role-badge :role="$user->roles"/></div>
            </div>
        </div>
    </div>

    <ul class="nav nav-tabs mb-3" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-info">{{ __('Informasi') }}</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-pwd">{{ __('Password') }}</button></li>
        <li class="nav-item"><button class="nav-link text-danger" data-bs-toggle="tab" data-bs-target="#tab-del">{{ __('Hapus Akun') }}</button></li>
    </ul>

    <div class="tab-content">
        {{-- INFO --}}
        <div class="tab-pane fade show active" id="tab-info">
            <div class="card-clean">
                <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf @method('PUT')
                    <div class="row">
                        <div class="col-md-3 text-center">
                            <img id="prev" src="{{ $user->avatar_url }}" class="rounded-circle mb-2" style="width:128px;height:128px;object-fit:cover">
                            <input type="file" name="avatar" id="av" accept="image/*" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-9">
                            <x-form-input name="name" label="Nama Lengkap" :value="$user->name" required/>
                            <x-form-input name="email" label="Email" type="email" :value="$user->email" required/>
                            <x-form-input name="phone" label="No. HP" :value="$user->phone"/>
                            <button class="btn btn-primary">{{ __('Simpan Perubahan') }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- PASSWORD --}}
        <div class="tab-pane fade" id="tab-pwd">
            <div class="card-clean">
                <form action="{{ route('profile.password') }}" method="POST">
                    @csrf @method('PUT')
                    <x-form-input name="current_password" label="Password Sekarang" type="password" required/>
                    <x-form-input name="password" label="Password Baru" type="password" required/>
                    <x-form-input name="password_confirmation" label="Konfirmasi Password Baru" type="password" required/>
                    <button class="btn btn-primary">{{ __('Ubah Password') }}</button>
                </form>
            </div>
        </div>

        {{-- DELETE --}}
        <div class="tab-pane fade" id="tab-del">
            <div class="card-clean border border-danger">
                <h5 class="text-danger"><i class="fas fa-exclamation-triangle me-2"></i>{{ __('Hapus Akun Permanen') }}</h5>
                <p class="text-muted">{{ __('Aksi ini akan menghapus akun Anda dan men-anonymize data. Tidak bisa dibatalkan.') }}</p>
                <form action="{{ route('profile.destroy') }}" method="POST">
                    @csrf @method('DELETE')
                    <x-form-input name="confirmation" label="Ketik HAPUS AKUN untuk konfirmasi" required/>
                    <x-form-input name="password" label="Password Anda" type="password" required/>
                    <button class="btn btn-danger" onclick="return confirm('Yakin hapus akun?')">{{ __('Hapus Akun Saya') }}</button>
                </form>
            </div>
        </div>
    </div>
</section>
@push('scripts')
<script>
document.getElementById('av')?.addEventListener('change', e => {
    const f = e.target.files[0]; if(!f) return;
    const r = new FileReader();
    r.onload = ev => document.getElementById('prev').src = ev.target.result;
    r.readAsDataURL(f);
});
</script>
@endpush
@endsection
```

D. SIDEBAR + HEADER

4. Tambah link "Profile" di user dropdown header (`components/header.blade.php`):
   ```blade
   <a href="{{ route('profile.show') }}" class="dropdown-item"><i class="fas fa-user me-2"></i>{{ __('Profil Saya') }}</a>
   ```

E. API ENDPOINT (untuk Flutter delete account)

5. `app/Http/Controllers/Api/AuthController.php` tambah method `deleteAccount`:
   ```php
   public function deleteAccount(Request $request) {
       $request->validate([
           'confirmation' => ['required','in:HAPUS AKUN'],
           'password' => ['required','current_password'],
       ]);
       $user = $request->user();
       if ($user->isOwner() && User::where('roles','owner')->where('id','!=',$user->id)->count() === 0) {
           return ApiResponse::error('Tidak bisa menghapus owner terakhir.', 422);
       }
       DB::transaction(function () use ($user) {
           $user->forceFill([
               'name' => '[akun dihapus]',
               'email' => 'deleted-'.$user->id.'@deleted.local',
               'avatar' => null,
               'phone' => null,
               'is_active' => false,
           ])->saveQuietly();
           $user->tokens()->delete();
           $user->delete();
       });
       return ApiResponse::success(null, 'Akun dihapus.');
   }
   ```

6. Route `DELETE /api/account` di `routes/api.php`:
   ```php
   Route::delete('account', [AuthController::class, 'deleteAccount']);
   ```

Tampilkan output `php artisan route:list | grep profile` setelah selesai.
````

## Hasil yang Diharapkan

```
app/Http/Controllers/ProfileController.php
app/Http/Controllers/Api/AuthController.php  ← +deleteAccount
resources/views/pages/profile/index.blade.php
resources/views/components/header.blade.php  ← link Profile
routes/web.php                                ← group profile.*
routes/api.php                                ← account delete
```

## Cara Test

```bash
# 1. Klik avatar di header → dropdown → klik "Profil Saya"
# 2. /profile → tampil 3 tab: Informasi, Password, Hapus Akun

# Tab Informasi:
# 3. Upload avatar baru → submit → toast "Profil berhasil diperbarui"
# 4. Ubah nama → cek sidebar update real-time

# Tab Password:
# 5. Submit dengan current_password salah → error validation
# 6. Submit benar → toast "Password berhasil diubah" → logout → login dengan password baru

# Tab Hapus Akun:
# 7. Buka shift kasir dulu → coba hapus → error "Tutup shift dulu"
# 8. Tutup shift → coba hapus dengan confirmation "salah" → error
# 9. confirmation = "HAPUS AKUN" + password benar → akun dihapus → redirect login
#    → cek DB: name='[akun dihapus]', email='deleted-X@deleted.local', deleted_at not null

# API test:
curl -X DELETE http://localhost:8000/api/account \
  -H "Authorization: Bearer TOKEN" \
  -H "Accept: application/json" \
  -d '{"confirmation":"HAPUS AKUN","password":"12345678"}'
```

## Penjelasan untuk Murid

Talking points:

1. **"`current_password` rule?"** — Built-in Laravel rule. Bandingkan input dengan password user logged-in. Hemat manual `Hash::check`.

2. **"`Rule::unique(...)->ignore($id)` vs string format?"** — Sama. Rule class lebih readable + IDE autocomplete.

3. **"Anonymize sebelum soft delete — kenapa?"** — Email unique constraint masih aktif. Kalau di-soft-delete tanpa ubah email, user tidak bisa register ulang dengan email yang sama (karena `where('email', ...)` tetap match record di-soft-delete).

4. **"`Auth::logout()` + `regenerateToken()`?"** — Habiskan session + CSRF token baru. Hindari session fixation.

5. **"Safeguard owner terakhir?"** — Tanpa owner, tidak ada yang bisa manage user. Database orphan tapi UI buntu.

6. **"Konfirmasi tipe phrase ('HAPUS AKUN') — kenapa?"** — Ini destructive action. Native confirm() bisa di-skip. Phrase wajib ketik = double confirmation.

Pertanyaan reflektif:
- "Bagaimana implement 'restore account dalam 30 hari' (undo delete)?" (→ tidak `forceFill` email; tambah link reactivation email; cron clean record > 30 hari)
- "Bagaimana sync delete account dari Flutter ke Web Session aktif?" (→ revoke tokens cukup; web session terpisah dari API token, kecuali pakai Sanctum SPA mode)

---

**Next: [15-orders.md](15-orders.md)**
