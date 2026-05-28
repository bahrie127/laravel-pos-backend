@extends('layouts.app')

@section('title', 'Profil Saya')

@section('main')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1>Profil Saya</h1>
                <div class="section-header-breadcrumb">
                    <div class="breadcrumb-item active"><a href="{{ route('home') }}">Dashboard</a></div>
                    <div class="breadcrumb-item">Profil</div>
                </div>
            </div>

            <div class="section-body">
                @include('layouts.alert')

                {{-- Profile header --}}
                <div class="row">
                    <div class="col-12">
                        <div class="card-clean">
                            <div class="d-flex align-items-center" style="gap:20px;flex-wrap:wrap;">
                                <img src="{{ $user->avatar_url }}" alt=""
                                    style="width:88px;height:88px;border-radius:50%;object-fit:cover;border:3px solid #f3f4f6;">
                                <div>
                                    <h3 class="m-0">{{ $user->name }}</h3>
                                    <div class="text-muted">{{ $user->email }}</div>
                                    @php $role = $user->role(); @endphp
                                    @if ($role)
                                        <span class="badge {{ $role->badgeClass() }} mt-2">{{ $role->label() }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Tabs --}}
                <div class="row">
                    <div class="col-12">
                        <div class="card-clean p-0">
                            <ul class="nav nav-tabs px-3 pt-3" id="profileTabs" role="tablist" style="border-bottom:1px solid #e5e7eb;">
                                <li class="nav-item">
                                    <a class="nav-link active" id="info-tab" data-toggle="tab" href="#tab-info" role="tab">
                                        <i class="fas fa-user mr-1"></i> Informasi
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="password-tab" data-toggle="tab" href="#tab-password" role="tab">
                                        <i class="fas fa-key mr-1"></i> Password
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link text-danger" id="danger-tab" data-toggle="tab" href="#tab-danger" role="tab">
                                        <i class="fas fa-exclamation-triangle mr-1"></i> Hapus Akun
                                    </a>
                                </li>
                            </ul>

                            <div class="tab-content p-4">
                                {{-- Tab Info --}}
                                <div class="tab-pane fade show active" id="tab-info" role="tabpanel">
                                    <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
                                        @csrf
                                        @method('PUT')

                                        <div class="row">
                                            <div class="col-md-4 text-center mb-3">
                                                <img id="avatar-preview" src="{{ $user->avatar_url }}"
                                                    style="width:140px;height:140px;border-radius:50%;object-fit:cover;border:3px solid #f3f4f6;">
                                                <div class="mt-3">
                                                    <input type="file" name="avatar" accept="image/*" id="avatar-input"
                                                        class="form-control-file">
                                                    <small class="text-muted">PNG/JPG, maks 1MB.</small>
                                                </div>
                                            </div>

                                            <div class="col-md-8">
                                                <div class="form-group">
                                                    <label>Nama Lengkap <span class="text-danger">*</span></label>
                                                    <input type="text" name="name" value="{{ old('name', $user->name) }}"
                                                        class="form-control @error('name') is-invalid @enderror" required>
                                                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                </div>
                                                <div class="form-group">
                                                    <label>Email <span class="text-danger">*</span></label>
                                                    <input type="email" name="email" value="{{ old('email', $user->email) }}"
                                                        class="form-control @error('email') is-invalid @enderror" required>
                                                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                </div>
                                                <div class="form-group">
                                                    <label>No. HP</label>
                                                    <input type="tel" name="phone" value="{{ old('phone', $user->phone) }}"
                                                        class="form-control @error('phone') is-invalid @enderror">
                                                    @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                </div>
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="fas fa-save mr-1"></i> Simpan Perubahan
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>

                                {{-- Tab Hapus Akun (Danger Zone) --}}
                                <div class="tab-pane fade" id="tab-danger" role="tabpanel">
                                    <div style="max-width:560px;">
                                        <div class="alert alert-danger d-flex" style="gap:12px;align-items:flex-start;">
                                            <i class="fas fa-exclamation-triangle mt-1"></i>
                                            <div>
                                                <strong>Zona Bahaya</strong>
                                                <p class="mb-0 mt-1" style="font-size:13px;line-height:1.5;">
                                                    Menghapus akun bersifat <strong>permanen</strong>. Semua token sesi akan dicabut
                                                    dan data identitas (nama, email, telepon, foto) Anda akan dihilangkan.
                                                    Riwayat transaksi yang sudah tercatat tetap disimpan dengan identitas teranonimisasi
                                                    untuk keperluan audit.
                                                </p>
                                            </div>
                                        </div>

                                        <ul class="text-muted" style="font-size:13px;line-height:1.7;">
                                            <li>Anda akan otomatis logout dari semua perangkat.</li>
                                            <li>Email <code>{{ $user->email }}</code> tidak bisa dipakai login lagi.</li>
                                            <li>Aksi ini <strong>tidak dapat dibatalkan</strong> oleh diri sendiri.</li>
                                        </ul>

                                        <form id="delete-account-form" action="{{ route('profile.destroy') }}" method="POST" class="mt-4">
                                            @csrf
                                            @method('DELETE')

                                            <div class="form-group">
                                                <label>
                                                    Ketik <code>HAPUS AKUN</code> untuk konfirmasi
                                                    <span class="text-danger">*</span>
                                                </label>
                                                <input type="text" name="confirmation" autocomplete="off"
                                                    class="form-control @error('confirmation') is-invalid @enderror"
                                                    placeholder="HAPUS AKUN" required>
                                                @error('confirmation')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                            </div>

                                            <div class="form-group">
                                                <label>Password Anda <span class="text-danger">*</span></label>
                                                <input type="password" name="password" autocomplete="current-password"
                                                    class="form-control @error('password') is-invalid @enderror" required>
                                                <small class="text-muted">Pemastian terakhir kalau memang Anda.</small>
                                                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                            </div>

                                            <button type="submit" class="btn btn-danger" id="delete-account-btn">
                                                <i class="fas fa-trash mr-1"></i> Hapus Akun Saya Permanen
                                            </button>
                                        </form>
                                    </div>
                                </div>

                                {{-- Tab Password --}}
                                <div class="tab-pane fade" id="tab-password" role="tabpanel">
                                    <form action="{{ route('profile.password') }}" method="POST" style="max-width:500px;">
                                        @csrf
                                        @method('PUT')

                                        <div class="form-group">
                                            <label>Password Sekarang <span class="text-danger">*</span></label>
                                            <input type="password" name="current_password"
                                                class="form-control @error('current_password') is-invalid @enderror" required>
                                            @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="form-group">
                                            <label>Password Baru <span class="text-danger">*</span></label>
                                            <input type="password" name="password"
                                                class="form-control @error('password') is-invalid @enderror" required>
                                            <small class="text-muted">Min. 8 karakter.</small>
                                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="form-group">
                                            <label>Konfirmasi Password Baru <span class="text-danger">*</span></label>
                                            <input type="password" name="password_confirmation" class="form-control" required>
                                        </div>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-key mr-1"></i> Ubah Password
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const input = document.getElementById('avatar-input');
            const preview = document.getElementById('avatar-preview');
            if (!input) return;
            input.addEventListener('change', e => {
                const file = e.target.files[0];
                if (file) preview.src = URL.createObjectURL(file);
            });

            // Auto-open password tab kalau ada error pada current_password atau password
            @if ($errors->hasAny(['current_password']))
                $('#password-tab').tab('show');
            @endif

            // Auto-open danger tab kalau ada error pada delete-form fields
            @if ($errors->hasAny(['confirmation']) || ($errors->has('password') && !$errors->has('current_password')))
                $('#danger-tab').tab('show');
            @endif

            // Confirm + double-check before submitting delete-account form
            const deleteForm = document.getElementById('delete-account-form');
            if (deleteForm) {
                deleteForm.addEventListener('submit', function (e) {
                    e.preventDefault();
                    if (typeof Swal === 'undefined') {
                        if (confirm('Yakin hapus akun? Aksi ini permanen.')) deleteForm.submit();
                        return;
                    }
                    Swal.fire({
                        title: 'Hapus akun permanen?',
                        text: 'Anda akan langsung logout. Aksi ini tidak bisa dibatalkan.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#EF4444',
                        confirmButtonText: 'Ya, hapus akun saya',
                        cancelButtonText: 'Batal',
                        reverseButtons: true,
                    }).then(function (r) {
                        if (r.isConfirmed) deleteForm.submit();
                    });
                });
            }
        })();
    </script>
@endpush
