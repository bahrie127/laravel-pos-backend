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
            <li class="nav-item {{ request()->routeIs('promo.*') ? 'active' : '' }}">
                <a href="{{ route('promo.index') }}" class="nav-link">
                    <i class="fas fa-percent"></i><span>Promo</span>
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
            <li class="nav-item {{ request()->routeIs('cash-session.*') ? 'active' : '' }}">
                <a href="{{ route('cash-session.index') }}" class="nav-link">
                    <i class="fas fa-cash-register"></i><span>Cash Sessions</span>
                </a>
            </li>

            @can('view-reports')
                <li class="menu-header">Laporan</li>
                <li class="nav-item {{ request()->routeIs('reports.index') ? 'active' : '' }}">
                    <a href="{{ route('reports.index') }}" class="nav-link">
                        <i class="fas fa-th-large"></i><span>Semua Laporan</span>
                    </a>
                </li>
                <li class="nav-item {{ request()->routeIs('reports.sales-analytics') ? 'active' : '' }}">
                    <a href="{{ route('reports.sales-analytics') }}" class="nav-link">
                        <i class="fas fa-chart-line"></i><span>Sales Analytics</span>
                    </a>
                </li>
                <li class="nav-item {{ request()->routeIs('reports.summary') ? 'active' : '' }}">
                    <a href="{{ route('reports.summary') }}" class="nav-link">
                        <i class="fas fa-chart-bar"></i><span>Ringkasan</span>
                    </a>
                </li>
                <li class="nav-item {{ request()->routeIs('reports.product-sales') ? 'active' : '' }}">
                    <a href="{{ route('reports.product-sales') }}" class="nav-link">
                        <i class="fas fa-chart-pie"></i><span>Per Produk</span>
                    </a>
                </li>
                <li class="nav-item {{ request()->routeIs('reports.inventory') ? 'active' : '' }}">
                    <a href="{{ route('reports.inventory') }}" class="nav-link">
                        <i class="fas fa-warehouse"></i><span>Stok</span>
                    </a>
                </li>
                <li class="nav-item {{ request()->routeIs('reports.close-cashier') ? 'active' : '' }}">
                    <a href="{{ route('reports.close-cashier') }}" class="nav-link">
                        <i class="fas fa-money-check-alt"></i><span>Tutup Kasir</span>
                    </a>
                </li>
                <li class="nav-item {{ request()->routeIs('reports.promo-usage') ? 'active' : '' }}">
                    <a href="{{ route('reports.promo-usage') }}" class="nav-link">
                        <i class="fas fa-tags"></i><span>Pemakaian Promo</span>
                    </a>
                </li>
            @endcan
        </ul>

    </aside>
</div>
