@php $u = $user ?? null; @endphp

<div class="row">
    <div class="col-lg-8 col-12">
        <div class="card-clean mb-3">
            <h4 class="mb-3">Informasi Pengguna</h4>

            <div class="form-group">
                <label>Nama Lengkap <span class="text-danger">*</span></label>
                <input type="text" name="name" value="{{ old('name', $u?->name) }}"
                    class="form-control @error('name') is-invalid @enderror" autofocus required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="form-row">
                <div class="col-md-6 form-group">
                    <label>Email <span class="text-danger">*</span></label>
                    <input type="email" name="email" value="{{ old('email', $u?->email) }}"
                        class="form-control @error('email') is-invalid @enderror" required>
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 form-group">
                    <label>No. HP</label>
                    <input type="tel" name="phone" value="{{ old('phone', $u?->phone) }}"
                        class="form-control @error('phone') is-invalid @enderror">
                    @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <h5 class="mt-4 mb-3">Password</h5>
            <div class="form-row">
                <div class="col-md-6 form-group">
                    <label>{{ $u ? 'Password Baru' : 'Password' }} @if (! $u)<span class="text-danger">*</span>@endif</label>
                    <input type="password" name="password"
                        class="form-control @error('password') is-invalid @enderror" @if (! $u) required @endif>
                    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    @if ($u)
                        <small class="text-muted">Kosongkan untuk pertahankan password lama.</small>
                    @else
                        <small class="text-muted">Min. 8 karakter.</small>
                    @endif
                </div>
                <div class="col-md-6 form-group">
                    <label>Konfirmasi Password @if (! $u)<span class="text-danger">*</span>@endif</label>
                    <input type="password" name="password_confirmation"
                        class="form-control" @if (! $u) required @endif>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4 col-12">
        <div class="card-clean mb-3">
            <h4 class="mb-3">Foto Profil</h4>
            <div class="mb-3 text-center">
                <img id="avatar-preview" src="{{ $u?->avatar_url ?? 'https://ui-avatars.com/api/?name=?&background=3B82F6&color=fff' }}"
                    style="width:120px;height:120px;border-radius:50%;object-fit:cover;border:3px solid #f3f4f6;">
            </div>
            <input type="file" name="avatar" accept="image/*"
                class="form-control-file @error('avatar') is-invalid @enderror" id="avatar-input">
            <small class="text-muted">PNG/JPG, maks 1MB.</small>
            @error('avatar')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>

        <div class="card-clean mb-3">
            <h4 class="mb-3">Role & Status</h4>
            <div class="form-group">
                <label>Role <span class="text-danger">*</span></label>
                <select name="roles" class="form-control @error('roles') is-invalid @enderror" required>
                    <option value="">Pilih role</option>
                    @foreach ($roleOptions as $value => $label)
                        <option value="{{ $value }}" @selected(old('roles', $u?->roles) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('roles')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="custom-control custom-switch">
                <input type="checkbox" name="is_active" value="1" class="custom-control-input"
                    id="is-active-toggle" @checked(old('is_active', $u?->is_active ?? true))>
                <label class="custom-control-label" for="is-active-toggle">Akun Aktif</label>
            </div>
        </div>

        <button type="submit" class="btn btn-primary btn-block">
            <i class="fas fa-save mr-1"></i> Simpan
        </button>
        <a href="{{ route('user.index') }}" class="btn btn-light btn-block">Batal</a>
    </div>
</div>

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
        })();
    </script>
@endpush
