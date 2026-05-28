@extends('layouts.error')
@section('title', 'Halaman Tidak Ditemukan')

@section('main')
    <div class="error-code">404</div>
    <div class="error-title">Halaman tidak ditemukan</div>
    <p class="error-description">
        Maaf, halaman yang Anda cari tidak ada atau sudah dipindahkan.
        Periksa kembali URL atau kembali ke beranda.
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
