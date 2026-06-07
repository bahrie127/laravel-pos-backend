# Step 13 — CRUD Users + Login Audit

## Tujuan

CRUD Pengguna lengkap: filter by role, avatar upload, role selector, status toggle, last login tracking, password optional di edit.

## Prasyarat

- Step 12 selesai

## Konteks

Mirip Products, tapi:
- Field kompleks: password optional saat edit, avatar upload.
- Field role pakai enum `UserRole`.
- Event listener track `last_login_at` + `last_login_ip`.

## Prompt untuk AI

````
Project Laravel POS sudah punya CRUD Products. Sekarang buat CRUD Users + login audit.

A. ROUTE

1. `routes/web.php`:
```php
Route::resource('user', \App\Http\Controllers\UserController::class);
```

B. CONTROLLER

2. `app/Http/Controllers/UserController.php`:
```php
namespace App\Http\Controllers;

use App\Models\User;
use App\Enums\UserRole;
use App\Http\Requests\UserStoreRequest;
use App\Http\Requests\UpdateUserRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller {
    public function __construct() {
        $this->authorizeResource(User::class, 'user');
    }

    public function index(Request $request) {
        $query = User::query();
        if ($request->filled('q')) {
            $query->where(fn($q) => $q->where('name','like','%'.$request->q.'%')->orWhere('email','like','%'.$request->q.'%'));
        }
        if ($request->filled('role')) $query->where('roles', $request->role);
        $users = $query->latest()->paginate(15)->withQueryString();
        return view('pages.users.index', compact('users'));
    }

    public function create() {
        return view('pages.users.create', ['user' => new User(['is_active' => true, 'roles' => 'kasir'])]);
    }

    public function store(UserStoreRequest $request) {
        $data = $request->validated();
        if ($request->hasFile('avatar')) {
            $data['avatar'] = basename($request->file('avatar')->store('avatars','public'));
        }
        unset($data['password_confirmation']);
        User::create($data);
        return redirect()->route('user.index')->with('success', __('messages.created', ['resource' => 'Pengguna']));
    }

    public function edit(User $user) {
        return view('pages.users.edit', compact('user'));
    }

    public function update(UpdateUserRequest $request, User $user) {
        $data = $request->validated();
        if ($request->hasFile('avatar')) {
            if ($user->avatar) Storage::disk('public')->delete('avatars/'.$user->avatar);
            $data['avatar'] = basename($request->file('avatar')->store('avatars','public'));
        }
        if (empty($data['password'])) unset($data['password']);
        unset($data['password_confirmation']);
        $user->update($data);
        return redirect()->route('user.index')->with('success', __('messages.updated', ['resource' => 'Pengguna']));
    }

    public function destroy(User $user) {
        if ($user->id === auth()->id()) {
            return back()->with('error', __('Tidak bisa menghapus akun sendiri.'));
        }
        if ($user->avatar) Storage::disk('public')->delete('avatars/'.$user->avatar);
        $user->delete(); // soft delete
        return back()->with('success', __('messages.deleted', ['resource' => 'Pengguna']));
    }
}
```

C. FORM REQUEST

3. `UserStoreRequest.php`:
```php
public function authorize(): bool { return $this->user()->can('create', User::class); }
public function rules(): array {
    return [
        'name' => ['required','string','min:2','max:100'],
        'email' => ['required','email','unique:users,email'],
        'phone' => ['nullable','string','max:30'],
        'password' => ['required','string','min:8','confirmed'],
        'roles' => ['required','in:owner,admin,kasir'],
        'avatar' => ['nullable','image','mimes:jpg,jpeg,png,webp','max:1024'],
        'is_active' => ['nullable','boolean'],
    ];
}
protected function prepareForValidation(): void {
    $this->merge(['is_active' => $this->boolean('is_active', true)]);
}
```

4. `UpdateUserRequest.php`:
```php
public function authorize(): bool { return $this->user()->can('update', $this->route('user')); }
public function rules(): array {
    $id = $this->route('user')->id;
    return [
        'name' => ['required','string','min:2','max:100'],
        'email' => ['required','email','unique:users,email,'.$id],
        'phone' => ['nullable','string','max:30'],
        'password' => ['nullable','string','min:8','confirmed'],
        'roles' => ['required','in:owner,admin,kasir'],
        'avatar' => ['nullable','image','mimes:jpg,jpeg,png,webp','max:1024'],
        'is_active' => ['nullable','boolean'],
    ];
}
protected function prepareForValidation(): void {
    $this->merge(['is_active' => $this->boolean('is_active', true)]);
}
```

D. EVENT LISTENER (login audit)

5. `app/Listeners/UpdateLastLoginAt.php`:
```php
namespace App\Listeners;

use Illuminate\Auth\Events\Login;

class UpdateLastLoginAt {
    public function handle(Login $event): void {
        $event->user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => request()->ip(),
        ])->saveQuietly();
    }
}
```

6. Register di `app/Providers/EventServiceProvider.php`:
```php
protected $listen = [
    \Illuminate\Auth\Events\Login::class => [
        \App\Listeners\UpdateLastLoginAt::class,
    ],
];
```

E. VIEWS

7. `pages/users/index.blade.php` — table dengan avatar, role badge, status, last login.

```blade
@extends('layouts.app')
@section('title', __('Pengguna'))
@section('main')
<section class="section">
    <x-page-header title="{{ __('Pengguna') }}" subtitle="{{ __(':total pengguna', ['total' => $users->total()]) }}">
        <x-slot:actions>
            @can('create', App\Models\User::class)
                <a href="{{ route('user.create') }}" class="btn btn-primary"><i class="fas fa-user-plus me-2"></i>{{ __('Tambah Pengguna') }}</a>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <div class="card-clean mb-3">
        <form method="GET" class="row g-2">
            <div class="col-md-6"><input type="text" name="q" value="{{ request('q') }}" placeholder="{{ __('Cari nama / email...') }}" class="form-control"></div>
            <div class="col-md-3">
                <select name="role" class="form-control">
                    <option value="">{{ __('Semua Role') }}</option>
                    @foreach(\App\Enums\UserRole::options() as $v => $l)
                        <option value="{{ $v }}" @selected(request('role')===$v)>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3"><button class="btn btn-primary w-100">{{ __('Filter') }}</button></div>
        </form>
    </div>

    <div class="card-clean">
        @if($users->isEmpty())
            <x-empty-state icon="users" title="{{ __('Belum ada pengguna') }}"/>
        @else
            <table class="table align-middle">
                <thead class="text-uppercase small text-muted">
                    <tr><th>{{ __('Pengguna') }}</th><th>{{ __('Kontak') }}</th><th>{{ __('Role') }}</th><th>{{ __('Status') }}</th><th>{{ __('Login Terakhir') }}</th><th></th></tr>
                </thead>
                <tbody>
                    @foreach($users as $u)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <img src="{{ $u->avatar_url }}" class="rounded-circle me-2" style="width:40px;height:40px;object-fit:cover">
                                <div><div class="fw-semibold">{{ $u->name }}</div><div class="small text-muted">{{ __('Bergabung') }} {{ formatDate($u->created_at, 'd M Y') }}</div></div>
                            </div>
                        </td>
                        <td>{{ $u->email }}<div class="small text-muted">{{ $u->phone ?? '—' }}</div></td>
                        <td><x-role-badge :role="$u->roles"/></td>
                        <td>@if($u->is_active)<span class="badge bg-success">{{ __('Aktif') }}</span>@else<span class="badge bg-secondary">{{ __('Nonaktif') }}</span>@endif</td>
                        <td class="small text-muted">{{ $u->last_login_at ? $u->last_login_at->diffForHumans() : __('Belum pernah') }}</td>
                        <td class="text-end">
                            @can('update', $u)<a href="{{ route('user.edit', $u) }}" class="btn btn-sm btn-light"><i class="fas fa-pencil-alt"></i></a>@endcan
                            @can('delete', $u)<button class="btn btn-sm btn-light text-danger confirm-delete" data-action="{{ route('user.destroy', $u) }}"><i class="fas fa-trash"></i></button>@endcan
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="mt-3">{{ $users->links() }}</div>
        @endif
    </div>
</section>
@endsection
```

8. `pages/users/_form.blade.php`:
```blade
@php $isEdit = $user->exists; @endphp
<form action="{{ $isEdit ? route('user.update', $user) : route('user.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    @if($isEdit) @method('PUT') @endif
    <div class="row">
        <div class="col-lg-8">
            <div class="card-clean">
                <h5 class="mb-3">{{ __('Informasi') }}</h5>
                <x-form-input name="name" label="Nama Lengkap" :value="$user->name" required/>
                <x-form-input name="email" label="Email" type="email" :value="$user->email" required/>
                <x-form-input name="phone" label="No. HP" :value="$user->phone"/>
                <h6 class="mt-4 mb-3">{{ __('Password') }} @if($isEdit)<small class="text-muted">({{ __('Kosongkan jika tidak diubah') }})</small>@endif</h6>
                <x-form-input name="password" label="Password" type="password" :required="!$isEdit"/>
                <x-form-input name="password_confirmation" label="Konfirmasi Password" type="password" :required="!$isEdit"/>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card-clean mb-3">
                <h5 class="mb-3">{{ __('Foto') }}</h5>
                <img id="avPrev" src="{{ $user->avatar_url }}" class="rounded-circle mb-2" style="width:120px;height:120px;object-fit:cover">
                <input type="file" name="avatar" id="avInput" accept="image/*" class="form-control">
                @error('avatar')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            <div class="card-clean">
                <h5 class="mb-3">{{ __('Role & Status') }}</h5>
                <x-form-select name="roles" label="Role" :options="\App\Enums\UserRole::options()" :value="$user->roles" required/>
                <x-form-toggle name="is_active" label="Akun Aktif" :checked="$user->is_active ?? true"/>
                <div class="d-grid gap-2 mt-3">
                    <button class="btn btn-primary">{{ __('Simpan') }}</button>
                    <a href="{{ route('user.index') }}" class="btn btn-light">{{ __('Batal') }}</a>
                </div>
            </div>
        </div>
    </div>
</form>
@push('scripts')
<script>
document.getElementById('avInput')?.addEventListener('change', e => {
    const f = e.target.files[0]; if(!f) return;
    const r = new FileReader();
    r.onload = ev => document.getElementById('avPrev').src = ev.target.result;
    r.readAsDataURL(f);
});
</script>
@endpush
```

9. `create.blade.php` + `edit.blade.php` — extends layout, include `_form`.

Tampilkan struktur folder `pages/users/` setelah selesai.
````

## Hasil yang Diharapkan

```
app/
├── Http/Controllers/UserController.php
├── Http/Requests/{UserStoreRequest, UpdateUserRequest}.php
├── Listeners/UpdateLastLoginAt.php
└── Providers/EventServiceProvider.php  ← register listener
resources/views/pages/users/{index, create, edit, _form}.blade.php
```

## Cara Test

```bash
# 1. /user → 8 user dari seeder, avatar bulat, role badge berwarna
# 2. Filter role "Kasir" → 5 hasil
# 3. Tambah user baru: nama "Manager Toko", email "manager@fic11.com", password "rahasia12", role admin
# 4. Login dengan user baru → /home → buka /user, cek "Login Terakhir" untuk user tsb harus update
# 5. Edit user, kosongkan password → harus tidak ubah password
# 6. Coba delete diri sendiri → error toast "Tidak bisa menghapus akun sendiri"
# 7. Soft delete: cek DB `users.deleted_at` ter-set, query default tidak munculkan
```

## Penjelasan untuk Murid

Talking points:

1. **"Password optional di edit — kenapa pattern `if empty unset`?"** — Kalau form submit kosong password, kita tidak ubah. Beda dengan create yang wajib.

2. **"`saveQuietly()` di listener?"** — Update tanpa trigger event lain (e.g., `updated` event). Hindari infinite loop.

3. **"`forceFill` + `saveQuietly` vs `update`?"** — `forceFill` bypass `$fillable` guard (untuk kolom yang sengaja exclude). `update` lebih sederhana kalau kolom di-fillable.

4. **"Soft delete user — kenapa?"** — User yang sudah punya order historis tidak boleh benar-benar hilang. Soft delete preserves FK integrity.

5. **"`diffForHumans()`?"** — Format "2 jam yang lalu", "3 hari yang lalu". Locale-aware (pakai `id` setelah Carbon::setLocale).

6. **"Listener vs Observer?"** — Listener = react ke Event. Observer = react ke Model event (creating, deleted, dll). UpdateLastLoginAt react ke Auth event, jadi Listener.

Pertanyaan reflektif:
- "Bagaimana support 2-factor authentication untuk admin?" (→ Fortify sudah ready, enable di config + tambah view 2FA)
- "Kalau owner mau force-logout kasir tertentu, gimana?" (→ revoke session via `$user->tokens()->delete()` untuk API + manipulate `sessions` table untuk web)

---

**Next: [14-profile.md](14-profile.md)**
