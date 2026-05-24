<div class="main-sidebar sidebar-style-2">
    <aside id="sidebar-wrapper">
        <div class="sidebar-brand">
            <a href="{{ route('home') }}">
                <img src="{{ asset('img/logo.svg') }}" alt="{{ config('app.name') }}">
                <span>{{ config('app.name') }}</span>
            </a>
        </div>
        <div class="sidebar-brand sidebar-brand-sm">
            <a href="{{ route('home') }}">
                <img src="{{ asset('img/logo.svg') }}" alt="{{ config('app.name') }}" style="width:32px;height:32px;">
            </a>
        </div>

        <ul class="sidebar-menu">
            <li class="menu-header">Overview</li>
            <li class="nav-item {{ request()->routeIs('home') ? 'active' : '' }}">
                <a href="{{ route('home') }}" class="nav-link">
                    <i class="fas fa-chart-line"></i><span>Dashboard</span>
                </a>
            </li>

            <li class="menu-header">Master Data</li>
            <li class="nav-item {{ request()->routeIs('categories.*') ? 'active' : '' }}">
                <a href="{{ route('categories.index') }}" class="nav-link">
                    <i class="fas fa-tags"></i><span>Kategori</span>
                </a>
            </li>
            <li class="nav-item {{ request()->routeIs('product.*') ? 'active' : '' }}">
                <a href="{{ route('product.index') }}" class="nav-link">
                    <i class="fas fa-box-open"></i><span>Produk</span>
                </a>
            </li>
            @can('viewAny', App\Models\User::class)
                <li class="nav-item {{ request()->routeIs('user.*') ? 'active' : '' }}">
                    <a href="{{ route('user.index') }}" class="nav-link">
                        <i class="fas fa-users"></i><span>Pengguna</span>
                    </a>
                </li>
            @endcan

            <li class="menu-header">Transaksi</li>
            <li class="nav-item {{ request()->routeIs('order.*') ? 'active' : '' }}">
                <a href="{{ route('order.index') }}" class="nav-link">
                    <i class="fas fa-receipt"></i><span>Pesanan</span>
                </a>
            </li>
        </ul>

        @auth
            <div class="sidebar-user-card">
                <div class="d-flex align-items-center mb-2">
                    <span class="avatar mr-2">{{ initials(auth()->user()->name) }}</span>
                    <div class="flex-grow-1" style="min-width:0;">
                        <div class="font-weight-bold text-truncate" style="font-size:13px;">{{ auth()->user()->name }}</div>
                        <div class="text-muted text-truncate" style="font-size:11px;">{{ auth()->user()->email }}</div>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="sidebar-logout-btn">
                        <i class="fas fa-sign-out-alt mr-1"></i> Logout
                    </button>
                </form>
            </div>
        @endauth
    </aside>
</div>
