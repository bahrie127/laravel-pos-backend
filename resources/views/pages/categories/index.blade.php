@extends('layouts.app')

@section('title', 'Categories')

@push('style')
    <!-- CSS Libraries -->
    <link rel="stylesheet" href="{{ asset('library/selectric/public/selectric.css') }}">
@endpush

@section('main')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1>Categories</h1>
                <div class="section-header-button">
                    <a href="{{ route('categories.create') }}" class="btn btn-primary">Add New</a>
                </div>
                <div class="section-header-breadcrumb">
                    <div class="breadcrumb-item active"><a href="#">Dashboard</a></div>
                    <div class="breadcrumb-item"><a href="#">Category</a></div>
                    <div class="breadcrumb-item">All Category</div>
                </div>
            </div>
            <div class="section-body">
                <div class="row">
                    <div class="col-12">
                        @include('layouts.alert')
                    </div>
                </div>



                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">

                            <div class="card-body">

                                <div class="float-right">
                                    <form method="GET" action="{{ route('categories.index') }}">
                                        <div class="input-group">
                                            <input type="text" class="form-control" placeholder="Search" name="name">
                                            <div class="input-group-append">
                                                <button class="btn btn-primary"><i class="fas fa-search"></i></button>
                                            </div>
                                        </div>
                                    </form>
                                </div>

                                <div class="clearfix mb-3"></div>

                                @if ($categories->count() > 0)
                                    <div class="table-responsive">
                                        <table class="table-striped table">
                                            <tr>
                                                <th>Nama</th>
                                                <th>Dibuat</th>
                                                <th class="text-center">Aksi</th>
                                            </tr>
                                            @foreach ($categories as $category)
                                                <tr>
                                                    <td class="font-weight-bold">{{ $category->name }}</td>
                                                    <td>{{ formatDate($category->created_at, 'd M Y') }}</td>
                                                    <td>
                                                        <div class="d-flex justify-content-center">
                                                            <a href='{{ route('categories.edit', $category->id) }}'
                                                                class="btn btn-sm btn-info btn-icon">
                                                                <i class="fas fa-edit"></i> Edit
                                                            </a>

                                                            <form action="{{ route('categories.destroy', $category->id) }}"
                                                                method="POST" class="ml-2">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-sm btn-danger btn-icon confirm-delete"
                                                                    data-title="Hapus kategori?"
                                                                    data-text="Kategori '{{ $category->name }}' akan dihapus.">
                                                                    <i class="fas fa-trash"></i> Hapus
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </table>
                                    </div>
                                    <div class="float-right">
                                        {{ $categories->withQueryString()->links() }}
                                    </div>
                                @else
                                    <x-empty-state
                                        icon="tags"
                                        title="Belum ada kategori"
                                        description="Buat kategori untuk mengelompokkan produk."
                                        :action-label="'Tambah Kategori'"
                                        :action-url="route('categories.create')"
                                    />
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    <!-- JS Libraies -->
    <script src="{{ asset('library/selectric/public/jquery.selectric.min.js') }}"></script>

    <!-- Page Specific JS File -->
    <script src="{{ asset('js/page/features-posts.js') }}"></script>
@endpush
