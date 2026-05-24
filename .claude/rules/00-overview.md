# Enhancement Plan — Overview

Plan redesign + feature enhancement untuk Laravel POS Backend (FIC11 Jilid 2) supaya layak production-grade.

## Current State (audit ringkas)

| Area | Kondisi |
|---|---|
| UI template | **Stisla** (Bootstrap 4, jQuery 3.x, heavy vendor bundle di `public/library/`) |
| Dashboard | Penuh **dummy data** (PlayStation 9, "Code with Bahri", weather widget, fake referral) — tidak ada satupun metric POS real |
| Sidebar | Brand `POS BAHRI` hardcoded, semua icon = `fa-fire`, tidak ada active-state highlight |
| List pages (Products / Categories / Users / Orders) | Tabel HTML manual + search by-name only. Tanpa: sort header, pagination styling, server-side filter lain, empty-state, bulk actions |
| Forms (create/edit) | Validation feedback ada tapi kosong dari fitur: image preview, drag-drop upload, currency mask, character counter, autosave |
| Order detail | Layout flat, tanpa header summary card, tanpa kasir info, tanpa print/export, tanpa status badge |
| Auth login | Card sederhana, tanpa logo, no "remember me", no forgot password link aktif |
| Reports | Hanya endpoint API (`summary`, `product-sales`, `close-cashier`) — **belum ada page web** untuk lihat report |
| User profile | **Tidak ada** |
| Settings | **Tidak ada** |
| Error pages (404/500) | Default Laravel |
| Aksesibilitas | Tidak ada ARIA labels, kontras warna belum diaudit |
| Mobile responsive | Stisla bawaan responsive, tapi tabel scroll horisontal di mobile tanpa card-view |
| Notifications | `confirm-delete` pakai native JS confirm, tidak ada toast modern |
| Locale | UI campur Inggris-Indonesia, tanpa i18n |

## Design Principles (redesign target)

1. **Modern admin look** — pilih satu dari dua arah:
   - **A. Refresh Stisla** (low risk): upgrade ke versi Bootstrap 5 fork + redesign warna, spacing, dan icon set
   - **B. Migrasi ke Tabler/AdminLTE 4/CoreUI Bootstrap 5** (medium risk): UI lebih modern, accessibility lebih baik
   
   **Rekomendasi: A** dulu untuk Phase 1 (lebih cepat shipping), evaluasi B di Phase 3.

2. **Data-first**: hapus semua dummy/template content. Setiap card/widget harus terhubung ke data real dari DB.

3. **Konsisten i18n**: tetapkan satu bahasa default (**Bahasa Indonesia**) di semua label UI, simpan string di `lang/id/` supaya bisa swap.

4. **Component-driven**: ekstrak Blade components (`<x-stat-card>`, `<x-data-table>`, `<x-form-input>`, `<x-page-header>`) supaya tiap page tinggal compose.

5. **Production polish**: empty state, loading state, error state, toast notification (SweetAlert2 atau Notyf), confirmation modal (bukan native confirm).

6. **Aksesibilitas minimum**: label setiap form input, alt text di gambar, focus ring di tombol, kontras AA.

## Tech Stack Additions (proposal)

| Library | Tujuan |
|---|---|
| **Vite + TailwindCSS** (opsional, Phase 3) | Replace Bootstrap untuk konsistensi & lebih modern. Atau pertahankan Bootstrap untuk tidak bongkar semua. |
| **Alpine.js** | Interaktivitas ringan tanpa jQuery (dropdown, modal, tab) |
| **SweetAlert2** | Confirm dialog & toast |
| **Chart.js** (sudah ada) | Real chart untuk dashboard (sales trend, kategori share) |
| **DataTables** (sudah ada) | Sort, filter, export ke CSV/Excel di list page |
| **Spatie laravel-permission** | Role & permission (Admin / Kasir / Owner) |
| **Spatie laravel-medialibrary** | Image upload, conversion, & multi-image support |
| **Laravel Excel (maatwebsite)** | Export report ke Excel |
| **DomPDF / Browsershot** | Cetak struk PDF dari order detail |

## Roadmap (4 Phase)

### Phase 1 — Quick wins (1–2 hari)
- Hapus semua dummy data di dashboard, ganti dengan 4 stat card real (Total Sales Today, Total Orders Today, Total Products, Total Active Users)
- Fix sidebar: icon per menu yang relevan, active-state highlight, brand dari config
- Redesign login: tambah logo, "Remember me", link forgot-password
- Confirm dialog pakai SweetAlert2 (bukan native confirm)
- Empty state di setiap list page

### Phase 2 — Feature completion (3–5 hari)
- Dashboard: chart sales 7/30/90 hari, top-5 produk, recent orders, today's revenue breakdown by payment method
- Products: kolom stock, low-stock badge, filter by category, sort by harga/stock, bulk delete, image preview & lightbox di list
- Orders: filter date range, filter payment method, filter kasir, status badge, detail dengan order items + cetak struk PDF, export Excel
- Categories: tampilkan jumlah produk per kategori, color/icon picker
- Users: kolom role (admin/kasir), filter role, ubah password sendiri, avatar upload
- Profile page (`/profile`) untuk user yang login: edit nama/email/avatar/password
- Reports page web: summary, product-sales, close-cashier dengan chart dan export Excel

### Phase 3 — UI polish & UX (3–4 hari)
- Pilih: stay Bootstrap atau pindah ke Tailwind+Alpine
- Komponen Blade reusable (`x-page-header`, `x-stat-card`, `x-data-table`, `x-form-*`)
- Dark mode toggle
- Toast notification (Notyf/SweetAlert2)
- Mobile: card-view untuk tabel di breakpoint kecil
- Skeleton loading di halaman yang fetch data heavy
- 404/500 page custom

### Phase 4 — Production hardening (2–3 hari)
- Role & permission (Spatie) — Admin / Owner / Kasir
- Audit log (Spatie activitylog) untuk semua CRUD
- Backup database scheduler
- Rate limit per user di API
- API response standardization (resource class + consistent error shape)
- API documentation (Scribe atau L5-Swagger)
- Testing: feature tests untuk semua controller (saat ini hanya example test)
- CI: GitHub Actions untuk run `php artisan test` + `pint` per PR

## File Index (detail per area)

- `01-design-system.md` — palette, typography, component spec
- `02-layout-sidebar.md` — layout + sidebar redesign detail
- `03-dashboard.md` — dashboard redesign + widgets real
- `04-products.md` — products page enhancements
- `05-categories.md` — categories page enhancements
- `06-orders.md` — orders & order detail page enhancements
- `07-users.md` — users page + profile page baru
- `08-auth.md` — login redesign + forgot-password aktif
- `09-reports.md` — reports section baru (web)
- `10-api-improvements.md` — API standardization & docs
- `11-permissions-roles.md` — role/permission system
- `12-implementation-checklist.md` — checklist eksekusi per phase

## Definition of "Production-ready"

- [ ] Tidak ada hardcoded text Inggris/Indonesia campur — pakai `__()`
- [ ] Tidak ada dummy data di view
- [ ] Setiap list page punya: search, sort header, filter relevant, pagination, empty state
- [ ] Setiap form punya: validasi server + client-side, error message yang jelas, success toast
- [ ] Setiap action destructive konfirmasi via modal (bukan native confirm)
- [ ] Tabel responsive di mobile (card-view atau horizontal scroll dengan sticky column)
- [ ] Login & logout audit-logged
- [ ] Lulus `php artisan test` ≥ 80% coverage controller
- [ ] PHP CS via `pint` clean
- [ ] README setup steps tested end-to-end
