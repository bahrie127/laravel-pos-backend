@extends('layouts.app')

@section('title', 'Detail Shift #' . $session->id)

@section('main')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1>Shift #{{ $session->id }} — {{ $session->user->name }}</h1>
                <div class="section-header-button">
                    <a href="{{ route('cash-session.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left mr-1"></i> Kembali
                    </a>
                    @if ($session->is_open && $session->user_id === auth()->id())
                        <button type="button" class="btn btn-success ml-2" data-toggle="modal" data-target="#closeShiftModal">
                            <i class="fas fa-door-closed mr-1"></i> Tutup Shift Saya
                        </button>
                    @endif
                    @can('forceClose', $session)
                        <button type="button" class="btn btn-danger ml-2" data-toggle="modal" data-target="#forceCloseModal">
                            <i class="fas fa-door-closed mr-1"></i> Force Close
                        </button>
                    @endcan
                </div>
                <div class="section-header-breadcrumb">
                    <div class="breadcrumb-item active"><a href="{{ route('home') }}">Dashboard</a></div>
                    <div class="breadcrumb-item"><a href="{{ route('cash-session.index') }}">Cash Sessions</a></div>
                    <div class="breadcrumb-item">#{{ $session->id }}</div>
                </div>
            </div>

            <div class="section-body">
                @include('layouts.alert')

                {{-- Hero status --}}
                <div class="row">
                    <div class="col-12">
                        <div class="card-clean d-flex align-items-center" style="gap:18px;">
                            <div style="width:60px;height:60px;border-radius:14px;display:inline-flex;align-items:center;justify-content:center;font-size:26px;
                                background:{{ $session->is_open ? '#D1FAE5' : '#F3F4F6' }};
                                color:{{ $session->is_open ? '#047857' : '#6B7280' }};">
                                <i class="fas fa-{{ $session->is_open ? 'door-open' : 'door-closed' }}"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center" style="gap:10px;">
                                    <h3 class="m-0">{{ $session->is_open ? 'Shift Aktif' : 'Shift Ditutup' }}</h3>
                                    <span class="badge badge-soft-info">{{ $session->shift_label ?? '—' }}</span>
                                </div>
                                <div class="text-muted mt-1">
                                    Dibuka {{ formatDate($session->opened_at, 'd M Y H:i') }}
                                    @if (! $session->is_open)
                                        &middot; Ditutup {{ formatDate($session->closed_at, 'd M Y H:i') }}
                                        &middot; Durasi {{ $session->opened_at->diffForHumans($session->closed_at, ['parts' => 2, 'short' => true]) }}
                                    @else
                                        &middot; Berjalan {{ $session->opened_at->diffForHumans(now(), ['parts' => 2, 'short' => true]) }}
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    {{-- Left: rekonsiliasi --}}
                    <div class="col-md-5">
                        <div class="card-clean">
                            <h4 class="mb-3">Rekonsiliasi Kas</h4>

                            <div class="d-flex justify-content-between py-2 border-bottom">
                                <span class="text-muted">Modal Awal</span>
                                <span class="font-weight-bold">{{ rupiah($session->opening_float) }}</span>
                            </div>
                            <div class="d-flex justify-content-between py-2 border-bottom">
                                <span class="text-muted">Pendapatan Tunai</span>
                                <span class="font-weight-bold text-success">+ {{ rupiah($cashRevenue) }}</span>
                            </div>
                            <div class="d-flex justify-content-between py-2 border-bottom">
                                <span class="text-muted">Cash In (extra)</span>
                                <span>+ {{ rupiah($session->cash_in) }}</span>
                            </div>
                            <div class="d-flex justify-content-between py-2 border-bottom">
                                <span class="text-muted">Cash Out</span>
                                <span class="text-danger">- {{ rupiah($session->cash_out) }}</span>
                            </div>
                            <div class="d-flex justify-content-between py-3" style="font-size:16px;">
                                <span class="font-weight-bold">Expected (harus ada di laci)</span>
                                <span class="font-weight-bold">{{ rupiah($expectedCash) }}</span>
                            </div>

                            @if (! $session->is_open)
                                <div class="d-flex justify-content-between py-2 border-top">
                                    <span class="text-muted">Hitungan Fisik</span>
                                    <span>{{ rupiah($session->physical_count) }}</span>
                                </div>
                                <div class="py-3" style="background:{{ $session->variance == 0 ? '#D1FAE5' : ($session->variance < 0 ? '#FEE2E2' : '#FEF3C7') }};border-radius:8px;padding:12px;margin-top:8px;">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="font-weight-bold">
                                            @if ($session->variance == 0) ✓ Balance
                                            @elseif ($session->variance < 0) ✕ Kurang
                                            @else ⚠ Lebih
                                            @endif
                                        </span>
                                        <span class="font-weight-bold" style="font-size:18px;">
                                            {{ $session->variance > 0 ? '+' : '' }}{{ rupiah($session->variance) }}
                                        </span>
                                    </div>
                                </div>

                                @if ($session->closing_note)
                                    <div class="mt-3">
                                        <div class="text-muted" style="font-size:12px;">Catatan Penutupan:</div>
                                        <div class="mt-1" style="font-style:italic;background:#F9FAFB;padding:8px;border-radius:6px;font-size:13px;">
                                            {{ $session->closing_note }}
                                        </div>
                                    </div>
                                @endif
                            @else
                                <div class="text-center text-muted mt-3" style="font-size:13px;">
                                    @if ($session->user_id === auth()->id())
                                        Shift masih aktif. Gunakan tombol <strong>"Tutup Shift Saya"</strong> di atas
                                        atau tutup dari aplikasi POS.
                                    @else
                                        Shift masih aktif. Hanya kasir bersangkutan yang bisa menutup,
                                        atau admin bisa force-close.
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Right: payment breakdown + orders --}}
                    <div class="col-md-7">
                        <div class="card-clean">
                            <h4 class="mb-3">Breakdown Pembayaran</h4>
                            @if (! empty($byMethod))
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Metode</th>
                                            <th class="text-center">Trx</th>
                                            <th class="text-right">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($byMethod as $method => $data)
                                            <tr>
                                                <td><span class="badge badge-soft-info">{{ $method }}</span></td>
                                                <td class="text-center">{{ $data['count'] }}</td>
                                                <td class="text-right font-weight-bold">{{ rupiah($data['amount']) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @else
                                <p class="text-muted text-center py-3">Belum ada transaksi di shift ini.</p>
                            @endif
                        </div>

                        <div class="card-clean">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h4 class="m-0">Daftar Order ({{ $orders->count() }})</h4>
                            </div>

                            @if ($orders->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Order #</th>
                                                <th>Waktu</th>
                                                <th>Pembayaran</th>
                                                <th class="text-right">Total</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($orders as $order)
                                                <tr>
                                                    <td><code>{{ $order->order_number ?? '#'.$order->id }}</code></td>
                                                    <td>{{ formatDate($order->transaction_time, 'H:i') }}</td>
                                                    <td><span class="badge badge-soft-secondary">{{ $order->payment_method }}</span></td>
                                                    <td class="text-right font-weight-bold">{{ rupiah($order->total_price) }}</td>
                                                    <td>
                                                        <a href="{{ route('order.show', $order->id) }}" class="btn btn-sm btn-outline-info btn-icon">
                                                            <i class="fas fa-external-link-alt"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <p class="text-muted text-center py-3">Belum ada order.</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    @if ($session->is_open && $session->user_id === auth()->id())
        <div class="modal fade" id="closeShiftModal" tabindex="-1">
            <div class="modal-dialog">
                <form action="{{ route('cash-session.close', $session->id) }}" method="POST" class="modal-content">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-door-closed mr-1"></i> Tutup Shift #{{ $session->id }}</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info" style="font-size:13px;">
                            <i class="fas fa-info-circle mr-1"></i>
                            Hitung dulu uang fisik di laci, lalu isi jumlahnya di bawah. Sistem akan otomatis
                            menghitung selisih dengan total yang seharusnya ada.
                        </div>

                        <div class="form-group">
                            <label>Hitungan Fisik (Rp) <span class="text-danger">*</span></label>
                            <input type="number" name="physical_count" min="0" class="form-control" required
                                placeholder="Jumlah uang aktual di laci">
                            <small class="text-muted">Expected: <strong>{{ rupiah($expectedCash) }}</strong></small>
                        </div>

                        <div class="form-row">
                            <div class="col form-group">
                                <label>Cash In Extra (opsional)</label>
                                <input type="number" name="cash_in" min="0" value="{{ $session->cash_in ?? 0 }}" class="form-control">
                                <small class="text-muted">Tambahan uang masuk selain dari penjualan.</small>
                            </div>
                            <div class="col form-group">
                                <label>Cash Out (opsional)</label>
                                <input type="number" name="cash_out" min="0" value="{{ $session->cash_out ?? 0 }}" class="form-control">
                                <small class="text-muted">Pengeluaran kas selama shift.</small>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Catatan Penutupan (opsional)</label>
                            <textarea name="closing_note" rows="2" maxlength="500" class="form-control"
                                placeholder="Catatan misalnya alasan selisih..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-door-closed mr-1"></i> Tutup Shift Sekarang
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @can('forceClose', $session)
        <div class="modal fade" id="forceCloseModal" tabindex="-1">
            <div class="modal-dialog">
                <form action="{{ route('cash-session.force-close', $session->id) }}" method="POST" class="modal-content">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Force Close Shift #{{ $session->id }}</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning" style="font-size:13px;">
                            <i class="fas fa-exclamation-triangle mr-1"></i>
                            Action ini override flow normal. Pakai hanya kalau kasir lupa close & sudah tidak online.
                        </div>

                        <div class="form-group">
                            <label>Hitungan Fisik (Rp) <span class="text-danger">*</span></label>
                            <input type="number" name="physical_count" min="0" class="form-control" required
                                placeholder="Jumlah uang aktual di laci">
                            <small class="text-muted">Expected: {{ rupiah($expectedCash) }}</small>
                        </div>

                        <div class="form-row">
                            <div class="col form-group">
                                <label>Cash In Extra (opsional)</label>
                                <input type="number" name="cash_in" min="0" value="{{ $session->cash_in ?? 0 }}" class="form-control">
                            </div>
                            <div class="col form-group">
                                <label>Cash Out (opsional)</label>
                                <input type="number" name="cash_out" min="0" value="{{ $session->cash_out ?? 0 }}" class="form-control">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Catatan</label>
                            <textarea name="closing_note" rows="3" maxlength="500" class="form-control"
                                placeholder="Alasan force-close..."></textarea>
                            <small class="text-muted">Akan ditambah tag "[Force-closed oleh {nama admin}]" otomatis.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-door-closed mr-1"></i> Force Close Sekarang
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endcan
@endsection
