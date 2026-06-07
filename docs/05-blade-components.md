# Step 05 — Komponen Blade Reusable

## Tujuan

Mempunyai **library komponen Blade** yang dipakai di seluruh aplikasi: form input, card, button, badge, modal, stat-card, page-header, empty-state, dll. Tujuannya konsistensi UI + lebih sedikit kode duplikat di tiap view.

## Prasyarat

- Step 04 selesai (layout & sidebar siap)

## Konteks

Tanpa komponen, tiap form input butuh 8 baris HTML berulang. Dengan `<x-form-input>`, cuma 1 baris. 22 komponen ini dipakai berulang di Step 10-18.

## Prompt untuk AI

````
Project Laravel POS sudah punya layout + Stisla. Sekarang buat 22 komponen Blade reusable di `resources/views/components/`. Setiap komponen ringkas, terdokumentasi via props, dan match design system di `.claude/rules/01-design-system.md`.

Buat file-file berikut SEMUA:

1. `button.blade.php` — props: variant (primary/secondary/success/warning/danger/ghost), size (sm/md/lg), icon (font-awesome name), href (kalau ada jadi <a>), type (submit/button), loading (bool). Render dengan class Bootstrap `btn btn-{variant} btn-{size}` + ikon fa.

2. `card.blade.php` — props: title, subtitle, noPadding (bool). Slot $actions (untuk tombol di header), slot default (body). Render `<div class="card-clean">` dengan header + body.

3. `page-header.blade.php` — props: title, subtitle, breadcrumbs (array of ['label','url']). Slot $actions (tombol di kanan). Render H1 + breadcrumb nav + slot.

4. `breadcrumb.blade.php` — props: items (array). Render nav breadcrumb Bootstrap.

5. `form-input.blade.php` — props: name, label, type=text, value=null, placeholder, required (bool), readonly (bool), help (text bawah), icon (prepend). Render `<label>` + `<input class="form-control @error is-invalid">` + error message.

6. `form-select.blade.php` — props: name, label, options (assoc array key=>label), value, placeholder, required, help. Render `<label>` + `<select class="form-control">` dengan options.

7. `form-textarea.blade.php` — props: name, label, value, placeholder, rows=3, required, maxlength, counter (bool), help. Render textarea + character counter (kalau counter=true).

8. `form-toggle.blade.php` — props: name, label, checked (bool), value=1, help. Render Bootstrap switch (custom-switch).

9. `form-checkbox.blade.php` — props: name, label, checked, value=1. Standar checkbox.

10. `modal.blade.php` — props: id, title, size (sm/lg/xl), static (bool). Slots: $header, $footer. Render Bootstrap modal struct.

11. `stat-card.blade.php` — props: label, value, icon (fa name), color (primary/success/warning/danger/info), delta (number ± %), deltaLabel, href. Render card dengan icon kiri besar + label kecil di atas + value besar bold + delta arrow naik/turun. Klik card navigasi ke href.

12. `order-status-badge.blade.php` — prop: status (pending/paid/cancelled/refunded). Render badge dengan class color sesuai status (paid=success, pending=warning, cancelled=danger, refunded=info).

13. `role-badge.blade.php` — prop: role (owner/admin/kasir). Render badge sesuai UserRole enum (owner=danger, admin=primary, kasir=info).

14. `sort-link.blade.php` — props: column (string), label (slot). Render <a> dengan query string sort=column, direction toggle asc/desc. Tampilkan icon arrow naik/turun sesuai current sort.

15. `icon.blade.php` — props: name, set (default 'fas'). Render `<i class="fas fa-{name}"></i>`. Pembungkus sederhana font-awesome.

16. `empty-state.blade.php` — props: icon (fa name), title, description, actionLabel (button text), actionUrl. Render placeholder center: icon besar abu + title + desc + tombol CTA.

17. `loading-skeleton.blade.php` — props: rows (default 3), height (px). Render div abu animasi shimmer untuk loading placeholder.

18. `auth-header.blade.php` — render logo + nama brand center untuk halaman auth.

19. `reports-filter.blade.php` — props: action (form url), from, to, kasirList, showKasir (bool). Render form filter date range + dropdown kasir + submit.

20. `reports-filter-bar.blade.php` — versi advanced dari #19: tambah preset chips (Hari Ini, Minggu Ini, Bulan Ini, Custom), filter kategori, payment, status, tombol export (xlsx/csv/pdf/print).

21. `sidebar-link.blade.php` — props: route, icon, show=true. Wrapper untuk menu item sidebar dengan auto active-state dari `request()->routeIs($route.'*')`.

22. `header.blade.php` — sudah dibuat di Step 04, pastikan struktur lengkap: hamburger, user dropdown (avatar, profile link, logout).

Contoh implementasi `form-input.blade.php`:
```blade
@props([
    'name', 'label' => null, 'type' => 'text', 'value' => null,
    'placeholder' => null, 'required' => false, 'readonly' => false,
    'help' => null, 'icon' => null,
])
<div class="form-group">
    @if($label)
        <label for="{{ $name }}" class="form-label">
            {{ $label }} @if($required)<span class="text-danger">*</span>@endif
        </label>
    @endif
    <div class="@if($icon) input-group @endif">
        @if($icon)
            <span class="input-group-text"><i class="fas fa-{{ $icon }}"></i></span>
        @endif
        <input type="{{ $type }}"
               id="{{ $name }}"
               name="{{ $name }}"
               value="{{ old($name, $value) }}"
               placeholder="{{ $placeholder }}"
               @if($required) required @endif
               @if($readonly) readonly @endif
               {{ $attributes->merge(['class' => 'form-control'.($errors->has($name) ? ' is-invalid' : '')]) }}>
        @error($name)<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    @if($help) <small class="form-text text-muted">{{ $help }}</small> @endif
</div>
```

Contoh `stat-card.blade.php`:
```blade
@props(['label', 'value', 'icon', 'color' => 'primary', 'delta' => null, 'deltaLabel' => null, 'href' => null])
@php $wrap = $href ? 'a' : 'div'; @endphp
<{{ $wrap }} @if($href) href="{{ $href }}" @endif class="card-clean d-block text-decoration-none">
    <div class="d-flex">
        <div class="me-3 rounded p-3 bg-{{ $color }}-100 text-{{ $color }}-600">
            <i class="fas fa-{{ $icon }} fa-2x"></i>
        </div>
        <div>
            <div class="text-muted text-uppercase small fw-semibold">{{ $label }}</div>
            <div class="h3 fw-bold mb-0">{{ $value }}</div>
            @if($delta !== null)
                <div class="small {{ $delta >= 0 ? 'text-success' : 'text-danger' }}">
                    <i class="fas fa-arrow-{{ $delta >= 0 ? 'up' : 'down' }}"></i>
                    {{ abs($delta) }}% {{ $deltaLabel ?? __('vs kemarin') }}
                </div>
            @endif
        </div>
    </div>
</{{ $wrap }}>
```

Untuk warna semantic (`bg-{color}-100`), tambah CSS variants di `public/css/app.css`:
```css
.bg-primary-100{background:#DBEAFE}.text-primary-600{color:#2563EB}
.bg-success-100{background:#D1FAE5}.text-success-600{color:#059669}
.bg-warning-100{background:#FEF3C7}.text-warning-600{color:#D97706}
.bg-danger-100{background:#FEE2E2}.text-danger-600{color:#DC2626}
.bg-info-100{background:#E0F2FE}.text-info-600{color:#0284C7}
```

Lihat `.claude/rules/01-design-system.md` untuk semua warna & spacing. Lihat `.claude/rules/02-layout-sidebar.md` untuk komponen breakdown.

Setelah semua selesai, buat 1 halaman test `resources/views/pages/_component-showcase.blade.php` yang demo SEMUA komponen — supaya bisa visual-test sekaligus. Tambah route `/showcase` (auth only) untuk akses.

Tampilkan list file di `resources/views/components/` setelah selesai.
````

## Hasil yang Diharapkan

```
resources/views/components/
├── auth-header.blade.php
├── breadcrumb.blade.php
├── button.blade.php
├── card.blade.php
├── empty-state.blade.php
├── footer.blade.php           ← dari Step 04
├── form-checkbox.blade.php
├── form-input.blade.php
├── form-select.blade.php
├── form-textarea.blade.php
├── form-toggle.blade.php
├── header.blade.php           ← dari Step 04
├── icon.blade.php
├── loading-skeleton.blade.php
├── modal.blade.php
├── order-status-badge.blade.php
├── page-header.blade.php
├── reports-filter-bar.blade.php
├── reports-filter.blade.php
├── role-badge.blade.php
├── sidebar.blade.php          ← dari Step 04
├── sidebar-link.blade.php
├── sort-link.blade.php
└── stat-card.blade.php

resources/views/pages/_component-showcase.blade.php  ← demo semua komponen
```

## Cara Test

```bash
php artisan serve
# Login → buka http://localhost:8000/showcase
# Verifikasi tampil:
# - 6 variant button
# - Card dengan title + action
# - Form input (text, email, password, dengan error placeholder)
# - Form select, textarea, toggle, checkbox
# - Modal (klik tombol → modal muncul)
# - Stat card 4 warna dengan delta
# - Badge: status order, role
# - Empty state
# - Loading skeleton
# - Sort link
```

## Penjelasan untuk Murid

Talking points:

1. **"Kenapa `@props([])`?"** — Mendeklarasi props yang diterima komponen + default value. Property yang tidak ada di props otomatis di-spread ke `$attributes` (forward ke elemen utama). Standar Laravel Blade 8+.

2. **"`old($name, $value)` di form input?"** — Jika validation gagal & redirect back, Laravel simpan input lama di session. `old()` ambil dari session, fallback ke `$value` (untuk edit form).

3. **"Komponen vs Partial (`@include`)?"**
   - Partial: simple include, terima variabel via parent context.
   - Component: terenkapsulasi, ada props eksplisit, slot, attribute forwarding. Lebih maintainable.

4. **"Kenapa `<x-form-input>` bukan `@input`?"** — Konvensi Laravel: anonymous Blade components pakai `<x-tag>` syntax. Lebih mirip HTML. Easy to refactor.

5. **"`$attributes->merge(['class' => '...'])`?"** — Kalau user pakai `<x-form-input class="custom" />`, attribut `class` digabung, bukan override. Pattern penting untuk komponen yang fleksibel.

6. **"Slot dengan nama?"** — `<x-card>` punya slot `$actions`. Di parent: `<x-slot:actions>...</x-slot>`. Berguna untuk komponen yang butuh multi-area konten.

7. **"Component-showcase page untuk apa?"** — Visual regression test. Saat redesign warna / komponen, cek showcase dulu untuk pastikan tidak ada yang break.

Pertanyaan reflektif:
- "Kalau saya butuh komponen `<x-data-table>` generik, prop apa saja yang harus terima?" (→ rows collection, columns config, action slot, pagination)
- "Apa beda Volt / Livewire component dengan Blade component?" (→ Volt/Livewire stateful + reactive, Blade component stateless + presentational)

---

**Next: [06-helpers-i18n.md](06-helpers-i18n.md)**
