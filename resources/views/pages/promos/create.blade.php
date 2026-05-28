@extends('layouts.app')

@section('title', 'Tambah Promo')

@section('main')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1>Tambah Promo</h1>
                <div class="section-header-breadcrumb">
                    <div class="breadcrumb-item active"><a href="{{ route('home') }}">Dashboard</a></div>
                    <div class="breadcrumb-item"><a href="{{ route('promo.index') }}">Promo</a></div>
                    <div class="breadcrumb-item">Tambah</div>
                </div>
            </div>

            <div class="section-body">
                @include('layouts.alert')

                <form action="{{ route('promo.store') }}" method="POST">
                    @csrf
                    @include('pages.promos._form')
                </form>
            </div>
        </section>
    </div>
@endsection
