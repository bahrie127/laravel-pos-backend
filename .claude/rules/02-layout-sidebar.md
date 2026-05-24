# Layout & Sidebar Redesign

## Current Issues

- `resources/views/components/sidebar.blade.php`:
  - Brand `POS BAHRI` hardcoded, link ke `index.html` (mati)
  - Semua menu icon = `fa-fire` (Users, Categories, Products, Orders semua sama)
  - Tidak ada active-state highlight (current page tidak ter-highlight)
  - Tidak ada section grouping (Master Data vs Transaksi vs Laporan)
  - Tidak ada submenu nested
  - Tidak ada logout button di bottom

- `resources/views/layouts/app.blade.php`:
  - Title suffix hardcoded `CWB` → harus pakai `config('app.name')`
  - Tidak ada favicon
  - Double `</head>` tag (typo bug)

- `resources/views/components/header.blade.php`: belum direview, tapi kemungkinan default Stisla template (search bar yang tidak berfungsi, notif dropdown dengan dummy data).

## Target Design

### Layout (`layouts/app.blade.php`)

```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('Dashboard')) — {{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('favicon.png') }}">
    
    {{-- Stack: Google Fonts Inter, Bootstrap (atau Tailwind), custom app.css --}}
    @stack('style')
</head>
<body class="bg-gray-50">
    <div id="app" class="min-h-screen flex">
        @include('components.sidebar')
        
        <div class="flex-1 flex flex-col min-w-0">
            @include('components.header')
            
            <main class="flex-1 p-6">
                @include('components.breadcrumb')
                @include('layouts.alert')
                @yield('main')
            </main>
            
            @include('components.footer')
        </div>
    </div>
    
    {{-- Global toast container --}}
    <div id="toast-container" class="fixed top-4 right-4 z-50 space-y-2"></div>
    
    @stack('scripts')
</body>
</html>
```

### Sidebar (`components/sidebar.blade.php`)

```blade
<aside class="w-64 bg-white border-r border-gray-200 hidden lg:block">
    {{-- Brand --}}
    <div class="h-16 flex items-center px-6 border-b border-gray-200">
        <img src="{{ asset('img/logo.svg') }}" class="h-8 w-8" alt="">
        <span class="ml-2 font-bold text-lg">{{ config('app.name') }}</span>
    </div>
    
    {{-- Menu grouped --}}
    <nav class="p-4 space-y-6">
        {{-- Group: Overview --}}
        <div>
            <p class="text-xs uppercase text-gray-400 font-semibold mb-2 px-3">{{ __('Overview') }}</p>
            <x-sidebar-link route="home" icon="chart-bar">{{ __('Dashboard') }}</x-sidebar-link>
        </div>
        
        {{-- Group: Master Data --}}
        <div>
            <p class="text-xs uppercase text-gray-400 font-semibold mb-2 px-3">{{ __('Master Data') }}</p>
            <x-sidebar-link route="categories.index" icon="tag">{{ __('Kategori') }}</x-sidebar-link>
            <x-sidebar-link route="product.index" icon="cube">{{ __('Produk') }}</x-sidebar-link>
            <x-sidebar-link route="user.index" icon="users" :show="auth()->user()->isAdmin()">{{ __('Pengguna') }}</x-sidebar-link>
        </div>
        
        {{-- Group: Transaksi --}}
        <div>
            <p class="text-xs uppercase text-gray-400 font-semibold mb-2 px-3">{{ __('Transaksi') }}</p>
            <x-sidebar-link route="order.index" icon="receipt">{{ __('Pesanan') }}</x-sidebar-link>
        </div>
        
        {{-- Group: Laporan --}}
        <div>
            <p class="text-xs uppercase text-gray-400 font-semibold mb-2 px-3">{{ __('Laporan') }}</p>
            <x-sidebar-link route="reports.summary" icon="document-chart-bar">{{ __('Ringkasan') }}</x-sidebar-link>
            <x-sidebar-link route="reports.product-sales" icon="chart-pie">{{ __('Penjualan Produk') }}</x-sidebar-link>
            <x-sidebar-link route="reports.close-cashier" icon="banknotes">{{ __('Tutup Kasir') }}</x-sidebar-link>
        </div>
    </nav>
    
    {{-- Footer (user info + logout) --}}
    <div class="absolute bottom-0 w-64 p-4 border-t border-gray-200 bg-white">
        <div class="flex items-center">
            <img src="{{ auth()->user()->avatar_url }}" class="w-10 h-10 rounded-full">
            <div class="ml-3 flex-1 min-w-0">
                <p class="text-sm font-medium truncate">{{ auth()->user()->name }}</p>
                <p class="text-xs text-gray-500 truncate">{{ auth()->user()->email }}</p>
            </div>
        </div>
        <form method="POST" action="{{ route('logout') }}" class="mt-3">
            @csrf
            <button class="w-full text-left text-sm text-danger-500 hover:bg-danger-50 px-3 py-2 rounded-lg flex items-center">
                <x-icon name="arrow-right-on-rectangle" class="w-4 h-4 mr-2"/>
                {{ __('Logout') }}
            </button>
        </form>
    </div>
</aside>

{{-- Mobile drawer (Phase 2): toggle dengan Alpine.js, slide-in dari kiri --}}
```

### Header (`components/header.blade.php`)

Komponen minimal:
- Hamburger toggle (mobile)
- Page title (auto dari section title)
- Global search (Phase 3: command palette ala Linear)
- Notification dropdown (Phase 4: low stock alerts, new orders)
- User dropdown (Profile, Settings, Logout)

Bukan: search box gigantic, weather widget, full-screen toggle (default Stisla yang tidak relevan).

### Breadcrumb component (baru)

```blade
{{-- components/breadcrumb.blade.php --}}
<nav class="flex mb-4 text-sm">
    @foreach ($crumbs as $i => $crumb)
        @if (!$loop->last)
            <a href="{{ $crumb['url'] }}" class="text-gray-500 hover:text-primary-600">{{ $crumb['label'] }}</a>
            <span class="mx-2 text-gray-300">/</span>
        @else
            <span class="text-gray-900 font-medium">{{ $crumb['label'] }}</span>
        @endif
    @endforeach
</nav>
```

Setiap controller passing `$crumbs` array, atau pakai package `diglactic/laravel-breadcrumbs`.

## Sidebar Link Component

`resources/views/components/sidebar-link.blade.php`:

```blade
@props(['route', 'icon', 'show' => true])

@if ($show)
    @php
        $active = request()->routeIs($route) || request()->routeIs($route . '.*');
    @endphp
    <a href="{{ route($route) }}"
       class="flex items-center px-3 py-2 rounded-lg text-sm font-medium transition
              {{ $active 
                 ? 'bg-primary-50 text-primary-600' 
                 : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900' }}">
        <x-icon name="{{ $icon }}" class="w-5 h-5 mr-3" />
        {{ $slot }}
    </a>
@endif
```

## Action Items

- [ ] Fix double `</head>` di `layouts/app.blade.php`
- [ ] Brand & favicon dari config
- [ ] Title suffix `CWB` → `config('app.name')`
- [ ] Buat `<x-sidebar-link>` component
- [ ] Buat `<x-icon>` component (Heroicons via blade-icons package)
- [ ] Buat `<x-breadcrumb>` component
- [ ] Rebuild sidebar dengan group + icon spesifik + active state + logout footer
- [ ] Header: kurangi widget tidak relevan, tambah user dropdown
- [ ] Mobile drawer dengan Alpine.js

## Dependencies untuk dibuat

```bash
composer require blade-ui-kit/blade-heroicons
# atau
composer require diglactic/laravel-breadcrumbs
```
