@php
    $p = $promo ?? null;
    $types = [
        \App\Models\Promo::TYPE_PERCENT => 'Persen (%)',
        \App\Models\Promo::TYPE_RUPIAH => 'Potongan Rupiah',
        \App\Models\Promo::TYPE_B1G1 => 'Beli 1 Gratis 1',
    ];
@endphp

<div class="row">
    <div class="col-lg-8 col-12">
        <div class="card-clean mb-3">
            <h4 class="mb-3">Informasi Promo</h4>

            <div class="form-group">
                <label>Nama Promo <span class="text-danger">*</span></label>
                <input type="text" name="name" value="{{ old('name', $p?->name) }}"
                    class="form-control @error('name') is-invalid @enderror"
                    placeholder="mis. Diskon Lebaran 20%" autofocus required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="form-row">
                <div class="col-md-6 form-group">
                    <label>Tipe Promo <span class="text-danger">*</span></label>
                    <select name="type" id="promo-type"
                        class="form-control @error('type') is-invalid @enderror" required>
                        @foreach ($types as $val => $label)
                            <option value="{{ $val }}" @selected(old('type', $p?->type) === $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6 form-group">
                    <label>
                        Nilai <span class="text-danger">*</span>
                        <small id="value-hint" class="text-muted ml-1"></small>
                    </label>
                    <input type="number" name="value" id="promo-value"
                        value="{{ old('value', $p?->value ?? 0) }}" min="0"
                        class="form-control @error('value') is-invalid @enderror" required>
                    @error('value')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="form-row">
                <div class="col-md-6 form-group">
                    <label>Kode Voucher</label>
                    <input type="text" name="code" value="{{ old('code', $p?->code) }}" maxlength="50"
                        class="form-control text-uppercase @error('code') is-invalid @enderror"
                        placeholder="mis. RAMADHAN2026">
                    <small class="text-muted">Kosongkan kalau promo otomatis (tanpa kode).</small>
                    @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6 form-group">
                    <label>Minimum Belanja (Rp)</label>
                    <input type="number" name="min_subtotal"
                        value="{{ old('min_subtotal', $p?->min_subtotal ?? 0) }}" min="0"
                        class="form-control @error('min_subtotal') is-invalid @enderror">
                    @error('min_subtotal')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>

        <div class="card-clean mb-3">
            <h4 class="mb-3">Periode Berlaku</h4>
            <p class="text-muted" style="font-size:13px;">Kosongkan kalau promo berlaku tanpa batas waktu.</p>

            <div class="form-row">
                <div class="col-md-6 form-group">
                    <label>Mulai</label>
                    <input type="datetime-local" name="starts_at"
                        value="{{ old('starts_at', $p?->starts_at?->format('Y-m-d\TH:i')) }}"
                        class="form-control @error('starts_at') is-invalid @enderror">
                    @error('starts_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6 form-group">
                    <label>Berakhir</label>
                    <input type="datetime-local" name="ends_at"
                        value="{{ old('ends_at', $p?->ends_at?->format('Y-m-d\TH:i')) }}"
                        class="form-control @error('ends_at') is-invalid @enderror">
                    @error('ends_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4 col-12">
        <div class="card-clean mb-3">
            <h4 class="mb-3">Status</h4>

            <div class="custom-control custom-switch mb-3">
                <input type="checkbox" name="active" value="1" class="custom-control-input"
                    id="active-toggle" @checked(old('active', $p?->active ?? true))>
                <label class="custom-control-label" for="active-toggle">
                    Promo Aktif
                </label>
            </div>

            <div class="text-muted" style="font-size:12px;">
                Promo non-aktif tidak akan muncul di aplikasi kasir & tidak bisa dipakai walaupun kode-nya dikenal.
            </div>
        </div>

        <button type="submit" class="btn btn-primary btn-block">
            <i class="fas fa-save mr-1"></i> Simpan
        </button>
        <a href="{{ route('promo.index') }}" class="btn btn-light btn-block">Batal</a>
    </div>
</div>

@push('scripts')
    <script>
        (function () {
            const typeEl = document.getElementById('promo-type');
            const valEl = document.getElementById('promo-value');
            const hint = document.getElementById('value-hint');

            function syncHint() {
                const t = typeEl.value;
                if (t === 'percent') {
                    hint.textContent = '(1–100)';
                    valEl.max = 100;
                } else if (t === 'rupiah') {
                    hint.textContent = '(dalam Rupiah)';
                    valEl.removeAttribute('max');
                } else {
                    hint.textContent = '(diabaikan untuk B1G1)';
                    valEl.removeAttribute('max');
                }
            }
            typeEl.addEventListener('change', syncHint);
            syncHint();
        })();
    </script>
@endpush
