@extends('layouts.auth')

@section('title', 'Lupa Password')

@section('main')
    <div class="login-split">
        <div class="login-form-side">
            <div class="login-form-card">
                <div class="text-center mb-4">
                    <img src="{{ asset('img/logo.svg') }}" alt="{{ config('app.name') }}" class="login-logo-mark">
                    <div class="login-title">Lupa Password?</div>
                    <div class="login-subtitle">
                        Masukkan email Anda, kami akan kirim link untuk reset password.
                    </div>
                </div>

                @if (session('status'))
                    <div class="alert alert-success">{{ session('status') }}</div>
                @endif

                <form method="POST" action="{{ route('password.email') }}">
                    @csrf
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input id="email" type="email"
                            class="form-control @error('email') is-invalid @enderror"
                            name="email" value="{{ old('email') }}" autofocus required>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg btn-block">
                        Kirim Link Reset
                    </button>

                    <a href="{{ route('login') }}" class="d-block text-center text-muted mt-3" style="font-size:13px;">
                        <i class="fas fa-arrow-left mr-1"></i> Kembali ke login
                    </a>
                </form>
            </div>
        </div>

        <div class="login-brand-side">
            <div class="text-center" style="max-width: 400px;">
                <i class="fas fa-lock" style="font-size:80px;opacity:.8;margin-bottom:24px;"></i>
                <h2 style="font-weight:700;margin-bottom:12px;">Lupa Password Sudah Biasa</h2>
                <p style="opacity:.9;line-height:1.6;">
                    Kami akan kirim instruksi reset password ke email Anda dalam beberapa detik.
                </p>
            </div>
        </div>
    </div>
@endsection
