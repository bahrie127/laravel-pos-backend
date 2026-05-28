<div class="navbar-bg"></div>
<nav class="navbar navbar-expand-lg main-navbar">
    <form class="form-inline mr-auto">
        <ul class="navbar-nav mr-3">
            <li>
                <a href="#" data-toggle="sidebar" class="nav-link nav-link-lg" aria-label="Toggle sidebar">
                    <i class="fas fa-bars"></i>
                </a>
            </li>
        </ul>
    </form>

    <ul class="navbar-nav navbar-right">
        {{-- Shift aktif widget — hanya tampil kalau user yang login punya open session --}}
        @php
            $activeShift = \App\Models\CashSession::currentFor(auth()->id());
        @endphp
        @if ($activeShift)
            <li class="d-none d-md-block">
                <a href="{{ route('cash-session.show', $activeShift->id) }}" class="nav-link nav-link-lg"
                   title="Shift aktif — klik untuk detail"
                   style="display:inline-flex;align-items:center;gap:8px;color:#047857;background:#D1FAE5;border-radius:8px;padding:6px 12px;margin:8px 4px;font-size:13px;font-weight:600;">
                    <span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#10B981;animation:pulse 2s infinite;"></span>
                    <i class="fas fa-door-open"></i>
                    Shift {{ $activeShift->shift_label }}
                    <span class="text-muted" style="font-weight:400;font-size:11px;">
                        · {{ $activeShift->opened_at->diffForHumans(now(), ['short' => true, 'parts' => 1, 'syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE]) }}
                    </span>
                </a>
            </li>
        @endif

        {{-- Dark mode toggle --}}
        <li>
            <button type="button" id="theme-toggle" class="nav-link nav-link-lg" aria-label="Toggle theme"
                    style="background:none;border:0;cursor:pointer;">
                <i class="fas fa-moon" id="theme-toggle-icon"></i>
            </button>
        </li>

        {{-- User dropdown --}}
        <li class="dropdown">
            <a href="#" data-toggle="dropdown"
               class="nav-link dropdown-toggle nav-link-lg nav-link-user d-flex align-items-center">
                <img alt="{{ auth()->user()->name }}"
                     src="{{ auth()->user()->avatar_url }}"
                     class="rounded-circle mr-1"
                     style="width:32px;height:32px;object-fit:cover;">
                <div class="d-sm-none d-lg-inline-block">{{ auth()->user()->name }}</div>
            </a>
            <div class="dropdown-menu dropdown-menu-right">
                <div class="dropdown-title">
                    {{ auth()->user()->email }}
                </div>

                <a href="{{ route('profile.show') }}" class="dropdown-item has-icon">
                    <i class="far fa-user"></i> Profil Saya
                </a>

                @if ($activeShift)
                    <a href="{{ route('cash-session.show', $activeShift->id) }}" class="dropdown-item has-icon text-success">
                        <i class="fas fa-door-open"></i> Shift Aktif (#{{ $activeShift->id }})
                    </a>
                @else
                    <a href="{{ route('cash-session.index') }}" class="dropdown-item has-icon">
                        <i class="fas fa-cash-register"></i> Buka Shift
                    </a>
                @endif

                @can('view-reports')
                    <a href="{{ route('reports.index') }}" class="dropdown-item has-icon">
                        <i class="fas fa-chart-line"></i> Laporan
                    </a>
                @endcan

                <div class="dropdown-divider"></div>

                <a href="#" class="dropdown-item has-icon text-danger"
                   onclick="event.preventDefault(); document.getElementById('logout-form').submit()">
                    <i class="fas fa-sign-out-alt"></i> Keluar
                </a>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                    @csrf
                </form>
            </div>
        </li>
    </ul>
</nav>
