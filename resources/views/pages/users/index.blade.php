@extends('layouts.app')

@section('title', 'Users')

@push('style')
    <!-- CSS Libraries -->
    <link rel="stylesheet" href="{{ asset('library/selectric/public/selectric.css') }}">
@endpush

@section('main')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1>Users</h1>
                <div class="section-header-button">
                    <a href="{{ route('user.create') }}" class="btn btn-primary">Add New</a>
                </div>
                <div class="section-header-breadcrumb">
                    <div class="breadcrumb-item active"><a href="#">Dashboard</a></div>
                    <div class="breadcrumb-item"><a href="#">Users</a></div>
                    <div class="breadcrumb-item">All Users</div>
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
                                    <form method="GET" action="{{ route('user.index') }}">
                                        <div class="input-group">
                                            <input type="text" class="form-control" placeholder="Search" name="name">
                                            <div class="input-group-append">
                                                <button class="btn btn-primary"><i class="fas fa-search"></i></button>
                                            </div>
                                        </div>
                                    </form>
                                </div>

                                <div class="clearfix mb-3"></div>

                                @if ($users->count() > 0)
                                    <div class="table-responsive">
                                        <table class="table-striped table">
                                            <tr>
                                                <th></th>
                                                <th>Nama</th>
                                                <th>Email</th>
                                                <th>No. HP</th>
                                                <th>Bergabung</th>
                                                <th class="text-center">Aksi</th>
                                            </tr>
                                            @foreach ($users as $user)
                                                <tr>
                                                    <td>
                                                        <span class="avatar" style="display:inline-flex;width:36px;height:36px;border-radius:50%;background:#3B82F6;color:#fff;align-items:center;justify-content:center;font-weight:600;font-size:13px;">
                                                            {{ initials($user->name) }}
                                                        </span>
                                                    </td>
                                                    <td class="font-weight-bold">{{ $user->name }}</td>
                                                    <td>{{ $user->email }}</td>
                                                    <td>{{ $user->phone ?? '—' }}</td>
                                                    <td>{{ formatDate($user->created_at, 'd M Y') }}</td>
                                                    <td>
                                                        <div class="d-flex justify-content-center">
                                                            <a href='{{ route('user.edit', $user->id) }}'
                                                                class="btn btn-sm btn-info btn-icon">
                                                                <i class="fas fa-edit"></i> Edit
                                                            </a>

                                                            <form action="{{ route('user.destroy', $user->id) }}" method="POST" class="ml-2">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-sm btn-danger btn-icon confirm-delete"
                                                                    data-title="Hapus pengguna?"
                                                                    data-text="Pengguna '{{ $user->name }}' akan dihapus.">
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
                                        {{ $users->withQueryString()->links() }}
                                    </div>
                                @else
                                    <x-empty-state
                                        icon="users"
                                        title="Belum ada pengguna"
                                        description="Tambahkan pengguna untuk mengakses panel admin."
                                        :action-label="'Tambah Pengguna'"
                                        :action-url="route('user.create')"
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
