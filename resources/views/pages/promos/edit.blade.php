@extends('layouts.app')

@section('title', 'Edit Promo')

@section('main')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1>Edit Promo</h1>
                <div class="section-header-breadcrumb">
                    <div class="breadcrumb-item active"><a href="{{ route('home') }}">Dashboard</a></div>
                    <div class="breadcrumb-item"><a href="{{ route('promo.index') }}">Promo</a></div>
                    <div class="breadcrumb-item">{{ $promo->name }}</div>
                </div>
            </div>

            <div class="section-body">
                @include('layouts.alert')

                <form action="{{ route('promo.update', $promo->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    @include('pages.promos._form')
                </form>
            </div>
        </section>
    </div>
@endsection
