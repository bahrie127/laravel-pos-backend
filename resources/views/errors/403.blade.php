@extends('layouts.error')
@section('title', 'Akses Ditolak')

@section('main')
    <div class="error-code" style="color:var(--brand-warning);">403</div>
    <div class="error-title">Akses ditolak</div>
    <p class="error-description">
        {{ $exception->getMessage() ?: 'Anda tidak memiliki izin untuk mengakses halaman ini. Hubungi administrator jika Anda merasa ini adalah kesalahan.' }}
    </p>
    <div class="error-actions">
        <a href="{{ url()->previous() === url()->current() ? url('/') : url()->previous() }}" class="btn btn-light">
            <i class="fas fa-arrow-left mr-1"></i> Kembali
        </a>
        <a href="{{ url('/') }}" class="btn btn-primary">
            <i class="fas fa-home mr-1"></i> Ke Beranda
        </a>
    </div>
@endsection
