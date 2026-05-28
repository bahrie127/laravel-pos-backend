@extends('layouts.error')
@section('title', 'Kesalahan Server')

@section('main')
    <div class="error-code" style="color:var(--brand-danger);">500</div>
    <div class="error-title">Terjadi kesalahan di server</div>
    <p class="error-description">
        Sistem mengalami gangguan. Tim teknis sudah diberitahu dan akan segera memperbaikinya.
        Silakan coba lagi beberapa saat lagi.
    </p>
    <div class="error-actions">
        <a href="{{ url('/') }}" class="btn btn-primary">
            <i class="fas fa-home mr-1"></i> Ke Beranda
        </a>
    </div>
@endsection
