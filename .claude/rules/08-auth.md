# Auth Pages Enhancement

## Current State

`resources/views/pages/auth/login.blade.php`:
- Card sederhana di tengah dengan field email & password
- Tidak ada logo / branding
- Tidak ada "Remember me" checkbox
- Tidak ada link "Lupa password?" (Fortify endpoint sudah tersedia tapi tidak ada UI-nya)
- Tidak ada link "Register" (commented out)
- Tidak ada social login (jangan tambah kecuali butuh)
- Pakai layout `layouts/auth.blade.php` (Stisla default — kemungkinan minimalist)

Fortify sudah expose:
- `/login`, `/logout`, `/register` (terdaftar di route:list)
- `/forgot-password`, `/reset-password`
- `/two-factor-challenge`

Tapi **views untuk forgot-password & reset-password tidak ada** — Fortify akan 404.

## Target Enhancement

### Login page (target)

Layout: split screen. Kiri: form, kanan: brand/illustration.

```blade
@extends('layouts.auth')
@section('title', __('Masuk'))

@section('main')
<div class="min-h-screen flex">
    {{-- Left: Form --}}
    <div class="flex-1 flex items-center justify-center p-8 bg-white">
        <div class="w-full max-w-md">
            {{-- Logo --}}
            <div class="text-center mb-8">
                <img src="{{ asset('img/logo.svg') }}" class="h-12 mx-auto mb-4" alt="">
                <h1 class="text-2xl font-bold">{{ config('app.name') }}</h1>
                <p class="text-gray-500 mt-1">{{ __('Masuk untuk melanjutkan ke panel admin') }}</p>
            </div>
            
            @if (session('status'))
                <div class="bg-success-50 text-success-700 p-3 rounded-lg mb-4 text-sm">
                    {{ session('status') }}
                </div>
            @endif
            
            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf
                
                <div>
                    <label class="form-label">{{ __('Email') }}</label>
                    <input type="email" name="email" value="{{ old('email') }}"
                           class="form-input @error('email') is-invalid @enderror"
                           autofocus required>
                    @error('email')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>
                
                <div>
                    <div class="flex justify-between">
                        <label class="form-label">{{ __('Password') }}</label>
                        <a href="{{ route('password.request') }}" class="text-sm text-primary-600 hover:underline">
                            {{ __('Lupa password?') }}
                        </a>
                    </div>
                    <div class="relative">
                        <input type="password" name="password" id="password"
                               class="form-input pr-10 @error('password') is-invalid @enderror"
                               required>
                        <button type="button" onclick="togglePassword('password')"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400">
                            <x-icon name="eye" class="w-5 h-5"/>
                        </button>
                    </div>
                    @error('password')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>
                
                <label class="flex items-center text-sm">
                    <input type="checkbox" name="remember" class="form-checkbox mr-2">
                    {{ __('Ingat saya') }}
                </label>
                
                <button class="btn btn-primary w-full btn-lg">
                    {{ __('Masuk') }}
                </button>
            </form>
            
            <p class="text-center text-sm text-gray-500 mt-8">
                &copy; {{ date('Y') }} {{ config('app.name') }}
            </p>
        </div>
    </div>
    
    {{-- Right: Brand panel (hidden on mobile) --}}
    <div class="hidden lg:flex flex-1 bg-gradient-to-br from-primary-600 to-primary-800 items-center justify-center p-8 text-white">
        <div class="max-w-md text-center">
            <img src="{{ asset('img/illustration-pos.svg') }}" class="w-full mb-8 opacity-90">
            <h2 class="text-3xl font-bold mb-3">{{ __('Kelola Bisnis Anda dengan Mudah') }}</h2>
            <p class="text-primary-100">
                {{ __('Dashboard, produk, transaksi, dan laporan POS — semua dalam satu tempat.') }}
            </p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function togglePassword(id) {
    const input = document.getElementById(id);
    input.type = input.type === 'password' ? 'text' : 'password';
}
</script>
@endpush
```

### Forgot password page (BARU)

`resources/views/auth/forgot-password.blade.php`:

```blade
@extends('layouts.auth')
@section('title', __('Lupa Password'))

@section('main')
<div class="min-h-screen flex items-center justify-center p-8">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <x-icon name="lock-closed" class="w-12 h-12 mx-auto text-primary-500 mb-4"/>
            <h1 class="text-2xl font-bold">{{ __('Lupa Password?') }}</h1>
            <p class="text-gray-500 mt-2">{{ __('Masukkan email Anda dan kami akan kirim link untuk reset password.') }}</p>
        </div>
        
        @if (session('status'))
            <div class="bg-success-50 text-success-700 p-3 rounded-lg mb-4 text-sm">
                {{ session('status') }}
            </div>
        @endif
        
        <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
            @csrf
            <div>
                <label class="form-label">{{ __('Email') }}</label>
                <input type="email" name="email" value="{{ old('email') }}"
                       class="form-input @error('email') is-invalid @enderror" required autofocus>
                @error('email')<p class="form-error">{{ $message }}</p>@enderror
            </div>
            
            <button class="btn btn-primary w-full btn-lg">{{ __('Kirim Link Reset') }}</button>
            
            <a href="{{ route('login') }}" class="block text-center text-sm text-gray-500 hover:text-primary-600">
                ← {{ __('Kembali ke login') }}
            </a>
        </form>
    </div>
</div>
@endsection
```

### Reset password page (BARU)

`resources/views/auth/reset-password.blade.php`:

```blade
@extends('layouts.auth')
@section('title', __('Reset Password'))

@section('main')
<div class="min-h-screen flex items-center justify-center p-8">
    <div class="w-full max-w-md">
        <h1 class="text-2xl font-bold text-center mb-8">{{ __('Reset Password') }}</h1>
        
        <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="token" value="{{ $request->route('token') }}">
            
            <x-form-input name="email" label="Email" type="email" :value="$request->email" required readonly/>
            <x-form-input name="password" label="Password Baru" type="password" required/>
            <x-form-input name="password_confirmation" label="Konfirmasi Password" type="password" required/>
            
            <button class="btn btn-primary w-full btn-lg">{{ __('Reset Password') }}</button>
        </form>
    </div>
</div>
@endsection
```

### Register page

Saat ini route Fortify `register` aktif tapi UI tidak ada. **Putuskan:**
- (A) Disable register publik (POS biasanya invite-only oleh owner). Hapus dari `FortifyServiceProvider` boot atau set `Features::registration()` di-disable di `config/fortify.php`.
- (B) Aktifkan register dengan UI mirip login form.

**Rekomendasi: (A) — disable** karena POS B2B. User dibuat oleh admin di Users page.

```php
// config/fortify.php
'features' => [
    // Features::registration(),   // <-- disable
    Features::resetPasswords(),
    Features::updateProfileInformation(),
    Features::updatePasswords(),
    // Features::twoFactorAuthentication(),
],
```

### Email template (`mail/report.blade.php` sudah ada)

Cek isi & sesuaikan dengan brand. Untuk reset-password, Laravel/Fortify pakai notification class. Customize via:

```bash
php artisan vendor:publish --tag=laravel-mail
```

Lalu edit `resources/views/vendor/mail/` untuk match brand color & logo.

## Action Items

- [ ] Redesign login page: split screen, logo, password toggle, remember me, link forgot password
- [ ] Buat view `auth/forgot-password.blade.php`
- [ ] Buat view `auth/reset-password.blade.php`
- [ ] Disable register di `config/fortify.php` (atau enable + buat view)
- [ ] Register Fortify views di `FortifyServiceProvider::boot()`:
  ```php
  Fortify::requestPasswordResetLinkView(fn () => view('auth.forgot-password'));
  Fortify::resetPasswordView(fn ($request) => view('auth.reset-password', ['request' => $request]));
  ```
- [ ] Customize email template untuk brand
- [ ] Buat `layouts/auth.blade.php` redesign (kalau perlu)
- [ ] Tambah favicon, meta tags SEO
- [ ] Logo SVG di `public/img/logo.svg`
- [ ] Illustration SVG untuk right panel di `public/img/illustration-pos.svg`
- [ ] 2FA setup (Phase 4, kalau perlu — Fortify sudah support)
