@extends('layouts.auth')

@section('title', 'Reset Password')

@section('main')
    <div class="login-split">
        <div class="login-form-side">
            <div class="login-form-card">
                <div class="text-center mb-4">
                    <img src="{{ asset('img/logo.svg') }}" alt="{{ config('app.name') }}" class="login-logo-mark">
                    <div class="login-title">Reset Password</div>
                    <div class="login-subtitle">Buat password baru untuk akun Anda.</div>
                </div>

                <form method="POST" action="{{ route('password.update') }}">
                    @csrf
                    <input type="hidden" name="token" value="{{ $request->route('token') }}">

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input id="email" type="email"
                            class="form-control @error('email') is-invalid @enderror"
                            name="email" value="{{ $request->email ?? old('email') }}" required readonly>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="password">Password Baru</label>
                        <input id="password" type="password"
                            class="form-control @error('password') is-invalid @enderror"
                            name="password" required autofocus>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="password_confirmation">Konfirmasi Password</label>
                        <input id="password_confirmation" type="password"
                            class="form-control" name="password_confirmation" required>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg btn-block">
                        Reset Password
                    </button>
                </form>
            </div>
        </div>

        <div class="login-brand-side">
            <div class="text-center" style="max-width: 400px;">
                <i class="fas fa-key" style="font-size:80px;opacity:.8;margin-bottom:24px;"></i>
                <h2 style="font-weight:700;margin-bottom:12px;">Hampir Selesai</h2>
                <p style="opacity:.9;line-height:1.6;">
                    Pilih password yang kuat untuk menjaga keamanan akun Anda.
                </p>
            </div>
        </div>
    </div>
@endsection
