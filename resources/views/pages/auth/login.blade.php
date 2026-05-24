@extends('layouts.auth')

@section('title', 'Masuk')

@section('main')
    <div class="login-split">
        {{-- Left: Form --}}
        <div class="login-form-side">
            <div class="login-form-card">
                <div class="text-center mb-4">
                    <img src="{{ asset('img/logo.svg') }}" alt="{{ config('app.name') }}" class="login-logo-mark">
                    <div class="login-title">{{ config('app.name') }}</div>
                    <div class="login-subtitle">Masuk untuk melanjutkan ke panel admin.</div>
                </div>

                @if (session('status'))
                    <div class="alert alert-success">{{ session('status') }}</div>
                @endif

                @if ($errors->any() && ! $errors->has('email') && ! $errors->has('password'))
                    <div class="alert alert-danger">{{ $errors->first() }}</div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="needs-validation" novalidate>
                    @csrf

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input id="email" type="email"
                            class="form-control @error('email') is-invalid @enderror"
                            name="email" value="{{ old('email') }}" tabindex="1" autofocus required>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <div class="d-flex justify-content-between align-items-center">
                            <label for="password" class="mb-0">Password</label>
                            <a href="{{ route('password.request') }}" class="text-primary" style="font-size:13px;">
                                Lupa password?
                            </a>
                        </div>
                        <div class="password-toggle-wrap">
                            <input id="password" type="password"
                                class="form-control @error('password') is-invalid @enderror"
                                name="password" tabindex="2" required>
                            <button type="button" class="password-toggle-btn"
                                data-password-toggle data-target="password" aria-label="Toggle password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        @error('password')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" name="remember" class="custom-control-input" id="remember-me" tabindex="3">
                            <label class="custom-control-label" for="remember-me">Ingat saya</label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg btn-block" tabindex="4">
                        Masuk
                    </button>
                </form>

                <div class="text-center text-muted mt-4" style="font-size:12px;">
                    &copy; {{ date('Y') }} {{ config('app.name') }}
                </div>
            </div>
        </div>

        {{-- Right: brand panel --}}
        <div class="login-brand-side">
            <div class="text-center" style="max-width: 400px;">
                <img src="{{ asset('img/logo.svg') }}" alt="" style="width:80px;height:80px;margin-bottom:24px;background:rgba(255,255,255,.15);padding:12px;border-radius:20px;">
                <h2 style="font-weight:700;margin-bottom:12px;">Kelola Bisnis Anda dengan Mudah</h2>
                <p style="opacity:.9;line-height:1.6;">
                    Dashboard, produk, transaksi, dan laporan POS — semua dalam satu tempat.
                </p>
            </div>
        </div>
    </div>
@endsection
