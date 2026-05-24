@extends('layouts.app')

@section('title', 'Kategori')

@section('main')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1>Kategori</h1>
                <div class="section-header-button">
                    @can('create', App\Models\Category::class)
                        <a href="{{ route('categories.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus mr-1"></i> Tambah Kategori
                        </a>
                    @endcan
                </div>
                <div class="section-header-breadcrumb">
                    <div class="breadcrumb-item active"><a href="{{ route('home') }}">Dashboard</a></div>
                    <div class="breadcrumb-item">Kategori</div>
                </div>
            </div>

            <div class="section-body">
                <div class="row">
                    <div class="col-12">
                        @include('layouts.alert')
                    </div>
                </div>

                {{-- Filter bar + view toggle --}}
                <div class="row">
                    <div class="col-12">
                        <div class="card-clean">
                            <form method="GET" action="{{ route('categories.index') }}" class="d-flex" style="gap:8px;flex-wrap:wrap;">
                                <input type="hidden" name="view" value="{{ $view }}">
                                <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Cari kategori..." style="max-width:300px;">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i>
                                </button>
                                <a href="{{ route('categories.index', ['view' => $view]) }}" class="btn btn-outline-secondary">Reset</a>

                                <div class="ml-auto btn-group">
                                    <a href="{{ request()->fullUrlWithQuery(['view' => 'grid']) }}"
                                        class="btn btn-sm {{ $view === 'grid' ? 'btn-primary' : 'btn-outline-secondary' }}">
                                        <i class="fas fa-th"></i> Grid
                                    </a>
                                    <a href="{{ request()->fullUrlWithQuery(['view' => 'list']) }}"
                                        class="btn btn-sm {{ $view === 'list' ? 'btn-primary' : 'btn-outline-secondary' }}">
                                        <i class="fas fa-list"></i> List
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- Content --}}
                @if ($categories->count() > 0)
                    @if ($view === 'grid')
                        {{-- Grid view --}}
                        <div class="row">
                            @foreach ($categories as $cat)
                                <div class="col-6 col-md-4 col-lg-3">
                                    <div class="card-clean position-relative" style="cursor:default;">
                                        @if (! $cat->is_active)
                                            <span class="badge badge-soft-secondary" style="position:absolute;top:10px;left:10px;">Nonaktif</span>
                                        @endif

                                        <div class="d-flex align-items-center mb-3">
                                            <div style="width:48px;height:48px;border-radius:10px;display:inline-flex;align-items:center;justify-content:center;font-size:22px;background:{{ $cat->color }}20;color:{{ $cat->color }};">
                                                <i class="fas fa-{{ $cat->icon ?? 'tag' }}"></i>
                                            </div>
                                        </div>

                                        <div class="font-weight-bold" style="font-size:15px;">{{ $cat->name }}</div>
                                        <div class="text-muted" style="font-size:12px;">
                                            {{ $cat->products_count }} produk
                                        </div>

                                        @if ($cat->description)
                                            <div class="text-muted mt-2" style="font-size:12px;">{{ Str::limit($cat->description, 50) }}</div>
                                        @endif

                                        <div class="d-flex mt-3" style="gap:6px;">
                                            @can('update', $cat)
                                                <a href="{{ route('categories.edit', $cat->id) }}" class="btn btn-sm btn-outline-secondary flex-grow-1">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            @endcan
                                            @can('delete', $cat)
                                                <form action="{{ route('categories.destroy', $cat->id) }}" method="POST" class="m-0 flex-grow-1">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger btn-block confirm-delete"
                                                        data-title="Hapus kategori?"
                                                        data-text="Kategori '{{ $cat->name }}' akan dihapus.">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            @endcan
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="row">
                            <div class="col-12 d-flex justify-content-between align-items-center">
                                <div class="text-muted" style="font-size:13px;">
                                    {{ $categories->total() }} kategori
                                </div>
                                <div>{{ $categories->links() }}</div>
                            </div>
                        </div>
                    @else
                        {{-- List view --}}
                        <div class="row">
                            <div class="col-12">
                                <div class="card-clean">
                                    <div class="table-responsive">
                                        <table class="table-striped table">
                                            <thead>
                                                <tr>
                                                    <th></th>
                                                    <th>Nama</th>
                                                    <th>Deskripsi</th>
                                                    <th class="text-center">Produk</th>
                                                    <th class="text-center">Status</th>
                                                    <th>Dibuat</th>
                                                    <th class="text-center">Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($categories as $cat)
                                                    <tr>
                                                        <td>
                                                            <div style="width:36px;height:36px;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;font-size:16px;background:{{ $cat->color }}20;color:{{ $cat->color }};">
                                                                <i class="fas fa-{{ $cat->icon ?? 'tag' }}"></i>
                                                            </div>
                                                        </td>
                                                        <td class="font-weight-bold">{{ $cat->name }}</td>
                                                        <td class="text-muted">{{ Str::limit($cat->description ?? '—', 60) }}</td>
                                                        <td class="text-center">{{ $cat->products_count }}</td>
                                                        <td class="text-center">
                                                            @if ($cat->is_active)
                                                                <span class="badge badge-soft-success">Aktif</span>
                                                            @else
                                                                <span class="badge badge-soft-secondary">Nonaktif</span>
                                                            @endif
                                                        </td>
                                                        <td>{{ formatDate($cat->created_at, 'd M Y') }}</td>
                                                        <td>
                                                            <div class="d-flex justify-content-center">
                                                                @can('update', $cat)
                                                                    <a href='{{ route('categories.edit', $cat->id) }}'
                                                                        class="btn btn-sm btn-info btn-icon">
                                                                        <i class="fas fa-edit"></i>
                                                                    </a>
                                                                @endcan
                                                                @can('delete', $cat)
                                                                    <form action="{{ route('categories.destroy', $cat->id) }}" method="POST" class="ml-2">
                                                                        @csrf
                                                                        @method('DELETE')
                                                                        <button type="submit" class="btn btn-sm btn-danger btn-icon confirm-delete"
                                                                            data-title="Hapus kategori?"
                                                                            data-text="Kategori '{{ $cat->name }}' akan dihapus.">
                                                                            <i class="fas fa-trash"></i>
                                                                        </button>
                                                                    </form>
                                                                @endcan
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mt-3">
                                        <div class="text-muted" style="font-size:13px;">
                                            Menampilkan {{ $categories->firstItem() }}–{{ $categories->lastItem() }}
                                            dari {{ $categories->total() }}
                                        </div>
                                        <div>{{ $categories->links() }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                @else
                    <div class="row">
                        <div class="col-12">
                            <div class="card-clean">
                                <x-empty-state
                                    icon="tags"
                                    title="Belum ada kategori"
                                    description="Tambahkan kategori untuk mengelompokkan produk."
                                    :action-label="auth()->user()->can('create', App\Models\Category::class) ? 'Tambah Kategori' : null"
                                    :action-url="auth()->user()->can('create', App\Models\Category::class) ? route('categories.create') : null"
                                />
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </section>
    </div>
@endsection
