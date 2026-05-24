# Users Page & Profile Page

## Current State

### `pages/users/index.blade.php`
- Kolom: Name, Email, Created At, Action
- Search by name only
- Tidak menampilkan kolom `roles` & `phone` (padahal sudah ada di schema dari migration `add_roles_phone_at_users`)
- Tidak ada filter by role
- Tidak ada avatar
- Action: Edit, Delete (native confirm)
- Tidak ada empty state

### `pages/users/create.blade.php` & `edit.blade.php`
- Field: name, email, password (kemungkinan)
- Tidak ada: role selector, phone, avatar upload
- Tidak ada validasi unik email yang user-friendly

### Profile page
**Tidak ada sama sekali.** User yang login tidak punya cara untuk ubah profile/password sendiri tanpa lewat admin Users page.

## Target Enhancement

### Schema (sudah ada `roles` & `phone`, tinggal tambah)

```php
Schema::table('users', function (Blueprint $table) {
    $table->string('avatar')->nullable()->after('phone');
    $table->boolean('is_active')->default(true)->after('avatar');
    $table->timestamp('last_login_at')->nullable()->after('is_active');
    $table->string('last_login_ip')->nullable()->after('last_login_at');
});
```

Update `roles` jadi enum/spatie:
- Phase 2: pakai enum value: `owner`, `admin`, `kasir`
- Phase 4: migrasi ke Spatie laravel-permission (multi-role)

### Index page (target)

```blade
@extends('layouts.app')
@section('title', __('Pengguna'))

@section('main')
<div class="space-y-4">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold">{{ __('Pengguna') }}</h1>
            <p class="text-sm text-gray-500">{{ __(':total pengguna terdaftar', ['total' => $users->total()]) }}</p>
        </div>
        @can('create', App\Models\User::class)
            <a href="{{ route('user.create') }}" class="btn btn-primary">
                <x-icon name="plus" class="w-4 h-4 mr-2"/> {{ __('Tambah Pengguna') }}
            </a>
        @endcan
    </div>
    
    {{-- Filter --}}
    <div class="card">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="{{ __('Cari nama / email...') }}" class="form-input md:col-span-2">
            <select name="role" class="form-select">
                <option value="">{{ __('Semua Role') }}</option>
                <option value="owner" @selected(request('role') == 'owner')>{{ __('Pemilik') }}</option>
                <option value="admin" @selected(request('role') == 'admin')>Admin</option>
                <option value="kasir" @selected(request('role') == 'kasir')>{{ __('Kasir') }}</option>
            </select>
            <button class="btn btn-primary">{{ __('Filter') }}</button>
        </form>
    </div>
    
    {{-- Table --}}
    <div class="card overflow-hidden">
        @if ($users->count() > 0)
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                    <tr>
                        <th class="p-3 text-left">{{ __('Pengguna') }}</th>
                        <th class="p-3 text-left">{{ __('Kontak') }}</th>
                        <th class="p-3 text-center">{{ __('Role') }}</th>
                        <th class="p-3 text-center">{{ __('Status') }}</th>
                        <th class="p-3 text-left">{{ __('Login Terakhir') }}</th>
                        <th class="p-3 text-center">{{ __('Aksi') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                        <tr class="border-t hover:bg-gray-50">
                            <td class="p-3">
                                <div class="flex items-center gap-3">
                                    <img src="{{ $user->avatar_url }}" class="w-10 h-10 rounded-full">
                                    <div>
                                        <p class="font-medium">{{ $user->name }}</p>
                                        <p class="text-xs text-gray-500">{{ __('Bergabung') }} {{ $user->created_at->translatedFormat('d M Y') }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="p-3">
                                <p>{{ $user->email }}</p>
                                <p class="text-xs text-gray-500">{{ $user->phone ?? '—' }}</p>
                            </td>
                            <td class="p-3 text-center">
                                <x-role-badge :role="$user->roles"/>
                            </td>
                            <td class="p-3 text-center">
                                @if ($user->is_active)
                                    <span class="badge badge-success">{{ __('Aktif') }}</span>
                                @else
                                    <span class="badge badge-gray">{{ __('Nonaktif') }}</span>
                                @endif
                            </td>
                            <td class="p-3 text-sm text-gray-500">
                                {{ $user->last_login_at?->diffForHumans() ?? __('Belum pernah') }}
                            </td>
                            <td class="p-3">
                                <div class="flex justify-center gap-1">
                                    @can('update', $user)
                                        <a href="{{ route('user.edit', $user) }}" class="btn-icon"><x-icon name="pencil" class="w-4 h-4"/></a>
                                    @endcan
                                    @can('delete', $user)
                                        <button class="btn-icon btn-icon-danger confirm-delete" data-action="{{ route('user.destroy', $user) }}">
                                            <x-icon name="trash" class="w-4 h-4"/>
                                        </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            
            <div class="p-4 border-t">{{ $users->withQueryString()->links() }}</div>
        @else
            <x-empty-state icon="users" title="{{ __('Belum ada pengguna') }}"/>
        @endif
    </div>
</div>
@endsection
```

### Create/Edit form

```blade
<form action="..." method="POST" enctype="multipart/form-data" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    @csrf
    
    <div class="lg:col-span-2 space-y-6">
        <div class="card">
            <h3 class="font-semibold mb-4">{{ __('Informasi Pengguna') }}</h3>
            <x-form-input name="name" label="Nama Lengkap" required/>
            <x-form-input name="email" label="Email" type="email" required/>
            <x-form-input name="phone" label="No. HP" type="tel"/>
            
            <h4 class="font-medium mt-6 mb-3">{{ __('Password') }}</h4>
            <x-form-input name="password" label="Password" type="password" :required="!isset($user)"/>
            <x-form-input name="password_confirmation" label="Konfirmasi Password" type="password" :required="!isset($user)"/>
            @if (isset($user))
                <p class="text-xs text-gray-500">{{ __('Kosongkan jika tidak ingin mengubah password.') }}</p>
            @endif
        </div>
    </div>
    
    <div class="space-y-6">
        <div class="card">
            <h3 class="font-semibold mb-4">{{ __('Foto Profil') }}</h3>
            <x-avatar-upload name="avatar" :current="$user->avatar ?? null"/>
        </div>
        
        <div class="card">
            <h3 class="font-semibold mb-4">{{ __('Role & Status') }}</h3>
            <x-form-select name="roles" label="Role" :options="['owner' => 'Pemilik', 'admin' => 'Admin', 'kasir' => 'Kasir']" required/>
            <x-form-toggle name="is_active" label="Akun Aktif" :checked="$user->is_active ?? true"/>
        </div>
        
        <button class="btn btn-primary w-full">{{ __('Simpan') }}</button>
    </div>
</form>
```

### Profile page (BARU)

Route: `GET /profile`, `PUT /profile`, `PUT /profile/password`.

```blade
@extends('layouts.app')
@section('title', __('Profil Saya'))

@section('main')
<div class="max-w-4xl mx-auto space-y-6">
    {{-- Header --}}
    <div class="card">
        <div class="flex items-center gap-6">
            <img src="{{ auth()->user()->avatar_url }}" class="w-24 h-24 rounded-full">
            <div>
                <h1 class="text-2xl font-bold">{{ auth()->user()->name }}</h1>
                <p class="text-gray-500">{{ auth()->user()->email }}</p>
                <x-role-badge :role="auth()->user()->roles" class="mt-2"/>
            </div>
        </div>
    </div>
    
    {{-- Tabs --}}
    <div x-data="{ tab: 'info' }">
        <div class="border-b flex gap-6">
            <button @click="tab='info'" :class="tab==='info' ? 'border-primary-500 text-primary-600' : ''" class="px-3 py-3 border-b-2 border-transparent">{{ __('Informasi') }}</button>
            <button @click="tab='password'" :class="tab==='password' ? 'border-primary-500 text-primary-600' : ''" class="px-3 py-3 border-b-2 border-transparent">{{ __('Password') }}</button>
            <button @click="tab='sessions'" :class="tab==='sessions' ? 'border-primary-500 text-primary-600' : ''" class="px-3 py-3 border-b-2 border-transparent">{{ __('Sesi Aktif') }}</button>
        </div>
        
        <div x-show="tab==='info'" class="card mt-4">
            <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
                @csrf @method('PUT')
                <x-avatar-upload name="avatar" :current="auth()->user()->avatar"/>
                <x-form-input name="name" label="Nama Lengkap" :value="auth()->user()->name"/>
                <x-form-input name="email" label="Email" type="email" :value="auth()->user()->email"/>
                <x-form-input name="phone" label="No. HP" :value="auth()->user()->phone"/>
                <button class="btn btn-primary">{{ __('Simpan Perubahan') }}</button>
            </form>
        </div>
        
        <div x-show="tab==='password'" class="card mt-4" style="display:none">
            <form method="POST" action="{{ route('profile.password') }}">
                @csrf @method('PUT')
                <x-form-input name="current_password" label="Password Sekarang" type="password" required/>
                <x-form-input name="password" label="Password Baru" type="password" required/>
                <x-form-input name="password_confirmation" label="Konfirmasi Password Baru" type="password" required/>
                <button class="btn btn-primary">{{ __('Ubah Password') }}</button>
            </form>
        </div>
        
        <div x-show="tab==='sessions'" class="card mt-4" style="display:none">
            <h3 class="font-semibold mb-4">{{ __('Sesi Aktif') }}</h3>
            <p class="text-sm text-gray-500">{{ __('Daftar token Sanctum & web session aktif.') }}</p>
            <table class="w-full text-sm mt-4">
                <thead><tr><th>Device</th><th>IP</th><th>Last Used</th><th></th></tr></thead>
                <tbody>
                    @foreach (auth()->user()->tokens as $token)
                        <tr>
                            <td>{{ $token->name }}</td>
                            <td>—</td>
                            <td>{{ $token->last_used_at?->diffForHumans() ?? 'Never' }}</td>
                            <td><button class="btn btn-sm btn-danger">Revoke</button></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
```

### Model: User

```php
// app/Models/User.php
protected $appends = ['avatar_url'];

public function getAvatarUrlAttribute()
{
    return $this->avatar
        ? asset('storage/avatars/' . $this->avatar)
        : 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&background=3B82F6&color=fff';
}

public function isAdmin(): bool { return in_array($this->roles, ['admin', 'owner']); }
public function isKasir(): bool { return $this->roles === 'kasir'; }
public function isOwner(): bool { return $this->roles === 'owner'; }
```

### Policy

```php
// app/Policies/UserPolicy.php
public function update(User $current, User $target): bool
{
    return $current->isOwner() || $current->id === $target->id;
}

public function delete(User $current, User $target): bool
{
    return $current->isOwner() && $current->id !== $target->id;
}
```

### Controller

```php
public function index(Request $request)
{
    $query = User::query();
    
    if ($request->filled('q')) {
        $query->where(fn($q) => $q->where('name', 'like', "%{$request->q}%")->orWhere('email', 'like', "%{$request->q}%"));
    }
    if ($request->filled('role')) {
        $query->where('roles', $request->role);
    }
    
    $users = $query->latest()->paginate(15);
    
    return view('pages.users.index', compact('users'));
}
```

### Login audit hook

Di `EventServiceProvider` atau di `FortifyServiceProvider`:

```php
Event::listen(Login::class, function ($event) {
    $event->user->update([
        'last_login_at' => now(),
        'last_login_ip' => request()->ip(),
    ]);
});
```

## Action Items

- [ ] Migration: avatar, is_active, last_login_at, last_login_ip
- [ ] User model: `avatar_url` accessor, role helper methods (isAdmin, isKasir, isOwner)
- [ ] UserPolicy: hanya owner yang bisa hapus/edit user lain, semua bisa edit dirinya sendiri
- [ ] Users index: avatar, kolom role badge, status, last login, filter role
- [ ] Users create/edit: role selector, phone, avatar upload, toggle active
- [ ] **Profile page baru** (`/profile`) dengan tab Info / Password / Sessions
- [ ] Route group `profile.*`
- [ ] Login event listener untuk update `last_login_at`
- [ ] `<x-role-badge>` component
- [ ] `<x-avatar-upload>` component (drag-drop, preview, crop)
