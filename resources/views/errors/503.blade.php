@extends('layouts.error')
@section('title', 'Maintenance')

@section('main')
    <div class="error-code" style="color:var(--brand-warning);">503</div>
    <div class="error-title">Sedang dalam pemeliharaan</div>
    <p class="error-description">
        {{ $exception->getMessage() ?: 'Sistem sedang dalam pemeliharaan terjadwal. Silakan kembali beberapa saat lagi.' }}
    </p>
    <div class="error-actions">
        <a href="{{ url('/') }}" class="btn btn-primary">
            <i class="fas fa-redo mr-1"></i> Coba Lagi
        </a>
    </div>
@endsection
