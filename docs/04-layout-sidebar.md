# Step 04 — Layout, Sidebar, Header (Stisla Theme)

## Tujuan

Mempunyai **skeleton UI admin** dengan:
- `layouts/app.blade.php` (untuk halaman ber-sidebar)
- `layouts/auth.blade.php` (untuk login/forgot-password)
- Komponen `sidebar`, `header`, `footer`, `alert`
- Theme Bootstrap 5 + Stisla (sudah responsive)
- Brand "POS FIC11" + favicon
- Active state highlight di sidebar
- Logo + login redesign (split-screen)

## Prasyarat

- Step 03 selesai (auth jalan)

## Konteks

Stisla adalah free admin template Bootstrap. Kita pakai sebagai foundation, lalu polish dengan CSS layer di atas (jangan ubah core Stisla). Brand name dari `.env`, sidebar pakai routing dinamis.

## Prompt untuk AI

````
Project Laravel POS sudah punya auth. Sekarang buat skeleton UI admin dengan Bootstrap 5 + Stisla theme.

A. ASSET STATIS
1. Download Stisla theme dari https://github.com/stisla/stisla atau salin asset minimal:
   - public/library/jquery/jquery-3.x.min.js
   - public/library/popper.js/popper.min.js
   - public/library/bootstrap/js/bootstrap.bundle.min.js
   - public/library/bootstrap/css/bootstrap.min.css
   - public/library/chart.js/Chart.min.js
   - public/library/sweetalert2/sweetalert2.all.min.js
   - public/library/fontawesome/css/all.min.css + webfonts
   - public/css/style.css (Stisla theme)
   - public/css/components.css
   - public/js/stisla.js
   
   (Sebagai alternatif singkat, gunakan CDN. Untuk production lebih baik self-host.)

2. Buat asset custom:
   - public/css/app.css   (override + dark mode hooks)
   - public/css/custom.css  (override khusus brand)
   - public/js/scripts.js   (helper umum: image preview, currency mask, password toggle)
   - public/js/custom.js    (delete confirm SweetAlert2, dll)
   - public/js/app.js       (theme toggle, init)
   - public/favicon.png     (favicon brand)
   - public/img/logo.svg    (logo brand)

B. LAYOUT MASTER
3. Buat `resources/views/layouts/app.blade.php`:
   ```blade
   <!DOCTYPE html>
   <html lang="{{ str_replace('_','-', app()->getLocale()) }}">
   <head>
       <meta charset="UTF-8">
       <meta name="viewport" content="width=device-width, initial-scale=1">
       <meta name="csrf-token" content="{{ csrf_token() }}">
       <title>@yield('title', __('Dashboard')) — {{ config('app.name') }}</title>
       <link rel="icon" href="{{ asset('favicon.png') }}">
       <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
       <link rel="stylesheet" href="{{ asset('library/bootstrap/css/bootstrap.min.css') }}">
       <link rel="stylesheet" href="{{ asset('library/fontawesome/css/all.min.css') }}">
       <link rel="stylesheet" href="{{ asset('css/style.css') }}">
       <link rel="stylesheet" href="{{ asset('css/components.css') }}">
       <link rel="stylesheet" href="{{ asset('css/app.css') }}">
       @stack('style')
   </head>
   <body>
   <div id="app">
       <div class="main-wrapper main-wrapper-1">
           @include('components.header')
           @include('components.sidebar')
           <div class="main-content">
               @include('layouts.alert')
               @yield('main')
           </div>
           @include('components.footer')
       </div>
   </div>
   <script src="{{ asset('library/jquery/jquery-3.7.1.min.js') }}"></script>
   <script src="{{ asset('library/popper.js/popper.min.js') }}"></script>
   <script src="{{ asset('library/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
   <script src="{{ asset('library/sweetalert2/sweetalert2.all.min.js') }}"></script>
   <script src="{{ asset('js/stisla.js') }}"></script>
   <script src="{{ asset('js/scripts.js') }}"></script>
   <script src="{{ asset('js/custom.js') }}"></script>
   <script src="{{ asset('js/app.js') }}"></script>
   @stack('scripts')
   </body>
   </html>
   ```

4. Buat `resources/views/layouts/auth.blade.php` (minimal, untuk login page):
   ```blade
   <!DOCTYPE html>
   <html lang="{{ str_replace('_','-', app()->getLocale()) }}">
   <head>
       <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
       <title>@yield('title') — {{ config('app.name') }}</title>
       <link rel="icon" href="{{ asset('favicon.png') }}">
       <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
       <link rel="stylesheet" href="{{ asset('library/bootstrap/css/bootstrap.min.css') }}">
       <link rel="stylesheet" href="{{ asset('library/fontawesome/css/all.min.css') }}">
       <link rel="stylesheet" href="{{ asset('css/style.css') }}">
       <link rel="stylesheet" href="{{ asset('css/components.css') }}">
       <link rel="stylesheet" href="{{ asset('css/app.css') }}">
   </head>
   <body>
       @include('layouts.alert')
       @yield('main')
       <script src="{{ asset('library/jquery/jquery-3.7.1.min.js') }}"></script>
       <script src="{{ asset('library/sweetalert2/sweetalert2.all.min.js') }}"></script>
   </body>
   </html>
   ```

5. Buat `resources/views/layouts/alert.blade.php`:
   ```blade
   @if (session('success'))
       <script>setTimeout(()=>window.AppToast?.('success', @json(session('success'))), 50);</script>
   @endif
   @if (session('error'))
       <script>setTimeout(()=>window.AppToast?.('error', @json(session('error'))), 50);</script>
   @endif
   @if (session('status'))
       <script>setTimeout(()=>window.AppToast?.('info', @json(session('status'))), 50);</script>
   @endif
   ```

C. KOMPONEN INTI
6. Buat `resources/views/components/header.blade.php`:
   - Navbar top dengan: hamburger toggle (mobile), title, theme toggle, user dropdown (avatar, nama, link Profile, Logout)
   - Pakai class Stisla: `.navbar`, `.navbar-bg`, `.nav-collapse`, `.nav-link`

7. Buat `resources/views/components/sidebar.blade.php`:
   ```blade
   <div class="main-sidebar sidebar-style-2">
       <aside id="sidebar-wrapper">
           <div class="sidebar-brand">
               <a href="{{ route('home') }}">
                   <img src="{{ asset('img/logo.svg') }}" class="header-logo">
                   <span class="logo-name">{{ config('app.name') }}</span>
               </a>
           </div>
           <ul class="sidebar-menu">
               <li class="menu-header">{{ __('Overview') }}</li>
               <li class="{{ request()->routeIs('home') ? 'active' : '' }}">
                   <a href="{{ route('home') }}" class="nav-link">
                       <i class="fas fa-chart-line"></i><span>{{ __('Dashboard') }}</span>
                   </a>
               </li>

               <li class="menu-header">{{ __('Master Data') }}</li>
               <li class="{{ request()->routeIs('categories.*') ? 'active' : '' }}">
                   <a href="{{ route('categories.index') }}" class="nav-link"><i class="fas fa-tags"></i><span>{{ __('Kategori') }}</span></a>
               </li>
               <li class="{{ request()->routeIs('product.*') ? 'active' : '' }}">
                   <a href="{{ route('product.index') }}" class="nav-link"><i class="fas fa-box-open"></i><span>{{ __('Produk') }}</span></a>
               </li>
               <li class="{{ request()->routeIs('promo.*') ? 'active' : '' }}">
                   <a href="{{ route('promo.index') }}" class="nav-link"><i class="fas fa-percent"></i><span>{{ __('Promo') }}</span></a>
               </li>
               @can('viewAny', App\Models\User::class)
               <li class="{{ request()->routeIs('user.*') ? 'active' : '' }}">
                   <a href="{{ route('user.index') }}" class="nav-link"><i class="fas fa-users"></i><span>{{ __('Pengguna') }}</span></a>
               </li>
               @endcan

               <li class="menu-header">{{ __('Transaksi') }}</li>
               <li class="{{ request()->routeIs('order.*') ? 'active' : '' }}">
                   <a href="{{ route('order.index') }}" class="nav-link"><i class="fas fa-receipt"></i><span>{{ __('Pesanan') }}</span></a>
               </li>
               <li class="{{ request()->routeIs('cash-session.*') ? 'active' : '' }}">
                   <a href="{{ route('cash-session.index') }}" class="nav-link"><i class="fas fa-cash-register"></i><span>{{ __('Cash Session') }}</span></a>
               </li>

               @can('view-reports')
               <li class="menu-header">{{ __('Laporan') }}</li>
               <li class="{{ request()->routeIs('reports.index') ? 'active' : '' }}">
                   <a href="{{ route('reports.index') }}" class="nav-link"><i class="fas fa-chart-bar"></i><span>{{ __('Semua Laporan') }}</span></a>
               </li>
               @endcan
           </ul>
       </aside>
   </div>
   ```
   
   Komentar route yang belum ada (Step berikutnya akan generate route-nya). Sementara comment-out blok yang routenya belum ada untuk hindari error.

8. Buat `resources/views/components/footer.blade.php`:
   ```blade
   <footer class="main-footer">
       <div class="footer-left">
           Copyright &copy; {{ date('Y') }} <strong>{{ config('app.name') }}</strong>
       </div>
       <div class="footer-right">v1.0</div>
   </footer>
   ```

D. LOGIN REDESIGN
9. Update `resources/views/pages/auth/login.blade.php` jadi split-screen:
   ```blade
   @extends('layouts.auth')
   @section('title', __('Masuk'))
   @section('main')
   <div class="d-flex" style="min-height:100vh">
       <div class="col-lg-6 d-flex align-items-center justify-content-center p-5 bg-white">
           <div style="max-width:420px;width:100%">
               <div class="text-center mb-4">
                   <img src="{{ asset('img/logo.svg') }}" style="height:48px" class="mb-3">
                   <h2 class="h3 fw-bold">{{ config('app.name') }}</h2>
                   <p class="text-muted">{{ __('Masuk untuk melanjutkan ke panel admin') }}</p>
               </div>

               @if (session('status'))
                   <div class="alert alert-success">{{ session('status') }}</div>
               @endif

               <form method="POST" action="{{ route('login') }}">
                   @csrf
                   <div class="mb-3">
                       <label class="form-label">{{ __('Email') }}</label>
                       <input type="email" name="email" value="{{ old('email') }}"
                              class="form-control @error('email') is-invalid @enderror"
                              autofocus required>
                       @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                   </div>
                   <div class="mb-3">
                       <div class="d-flex justify-content-between">
                           <label class="form-label">{{ __('Password') }}</label>
                           <a href="{{ route('password.request') }}" class="small">{{ __('Lupa password?') }}</a>
                       </div>
                       <div class="input-group">
                           <input type="password" name="password" id="password"
                                  class="form-control @error('password') is-invalid @enderror" required>
                           <button type="button" class="btn btn-outline-secondary" onclick="togglePwd('password')">
                               <i class="fas fa-eye"></i>
                           </button>
                       </div>
                       @error('password') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                   </div>
                   <div class="form-check mb-3">
                       <input class="form-check-input" type="checkbox" name="remember" id="remember">
                       <label class="form-check-label" for="remember">{{ __('Ingat saya') }}</label>
                   </div>
                   <button class="btn btn-primary w-100 btn-lg">{{ __('Masuk') }}</button>
               </form>
           </div>
       </div>
       <div class="col-lg-6 d-none d-lg-flex align-items-center justify-content-center text-white p-5"
            style="background:linear-gradient(135deg,#2563EB,#1D4ED8)">
           <div class="text-center" style="max-width:420px">
               <i class="fas fa-store fa-4x mb-4 opacity-75"></i>
               <h3 class="fw-bold">{{ __('Kelola Bisnis Anda dengan Mudah') }}</h3>
               <p class="opacity-75">{{ __('Dashboard, produk, transaksi, dan laporan POS — semua dalam satu tempat.') }}</p>
           </div>
       </div>
   </div>
   <script>
   function togglePwd(id){const x=document.getElementById(id);x.type=x.type==='password'?'text':'password';}
   </script>
   @endsection
   ```

10. Buat `resources/views/pages/auth/forgot-password.blade.php` dan `reset-password.blade.php` (struktur card minimal sesuai design system di `.claude/rules/08-auth.md`).

E. CSS OVERRIDE
11. Buat `public/css/app.css` minimal — variabel CSS untuk brand color, font Inter, tweak Stisla sidebar:
    ```css
    :root {
        --primary-500: #3B82F6;
        --primary-600: #2563EB;
        --primary-700: #1D4ED8;
    }
    body { font-family: 'Inter', system-ui, -apple-system, sans-serif; }
    .btn-primary { background:var(--primary-500); border-color:var(--primary-500); }
    .btn-primary:hover { background:var(--primary-600); border-color:var(--primary-600); }
    .sidebar-brand .logo-name { color: var(--primary-700); font-weight:700; }
    .main-sidebar .sidebar-menu li.active a { background:#EFF6FF; color:var(--primary-700); border-radius:.5rem; }
    .card-clean { background:#fff; border-radius:12px; border:1px solid #F3F4F6; box-shadow:0 1px 2px rgba(0,0,0,.05); padding:1.25rem; }
    ```

12. Buat `public/js/custom.js` minimal untuk delete confirm:
    ```js
    document.addEventListener('click', function(e){
        const btn = e.target.closest('.confirm-delete');
        if (!btn) return;
        e.preventDefault();
        const url = btn.dataset.action;
        Swal.fire({
            title:'Yakin hapus?', text:'Aksi tidak bisa dibatalkan.',
            icon:'warning', showCancelButton:true,
            confirmButtonColor:'#EF4444', confirmButtonText:'Ya, hapus',
            cancelButtonText:'Batal'
        }).then(r => {
            if (!r.isConfirmed) return;
            const f = document.createElement('form');
            f.method='POST'; f.action=url;
            f.innerHTML = `@csrf @method('DELETE')`;
            document.body.appendChild(f); f.submit();
        });
    });
    window.AppToast = (type, msg) => Swal.fire({
        toast:true, position:'top-end', icon:type, title:msg,
        timer:3500, showConfirmButton:false
    });
    ```

13. Pastikan link dari login form ke `route('password.request')` & `route('home')` ada. Test login sukses → ke dashboard placeholder.

Tampilkan struktur folder `resources/views/` dan `public/css/` setelah selesai.
````

## Hasil yang Diharapkan

```
public/
├── library/{jquery,bootstrap,popper.js,chart.js,sweetalert2,fontawesome}/
├── css/{style.css, components.css, app.css, custom.css}
├── js/{stisla.js, scripts.js, custom.js, app.js}
├── favicon.png
└── img/logo.svg

resources/views/
├── layouts/{app.blade.php, auth.blade.php, alert.blade.php}
├── components/{header.blade.php, sidebar.blade.php, footer.blade.php}
└── pages/auth/{login.blade.php, forgot-password.blade.php, reset-password.blade.php}
```

## Cara Test

```bash
php artisan serve
# 1. http://localhost:8000 → tampil split-screen login
# 2. Login → /home (placeholder dashboard) → sidebar muncul dengan menu (beberapa link error 404 — wajar, route belum dibuat)
# 3. Klik logo → kembali ke /home
# 4. Klik user dropdown → menu Profile + Logout
# 5. Logout → kembali ke login
# 6. Klik "Lupa password?" → /forgot-password tampil
# 7. Resize browser ke 768px → sidebar collapse ke hamburger
# 8. Toggle dark mode (kalau di-implement)
```

## Penjelasan untuk Murid

Talking points:

1. **"Kenapa pakai Stisla, bukan custom from scratch?"** — Hemat waktu. Stisla sudah responsive, punya komponen siap pakai (card, form, alert). Kita fokus polish, bukan reinvent.

2. **"Layer CSS: kenapa `app.css` di-load TERAKHIR?"** — Cascade. CSS terakhir menang. `app.css` override Stisla tanpa ubah file core.

3. **"Active state pakai `request()->routeIs()`?"** — Helper Laravel cek route name pattern. `request()->routeIs('product.*')` match `product.index`, `product.create`, dll. Lebih maintainable daripada cek path string.

4. **"Logo SVG kenapa, bukan PNG?"** — Scalable, ringan, retina-friendly. Kalau brand belum punya logo, pakai placeholder text + icon.

5. **"Stack `@push('scripts')`?"** — Memungkinkan child view inject script ke posisi tertentu di layout tanpa override section. Pakai untuk Chart.js init per page.

6. **"AppToast helper?"** — Pakai SweetAlert2 sebagai toast. Lebih estetik dari Bootstrap toast default. Konsisten dengan confirm dialog.

Pertanyaan reflektif:
- "Kalau Stisla update versi baru, gimana kita upgrade tanpa kehilangan override?" (→ versi-kan asset di folder terpisah, override di `app.css` jangan langsung edit Stisla file)
- "Buat custom dark mode — kolom mana yang harus di-toggle?" (→ tambah `data-theme="dark"` di `<body>`, CSS pakai `body[data-theme=dark] { ... }`)

---

**Next: [05-blade-components.md](05-blade-components.md)**
