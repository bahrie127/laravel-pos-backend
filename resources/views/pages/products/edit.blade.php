@extends('layouts.app')

@section('title', 'Edit Produk')

@section('main')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1>Edit Produk</h1>
                <div class="section-header-breadcrumb">
                    <div class="breadcrumb-item active"><a href="{{ route('home') }}">Dashboard</a></div>
                    <div class="breadcrumb-item"><a href="{{ route('product.index') }}">Produk</a></div>
                    <div class="breadcrumb-item">{{ $product->name }}</div>
                </div>
            </div>

            <div class="section-body">
                @include('layouts.alert')

                <form action="{{ route('product.update', $product) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="row">
                        <div class="col-lg-8 col-12">
                            <div class="card-clean mb-3">
                                <h4 class="mb-3">Informasi Produk</h4>

                                <div class="form-group">
                                    <label>Nama Produk <span class="text-danger">*</span></label>
                                    <input type="text" name="name" value="{{ old('name', $product->name) }}"
                                        class="form-control @error('name') is-invalid @enderror" required>
                                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>

                                <div class="form-group">
                                    <label>Deskripsi</label>
                                    <textarea name="description" rows="3" maxlength="1000"
                                        class="form-control @error('description') is-invalid @enderror">{{ old('description', $product->description) }}</textarea>
                                    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>

                                <div class="form-row">
                                    <div class="col-md-6 form-group">
                                        <label>Harga <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">Rp</span>
                                            </div>
                                            <input type="text" id="price-display"
                                                value="{{ number_format(old('price', $product->price), 0, ',', '.') }}"
                                                class="form-control" inputmode="numeric">
                                            <input type="hidden" name="price" id="price-raw" value="{{ old('price', $product->price) }}">
                                        </div>
                                        @error('price')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                    </div>

                                    <div class="col-md-6 form-group">
                                        <label>Stok <span class="text-danger">*</span></label>
                                        <input type="number" name="stock" value="{{ old('stock', $product->stock) }}" min="0"
                                            class="form-control @error('stock') is-invalid @enderror" required>
                                        @error('stock')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Kategori <span class="text-danger">*</span></label>
                                    <select name="category_id"
                                        class="form-control @error('category_id') is-invalid @enderror" required>
                                        <option value="">Pilih kategori</option>
                                        @foreach ($categories as $c)
                                            <option value="{{ $c->id }}" @selected(old('category_id', $product->category_id) == $c->id)>{{ $c->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4 col-12">
                            <div class="card-clean mb-3">
                                <h4 class="mb-3">Foto Produk</h4>
                                <div id="image-preview-wrapper" class="mb-3 text-center" @if (!$product->image) style="display:none;" @endif>
                                    <img id="image-preview" src="{{ $product->image_url ?? '' }}"
                                        style="max-width:100%;max-height:200px;border-radius:8px;">
                                </div>
                                <input type="file" name="image" accept="image/*"
                                    class="form-control-file @error('image') is-invalid @enderror" id="image-input">
                                <small class="text-muted">PNG/JPG/WebP, maks 2MB. Kosongkan untuk pertahankan foto lama.</small>
                                @error('image')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            </div>

                            <div class="card-clean mb-3">
                                <h4 class="mb-3">Status</h4>
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" name="is_best_seller" value="1" class="custom-control-input"
                                        id="best-seller-toggle" @checked(old('is_best_seller', $product->is_best_seller))>
                                    <label class="custom-control-label" for="best-seller-toggle">
                                        <i class="fas fa-star text-warning"></i> Tandai sebagai Best Seller
                                    </label>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-save mr-1"></i> Simpan Perubahan
                            </button>
                            <a href="{{ route('product.index') }}" class="btn btn-light btn-block">Batal</a>
                        </div>
                    </div>
                </form>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const input = document.getElementById('image-input');
            const wrapper = document.getElementById('image-preview-wrapper');
            const img = document.getElementById('image-preview');
            if (!input) return;
            input.addEventListener('change', e => {
                const file = e.target.files[0];
                if (file) {
                    img.src = URL.createObjectURL(file);
                    wrapper.style.display = '';
                }
            });
        })();

        (function () {
            const display = document.getElementById('price-display');
            const raw = document.getElementById('price-raw');
            if (!display) return;
            function format(num) { return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.'); }
            display.addEventListener('input', e => {
                const cleaned = e.target.value.replace(/\D/g, '');
                e.target.value = cleaned ? format(cleaned) : '';
                raw.value = cleaned || '';
            });
        })();
    </script>
@endpush
