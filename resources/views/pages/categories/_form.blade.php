@php
    $cat = $category ?? null;
    $icons = ['tag', 'utensils', 'coffee', 'cookie-bite', 'ice-cream', 'pizza-slice', 'hamburger',
        'gift', 'birthday-cake', 'wine-glass', 'cocktail', 'leaf', 'pepper-hot', 'fish',
        'box-open', 'shopping-bag', 'star', 'fire'];
    $colors = ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899',
        '#06B6D4', '#F97316', '#84CC16', '#6B7280'];
@endphp

<div class="row">
    <div class="col-lg-8 col-12">
        <div class="card-clean mb-3">
            <h4 class="mb-3">Informasi Kategori</h4>

            <div class="form-group">
                <label>Nama Kategori <span class="text-danger">*</span></label>
                <input type="text" name="name" value="{{ old('name', $cat?->name) }}"
                    class="form-control @error('name') is-invalid @enderror" autofocus required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="form-group">
                <label>Deskripsi</label>
                <textarea name="description" rows="3" maxlength="500"
                    class="form-control @error('description') is-invalid @enderror">{{ old('description', $cat?->description) }}</textarea>
                @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="form-row">
                <div class="col-md-6 form-group">
                    <label>Urutan Tampil</label>
                    <input type="number" name="sort_order" value="{{ old('sort_order', $cat?->sort_order ?? 0) }}" min="0"
                        class="form-control @error('sort_order') is-invalid @enderror">
                    <small class="text-muted">Semakin kecil semakin atas.</small>
                </div>
                <div class="col-md-6 form-group d-flex align-items-end">
                    <div class="custom-control custom-switch">
                        <input type="checkbox" name="is_active" value="1" class="custom-control-input"
                            id="is-active-toggle" @checked(old('is_active', $cat?->is_active ?? true))>
                        <label class="custom-control-label" for="is-active-toggle">
                            Kategori Aktif
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4 col-12">
        <div class="card-clean mb-3">
            <h4 class="mb-3">Tampilan</h4>

            {{-- Preview --}}
            <div class="text-center mb-3">
                <div id="cat-preview-icon" class="d-inline-flex align-items-center justify-content-center"
                    style="width:64px;height:64px;border-radius:14px;background:#DBEAFE;color:#3B82F6;font-size:28px;">
                    <i class="fas fa-{{ old('icon', $cat?->icon ?? 'tag') }}"></i>
                </div>
                <div class="mt-2 text-muted" style="font-size:12px;">Preview</div>
            </div>

            {{-- Icon picker --}}
            <div class="form-group">
                <label>Icon</label>
                <input type="hidden" name="icon" id="icon-input" value="{{ old('icon', $cat?->icon ?? 'tag') }}">
                <div class="d-flex flex-wrap" style="gap:6px;">
                    @foreach ($icons as $i)
                        <button type="button" class="icon-option btn btn-light p-2" data-icon="{{ $i }}"
                            style="width:42px;height:42px;display:inline-flex;align-items:center;justify-content:center;border-radius:8px;">
                            <i class="fas fa-{{ $i }}"></i>
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Color picker --}}
            <div class="form-group">
                <label>Warna</label>
                <input type="hidden" name="color" id="color-input" value="{{ old('color', $cat?->color ?? '#3B82F6') }}">
                <div class="d-flex flex-wrap" style="gap:6px;">
                    @foreach ($colors as $c)
                        <button type="button" class="color-option" data-color="{{ $c }}"
                            style="width:32px;height:32px;border-radius:50%;background:{{ $c }};border:2px solid transparent;cursor:pointer;">
                        </button>
                    @endforeach
                </div>
                <small class="text-muted">Atau pilih warna kustom:</small>
                <input type="color" id="color-custom" value="{{ old('color', $cat?->color ?? '#3B82F6') }}"
                    class="form-control mt-1" style="height:38px;">
            </div>
        </div>

        <button type="submit" class="btn btn-primary btn-block">
            <i class="fas fa-save mr-1"></i> Simpan
        </button>
        <a href="{{ route('categories.index') }}" class="btn btn-light btn-block">Batal</a>
    </div>
</div>

@push('scripts')
    <script>
        (function () {
            const iconInput = document.getElementById('icon-input');
            const colorInput = document.getElementById('color-input');
            const colorCustom = document.getElementById('color-custom');
            const previewWrap = document.getElementById('cat-preview-icon');
            const iconOptions = document.querySelectorAll('.icon-option');
            const colorOptions = document.querySelectorAll('.color-option');

            function updatePreview() {
                const icon = iconInput.value || 'tag';
                const color = colorInput.value || '#3B82F6';
                previewWrap.innerHTML = '<i class="fas fa-' + icon + '"></i>';
                previewWrap.style.background = color + '20';
                previewWrap.style.color = color;

                iconOptions.forEach(b => {
                    if (b.dataset.icon === icon) {
                        b.style.background = color + '20';
                        b.style.color = color;
                    } else {
                        b.style.background = '';
                        b.style.color = '';
                    }
                });
                colorOptions.forEach(b => {
                    b.style.borderColor = b.dataset.color === color ? '#111827' : 'transparent';
                });
            }

            iconOptions.forEach(b => b.addEventListener('click', () => {
                iconInput.value = b.dataset.icon;
                updatePreview();
            }));
            colorOptions.forEach(b => b.addEventListener('click', () => {
                colorInput.value = b.dataset.color;
                if (colorCustom) colorCustom.value = b.dataset.color;
                updatePreview();
            }));
            if (colorCustom) {
                colorCustom.addEventListener('input', e => {
                    colorInput.value = e.target.value;
                    updatePreview();
                });
            }

            updatePreview();
        })();
    </script>
@endpush
