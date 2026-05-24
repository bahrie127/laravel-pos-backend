@extends('layouts.app')

@section('title', 'Pengguna')

@section('main')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1>Pengguna</h1>
                <div class="section-header-button">
                    @can('create', App\Models\User::class)
                        <a href="{{ route('user.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus mr-1"></i> Tambah Pengguna
                        </a>
                    @endcan
                </div>
                <div class="section-header-breadcrumb">
                    <div class="breadcrumb-item active"><a href="{{ route('home') }}">Dashboard</a></div>
                    <div class="breadcrumb-item">Pengguna</div>
                </div>
            </div>

            <div class="section-body">
                <div class="row">
                    <div class="col-12">
                        @include('layouts.alert')
                    </div>
                </div>

                {{-- Filter --}}
                <div class="row">
                    <div class="col-12">
                        <div class="card-clean">
                            <form method="GET" action="{{ route('user.index') }}">
                                <div class="form-row">
                                    <div class="col-md-5 form-group">
                                        <label class="text-muted" style="font-size:12px;">Cari nama / email</label>
                                        <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Cari...">
                                    </div>
                                    <div class="col-md-4 form-group">
                                        <label class="text-muted" style="font-size:12px;">Role</label>
                                        <select name="role" class="form-control">
                                            <option value="">Semua role</option>
                                            @foreach ($roleOptions as $value => $label)
                                                <option value="{{ $value }}" @selected(request('role') === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3 form-group d-flex align-items-end">
                                        <button type="submit" class="btn btn-primary mr-2">
                                            <i class="fas fa-search"></i>
                                        </button>
                                        <a href="{{ route('user.index') }}" class="btn btn-outline-secondary">Reset</a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card-clean">
                            @if ($users->count() > 0)
                                <div class="table-responsive">
                                    <table class="table-striped table">
                                        <thead>
                                            <tr>
                                                <th></th>
                                                <th>Nama</th>
                                                <th>Email & HP</th>
                                                <th class="text-center">Role</th>
                                                <th class="text-center">Status</th>
                                                <th>Login Terakhir</th>
                                                <th class="text-center">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($users as $u)
                                                @php $role = $u->role(); @endphp
                                                <tr>
                                                    <td>
                                                        <img src="{{ $u->avatar_url }}" alt=""
                                                            style="width:36px;height:36px;border-radius:50%;object-fit:cover;">
                                                    </td>
                                                    <td>
                                                        <div class="font-weight-bold">{{ $u->name }}</div>
                                                        <div class="text-muted" style="font-size:12px;">Bergabung {{ formatDate($u->created_at, 'd M Y') }}</div>
                                                    </td>
                                                    <td>
                                                        <div>{{ $u->email }}</div>
                                                        <div class="text-muted" style="font-size:12px;">{{ $u->phone ?? '—' }}</div>
                                                    </td>
                                                    <td class="text-center">
                                                        @if ($role)
                                                            <span class="badge {{ $role->badgeClass() }}">{{ $role->label() }}</span>
                                                        @else
                                                            <span class="badge badge-soft-secondary">—</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-center">
                                                        @if ($u->is_active)
                                                            <span class="badge badge-soft-success">Aktif</span>
                                                        @else
                                                            <span class="badge badge-soft-secondary">Nonaktif</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if ($u->last_login_at)
                                                            <div>{{ $u->last_login_at->translatedFormat('d M Y H:i') }}</div>
                                                            <div class="text-muted" style="font-size:12px;">{{ $u->last_login_ip ?? '—' }}</div>
                                                        @else
                                                            <span class="text-muted" style="font-size:12px;">Belum pernah</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <div class="d-flex justify-content-center">
                                                            @can('update', $u)
                                                                <a href='{{ route('user.edit', $u->id) }}' class="btn btn-sm btn-info btn-icon">
                                                                    <i class="fas fa-edit"></i>
                                                                </a>
                                                            @endcan
                                                            @can('delete', $u)
                                                                <form action="{{ route('user.destroy', $u->id) }}" method="POST" class="ml-2">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button type="submit" class="btn btn-sm btn-danger btn-icon confirm-delete"
                                                                        data-title="Hapus pengguna?"
                                                                        data-text="Pengguna '{{ $u->name }}' akan dihapus.">
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
                                        Menampilkan {{ $users->firstItem() }}–{{ $users->lastItem() }}
                                        dari {{ $users->total() }} pengguna
                                    </div>
                                    <div>{{ $users->links() }}</div>
                                </div>
                            @else
                                <x-empty-state
                                    icon="users"
                                    title="Belum ada pengguna"
                                    description="Tambahkan pengguna untuk akses panel admin."
                                    :action-label="auth()->user()->can('create', App\Models\User::class) ? 'Tambah Pengguna' : null"
                                    :action-url="auth()->user()->can('create', App\Models\User::class) ? route('user.create') : null"
                                />
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
