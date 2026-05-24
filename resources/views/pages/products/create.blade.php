@extends('layouts.app')

@section('title', 'Tambah Produk')

@section('main')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1>Tambah Produk</h1>
                <div class="section-header-breadcrumb">
                    <div class="breadcrumb-item active"><a href="{{ route('home') }}">Dashboard</a></div>
                    <div class="breadcrumb-item"><a href="{{ route('product.index') }}">Produk</a></div>
                    <div class="breadcrumb-item">Tambah</div>
                </div>
            </div>

            <div class="section-body">
                @include('layouts.alert')

                <form action="{{ route('product.store') }}" method="POST" enctype="multipart/form-data" id="product-form">
                    @csrf
                    <div class="row">
                        <div class="col-lg-8 col-12">
                            <div class="card-clean mb-3">
                                <h4 class="mb-3">Informasi Produk</h4>

                                <div class="form-group">
                                    <label>Nama Produk <span class="text-danger">*</span></label>
                                    <input type="text" name="name" value="{{ old('name') }}"
                                        class="form-control @error('name') is-invalid @enderror" autofocus required>
                                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>

                                <div class="form-group">
                                    <label>Deskripsi</label>
                                    <textarea name="description" rows="3" maxlength="1000"
                                        class="form-control @error('description') is-invalid @enderror">{{ old('description') }}</textarea>
                                    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>

                                <div class="form-row">
                                    <div class="col-md-6 form-group">
                                        <label>Harga <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">Rp</span>
                                            </div>
                                            <input type="text" id="price-display" value="{{ old('price') ? number_format(old('price'), 0, ',', '.') : '' }}"
                                                class="form-control" placeholder="0" inputmode="numeric">
                                            <input type="hidden" name="price" id="price-raw" value="{{ old('price') }}">
                                        </div>
                                        @error('price')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                    </div>

                                    <div class="col-md-6 form-group">
                                        <label>Stok <span class="text-danger">*</span></label>
                                        <input type="number" name="stock" value="{{ old('stock', 0) }}" min="0"
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
                                            <option value="{{ $c->id }}" @selected(old('category_id') == $c->id)>{{ $c->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4 col-12">
                            <div class="card-clean mb-3">
                                <h4 class="mb-3">Foto Produk</h4>
                                <div id="image-preview-wrapper" class="mb-3 text-center" style="display:none;">
                                    <img id="image-preview" src="" style="max-width:100%;max-height:200px;border-radius:8px;">
                                </div>
                                <input type="file" name="image" accept="image/*"
                                    class="form-control-file @error('image') is-invalid @enderror" id="image-input">
                                <small class="text-muted">PNG/JPG/WebP, maks 2MB.</small>
                                @error('image')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            </div>

                            <div class="card-clean mb-3">
                                <h4 class="mb-3">Status</h4>
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" name="is_best_seller" value="1" class="custom-control-input"
                                        id="best-seller-toggle" @checked(old('is_best_seller'))>
                                    <label class="custom-control-label" for="best-seller-toggle">
                                        <i class="fas fa-star text-warning"></i> Tandai sebagai Best Seller
                                    </label>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-save mr-1"></i> Simpan
                            </button>
                            <button type="submit" name="save_and_new" value="1" class="btn btn-outline-primary btn-block">
                                <i class="fas fa-plus mr-1"></i> Simpan & Tambah Lagi
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
        // Image preview
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
                } else {
                    wrapper.style.display = 'none';
                }
            });
        })();

        // Currency mask for price input
        (function () {
            const display = document.getElementById('price-display');
            const raw = document.getElementById('price-raw');
            if (!display) return;

            function format(num) {
                return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            }

            display.addEventListener('input', e => {
                const cleaned = e.target.value.replace(/\D/g, '');
                e.target.value = cleaned ? format(cleaned) : '';
                raw.value = cleaned || '';
            });
        })();
    </script>
@endpush
