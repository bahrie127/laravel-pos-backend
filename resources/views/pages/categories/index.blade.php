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
                            <form method="GET" action="{{ route('categories.index') }}"
                                class="d-flex align-items-center" style="gap:8px;flex-wrap:wrap;">
                                <input type="hidden" name="view" value="{{ $view }}">

                                <div class="input-group" style="max-width:320px;flex:1;min-width:200px;">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text bg-white border-right-0">
                                            <i class="fas fa-search text-muted"></i>
                                        </span>
                                    </div>
                                    <input type="text" name="q" value="{{ request('q') }}"
                                        class="form-control border-left-0" placeholder="Cari kategori...">
                                </div>

                                <button type="submit" class="btn btn-primary">Cari</button>

                                @if (request('q'))
                                    <a href="{{ route('categories.index', ['view' => $view]) }}"
                                        class="btn btn-outline-secondary">
                                        <i class="fas fa-times mr-1"></i> Reset
                                    </a>
                                @endif

                                <div class="ml-auto view-toggle">
                                    <a href="{{ request()->fullUrlWithQuery(['view' => 'grid']) }}"
                                        class="btn {{ $view === 'grid' ? 'active' : '' }}">
                                        <i class="fas fa-th-large"></i> Grid
                                    </a>
                                    <a href="{{ request()->fullUrlWithQuery(['view' => 'list']) }}"
                                        class="btn {{ $view === 'list' ? 'active' : '' }}">
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
                                <div class="col-6 col-md-4 col-lg-3 col-xl-2 mb-3">
                                    <div class="category-card">
                                        <div class="d-flex align-items-start justify-content-between mb-3">
                                            <div class="cat-icon"
                                                style="background:{{ $cat->color }}20;color:{{ $cat->color }};">
                                                <i class="fas fa-{{ $cat->icon ?? 'tag' }}"></i>
                                            </div>
                                            @if (! $cat->is_active)
                                                <span class="badge badge-soft-secondary">Nonaktif</span>
                                            @endif
                                        </div>

                                        <div class="cat-name">{{ $cat->name }}</div>
                                        <div class="cat-meta">
                                            <i class="fas fa-cube"></i>
                                            <span>{{ $cat->products_count }} produk</span>
                                        </div>

                                        @if ($cat->description)
                                            <div class="cat-desc">{{ Str::limit($cat->description, 60) }}</div>
                                        @endif

                                        @if (auth()->user()->can('update', $cat) || auth()->user()->can('delete', $cat))
                                            <div class="cat-actions">
                                                @can('update', $cat)
                                                    <a href="{{ route('categories.edit', $cat->id) }}"
                                                        class="btn btn-outline-secondary" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                @endcan
                                                @can('delete', $cat)
                                                    <form action="{{ route('categories.destroy', $cat->id) }}"
                                                        method="POST" class="m-0" style="flex:1;">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit"
                                                            class="btn btn-outline-danger btn-block confirm-delete"
                                                            data-title="Hapus kategori?"
                                                            data-text="Kategori '{{ $cat->name }}' akan dihapus.">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                @endcan
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="row">
                            <div class="col-12 d-flex justify-content-between align-items-center flex-wrap"
                                style="gap:8px;">
                                <div class="text-muted" style="font-size:13px;">
                                    Menampilkan {{ $categories->firstItem() }}–{{ $categories->lastItem() }}
                                    dari {{ $categories->total() }} kategori
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
                                                    <th style="width:60px;"></th>
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
                                    <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap"
                                        style="gap:8px;">
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
