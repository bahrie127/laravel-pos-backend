@extends('layouts.app')

@section('title', 'Cash Sessions')

@section('main')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1>Cash Sessions (Shift)</h1>
                <div class="section-header-button">
                    @if ($mySession)
                        <a href="{{ route('cash-session.show', $mySession->id) }}" class="btn btn-success">
                            <i class="fas fa-door-open mr-1"></i> Shift Aktif Saya
                        </a>
                    @else
                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#openShiftModal">
                            <i class="fas fa-door-open mr-1"></i> Buka Shift Saya
                        </button>
                    @endif
                </div>
                <div class="section-header-breadcrumb">
                    <div class="breadcrumb-item active"><a href="{{ route('home') }}">Dashboard</a></div>
                    <div class="breadcrumb-item">Cash Sessions</div>
                </div>
            </div>

            <div class="section-body">
                @include('layouts.alert')

                {{-- Stats --}}
                <div class="row">
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon bg-success-soft"><i class="fas fa-door-open"></i></div>
                            <div class="stat-label">Shift Aktif Sekarang</div>
                            <div class="stat-value">{{ $stats['open_now'] }}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon bg-primary-soft"><i class="fas fa-check-circle"></i></div>
                            <div class="stat-label">Ditutup Hari Ini</div>
                            <div class="stat-value">{{ $stats['closed_today'] }}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon {{ $stats['variance_today_sum'] == 0 ? 'bg-info-soft' : ($stats['variance_today_sum'] < 0 ? 'bg-danger-soft' : 'bg-warning-soft') }}">
                                <i class="fas fa-balance-scale"></i>
                            </div>
                            <div class="stat-label">Variance Hari Ini</div>
                            <div class="stat-value">{{ rupiah($stats['variance_today_sum']) }}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon bg-warning-soft"><i class="fas fa-money-bill-wave"></i></div>
                            <div class="stat-label">Pendapatan Tunai Hari Ini</div>
                            <div class="stat-value">{{ rupiah($stats['cash_revenue_today']) }}</div>
                        </div>
                    </div>
                </div>

                {{-- Filter --}}
                <div class="row">
                    <div class="col-12">
                        <div class="card-clean">
                            <form method="GET" action="{{ route('cash-session.index') }}" class="form-row">
                                <div class="col-md-2 form-group m-0">
                                    <label class="text-muted" style="font-size:12px;">Dari</label>
                                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control form-control-sm">
                                </div>
                                <div class="col-md-2 form-group m-0">
                                    <label class="text-muted" style="font-size:12px;">Sampai</label>
                                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control form-control-sm">
                                </div>
                                <div class="col-md-2 form-group m-0">
                                    <label class="text-muted" style="font-size:12px;">Status</label>
                                    <select name="status" class="form-control form-control-sm">
                                        <option value="">Semua</option>
                                        <option value="open" @selected(request('status') === 'open')>Aktif</option>
                                        <option value="closed" @selected(request('status') === 'closed')>Ditutup</option>
                                    </select>
                                </div>
                                @if ($kasirList->isNotEmpty())
                                    <div class="col-md-3 form-group m-0">
                                        <label class="text-muted" style="font-size:12px;">Kasir</label>
                                        <select name="user_id" class="form-control form-control-sm">
                                            <option value="">Semua Kasir</option>
                                            @foreach ($kasirList as $k)
                                                <option value="{{ $k->id }}" @selected(request('user_id') == $k->id)>{{ $k->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif
                                <div class="col-md form-group m-0 d-flex align-items-end" style="gap:6px;">
                                    <button class="btn btn-sm btn-primary"><i class="fas fa-filter mr-1"></i> Terapkan</button>
                                    <a href="{{ route('cash-session.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- Sessions table --}}
                <div class="row">
                    <div class="col-12">
                        <div class="card-clean">
                            @if ($sessions->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Kasir</th>
                                                <th>Shift</th>
                                                <th>Dibuka</th>
                                                <th>Ditutup</th>
                                                <th class="text-center">Orders</th>
                                                <th class="text-right">Modal Awal</th>
                                                <th class="text-right">Expected</th>
                                                <th class="text-right">Variance</th>
                                                <th class="text-center">Status</th>
                                                <th class="text-center">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($sessions as $s)
                                                <tr>
                                                    <td class="font-weight-bold">{{ $s->user->name ?? '—' }}</td>
                                                    <td><span class="badge badge-soft-info">{{ $s->shift_label ?? '—' }}</span></td>
                                                    <td>{{ formatDate($s->opened_at, 'd M Y H:i') }}</td>
                                                    <td>{{ formatDate($s->closed_at, 'd M Y H:i', '—') }}</td>
                                                    <td class="text-center">{{ $s->orders_count }}</td>
                                                    <td class="text-right">{{ rupiah($s->opening_float) }}</td>
                                                    <td class="text-right">{{ $s->expected_cash !== null ? rupiah($s->expected_cash) : '—' }}</td>
                                                    <td class="text-right font-weight-bold">
                                                        @if ($s->variance === null)
                                                            <span class="text-muted">—</span>
                                                        @elseif ($s->variance == 0)
                                                            <span class="text-success">{{ rupiah(0) }}</span>
                                                        @elseif ($s->variance < 0)
                                                            <span class="text-danger">{{ rupiah($s->variance) }}</span>
                                                        @else
                                                            <span class="text-warning">+{{ rupiah($s->variance) }}</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-center">
                                                        @if ($s->is_open)
                                                            <span class="badge badge-soft-success">Aktif</span>
                                                        @else
                                                            <span class="badge badge-soft-secondary">Ditutup</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-center">
                                                        <a href="{{ route('cash-session.show', $s->id) }}" class="btn btn-sm btn-outline-info btn-icon" title="Detail">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <div class="text-muted" style="font-size:13px;">
                                        Menampilkan {{ $sessions->firstItem() }}–{{ $sessions->lastItem() }} dari {{ $sessions->total() }}
                                    </div>
                                    <div>{{ $sessions->links() }}</div>
                                </div>
                            @else
                                <x-empty-state icon="cash-register" title="Belum ada shift" description="Shift akan tampil di sini saat kasir membuka shift dari aplikasi POS." />
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    @unless ($mySession)
        <div class="modal fade" id="openShiftModal" tabindex="-1">
            <div class="modal-dialog">
                <form action="{{ route('cash-session.open') }}" method="POST" class="modal-content">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-door-open mr-1"></i> Buka Shift Baru</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Label Shift <span class="text-danger">*</span></label>
                            <select name="shift_label" class="form-control" required>
                                @foreach (\App\Models\CashSession::SHIFTS as $label)
                                    <option value="{{ $label }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Modal Awal (Rp) <span class="text-danger">*</span></label>
                            <input type="number" name="opening_float" min="0" value="0" class="form-control" required
                                placeholder="Jumlah uang awal di laci">
                            <small class="text-muted">Uang yang ada di laci saat shift dimulai.</small>
                        </div>

                        <div class="form-group">
                            <label>Catatan (opsional)</label>
                            <textarea name="opening_note" rows="2" maxlength="500" class="form-control"
                                placeholder="Misal: ada koin pecahan kecil dari shift sebelumnya"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-door-open mr-1"></i> Buka Shift
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endunless
@endsection
