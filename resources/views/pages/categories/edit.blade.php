@extends('layouts.app')

@section('title', 'Edit Kategori')

@section('main')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1>Edit Kategori</h1>
                <div class="section-header-breadcrumb">
                    <div class="breadcrumb-item active"><a href="{{ route('home') }}">Dashboard</a></div>
                    <div class="breadcrumb-item"><a href="{{ route('categories.index') }}">Kategori</a></div>
                    <div class="breadcrumb-item">{{ $category->name }}</div>
                </div>
            </div>

            <div class="section-body">
                @include('layouts.alert')

                <form action="{{ route('categories.update', $category) }}" method="POST">
                    @csrf
                    @method('PUT')
                    @include('pages.categories._form', ['category' => $category])
                </form>
            </div>
        </section>
    </div>
@endsection
