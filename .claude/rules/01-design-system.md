# Design System

Pedoman visual untuk seluruh halaman web admin POS. Tujuan: konsisten, mudah dibaca, layak production.

## Palette

Primary brand: **biru cerah** (vibe modern POS, mirip Square/Stripe).

| Token | HEX | Pakai untuk |
|---|---|---|
| `primary-50` | `#EFF6FF` | Background card highlight |
| `primary-100` | `#DBEAFE` | Hover state subtle |
| `primary-500` | `#3B82F6` | Tombol utama, link, focus ring |
| `primary-600` | `#2563EB` | Tombol hover |
| `primary-700` | `#1D4ED8` | Active state |
| `success-500` | `#10B981` | Status sukses, total revenue positive |
| `warning-500` | `#F59E0B` | Low stock badge, pending order |
| `danger-500` | `#EF4444` | Delete, error, out-of-stock |
| `gray-50` | `#F9FAFB` | Background app |
| `gray-100` | `#F3F4F6` | Card secondary |
| `gray-500` | `#6B7280` | Text muted |
| `gray-900` | `#111827` | Heading text |

Dark mode (Phase 3):
- Background `#0F172A`
- Card `#1E293B`
- Text primary `#F1F5F9`
- Text muted `#94A3B8`

## Typography

- **Font**: `Inter` (Google Fonts) sebagai default. Fallback: `system-ui, -apple-system, sans-serif`.
- **Scale**:
  - `text-xs`: 12px → label tabel, badge
  - `text-sm`: 14px → body table, helper text
  - `text-base`: 16px → body default
  - `text-lg`: 18px → card heading
  - `text-xl`: 20px → section title
  - `text-2xl`: 24px → page H1
  - `text-3xl`: 30px → stat card number

## Spacing & Radius

- Spacing scale: 4, 8, 12, 16, 24, 32, 48
- Border radius:
  - Button: `8px`
  - Card: `12px`
  - Input: `8px`
  - Badge: `999px` (pill)
- Shadow:
  - `shadow-sm`: `0 1px 2px rgba(0,0,0,0.05)` → card default
  - `shadow-md`: `0 4px 6px -1px rgba(0,0,0,0.1)` → card hover
  - `shadow-lg`: `0 10px 15px -3px rgba(0,0,0,0.1)` → modal, dropdown

## Components Spec

### Button
- Default: `bg-primary-500 text-white px-4 py-2 rounded-lg font-medium`
- Hover: `bg-primary-600`
- Sizes: `sm` (px-3 py-1.5 text-sm), `md` (default), `lg` (px-6 py-3 text-lg)
- Variants: `primary`, `secondary` (outline), `success`, `warning`, `danger`, `ghost`
- Loading state: spinner kecil di kiri + disabled

### Input
- Border: `border border-gray-300 rounded-lg px-3 py-2`
- Focus: `ring-2 ring-primary-500 border-primary-500`
- Error: `border-danger-500 ring-danger-500`
- Label di atas, error message di bawah dengan icon

### Card
- `bg-white rounded-xl shadow-sm border border-gray-100 p-6`
- Header: `flex justify-between items-center mb-4`
- Body padding internal `24px`

### Badge
- Pill rounded
- Color by status:
  - `success` → completed, paid
  - `warning` → pending, low-stock
  - `danger` → cancelled, out-of-stock
  - `gray` → draft

### Stat Card (dashboard)
```
[ICON]   Label kecil (gray-500 text-sm uppercase tracking-wide)
         Big number (text-3xl font-bold gray-900)
         ↑ 12% vs yesterday (text-xs success-500)
```

### Data Table
- Header row: `bg-gray-50 text-xs uppercase text-gray-500 font-medium`
- Row: hover `bg-gray-50`
- Action column: icon button (edit, view, delete) dengan tooltip
- Pagination: bottom-right
- Empty state: SVG illustration + "No data" message + CTA "Add new"

### Toast
- Position: top-right
- Auto dismiss 4s
- Variants: success (green), error (red), info (blue), warning (orange)

### Modal Confirm
- Centered, max-w-md
- Icon warning, title, description, two buttons (Cancel ghost, Confirm danger)

## Iconography

- **Library**: Heroicons (outline 24px) atau Phosphor. Hindari Font Awesome 6 kalau bisa (lebih ringan).
- **Konsistensi**: satu set icon untuk seluruh app, jangan campur.
- **Sidebar icons** (rekomendasi):
  - Dashboard → `chart-bar`
  - Users → `users`
  - Categories → `tag`
  - Products → `cube`
  - Orders → `receipt`
  - Reports → `document-chart-bar`
  - Settings → `cog-6-tooth`
  - Profile → `user-circle`
  - Logout → `arrow-right-on-rectangle`

## Locale & Format

- Tanggal: `dd MMM yyyy HH:mm` (contoh: `24 Mei 2026 14:30`)
- Currency: `Rp 1.250.000` (locale `id_ID`, tanpa decimal)
- Pluralization: pakai `__('messages.x_orders', ['count' => $n])`

## Implementation notes

- Jika tetap di Bootstrap, override Stisla `style.css` dengan custom `app.css` yang load **setelah** Stisla supaya cascade menang.
- Jika migrasi ke Tailwind, hapus seluruh `public/library/bootstrap-*` dan `stisla.js`. Refactor layout pakai Tailwind utility.
- Untuk Phase 1, **cukup tambah CSS override** (tidak perlu rip & replace).
