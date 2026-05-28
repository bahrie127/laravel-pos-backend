@extends('layouts.app')

@section('title', 'Promo')

@php
    $statusBadge = [
        'live' => ['Aktif', 'badge-soft-success'],
        'scheduled' => ['Terjadwal', 'badge-soft-info'],
        'expired' => ['Kadaluarsa', 'badge-soft-warning'],
        'inactive' => ['Nonaktif', 'badge-soft-secondary'],
    ];
    $typeLabel = [
        'percent' => 'Persen',
        'rupiah' => 'Rupiah',
        'b1g1' => 'B1G1',
    ];
@endphp

@section('main')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1>Promo</h1>
                <div class="section-header-button">
                    @can('create', App\Models\Promo::class)
                        <a href="{{ route('promo.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus mr-1"></i> Tambah Promo
                        </a>
                    @endcan
                </div>
                <div class="section-header-breadcrumb">
                    <div class="breadcrumb-item active"><a href="{{ route('home') }}">Dashboard</a></div>
                    <div class="breadcrumb-item">Promo</div>
                </div>
            </div>

            <div class="section-body">
                <div class="row">
                    <div class="col-12">
                        @include('layouts.alert')
                    </div>
                </div>

                {{-- Filter bar --}}
                <div class="row">
                    <div class="col-12">
                        <div class="card-clean">
                            <form method="GET" action="{{ route('promo.index') }}" class="d-flex" style="gap:8px;flex-wrap:wrap;">
                                <input type="text" name="q" value="{{ request('q') }}" class="form-control"
                                    placeholder="Cari nama atau kode..." style="max-width:260px;">

                                <select name="type" class="form-control" style="max-width:160px;">
                                    <option value="">Semua Tipe</option>
                                    <option value="percent" @selected(request('type') === 'percent')>Persen</option>
                                    <option value="rupiah" @selected(request('type') === 'rupiah')>Rupiah</option>
                                    <option value="b1g1" @selected(request('type') === 'b1g1')>B1G1</option>
                                </select>

                                <select name="status" class="form-control" style="max-width:160px;">
                                    <option value="">Semua Status</option>
                                    <option value="live" @selected(request('status') === 'live')>Aktif</option>
                                    <option value="scheduled" @selected(request('status') === 'scheduled')>Terjadwal</option>
                                    <option value="expired" @selected(request('status') === 'expired')>Kadaluarsa</option>
                                    <option value="inactive" @selected(request('status') === 'inactive')>Nonaktif</option>
                                </select>

                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i>
                                </button>
                                <a href="{{ route('promo.index') }}" class="btn btn-outline-secondary">Reset</a>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- Content --}}
                @if ($promos->count() > 0)
                    <div class="row">
                        <div class="col-12">
                            <div class="card-clean">
                                <div class="table-responsive">
                                    <table class="table-striped table">
                                        <thead>
                                            <tr>
                                                <th>Nama</th>
                                                <th>Kode</th>
                                                <th>Tipe</th>
                                                <th class="text-right">Nilai</th>
                                                <th class="text-right">Min. Belanja</th>
                                                <th>Periode</th>
                                                <th class="text-center">Dipakai</th>
                                                <th class="text-center">Status</th>
                                                <th class="text-center">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($promos as $promo)
                                                @php $status = $promo->status(); @endphp
                                                <tr>
                                                    <td class="font-weight-bold">{{ $promo->name }}</td>
                                                    <td>
                                                        @if ($promo->code)
                                                            <code style="background:#F3F4F6;padding:2px 6px;border-radius:4px;">{{ $promo->code }}</code>
                                                        @else
                                                            <span class="text-muted">— otomatis</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-soft-info">{{ $typeLabel[$promo->type] ?? $promo->type }}</span>
                                                    </td>
                                                    <td class="text-right">
                                                        @if ($promo->type === 'percent')
                                                            {{ $promo->value }}%
                                                        @elseif ($promo->type === 'rupiah')
                                                            {{ rupiah($promo->value) }}
                                                        @else
                                                            <span class="text-muted">—</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-right">
                                                        {{ $promo->min_subtotal > 0 ? rupiah($promo->min_subtotal) : '—' }}
                                                    </td>
                                                    <td>
                                                        @if ($promo->starts_at || $promo->ends_at)
                                                            <small class="d-block">{{ formatDate($promo->starts_at, 'd M Y H:i', 'Sekarang') }}</small>
                                                            <small class="d-block text-muted">s/d {{ formatDate($promo->ends_at, 'd M Y H:i', 'Tanpa batas') }}</small>
                                                        @else
                                                            <span class="text-muted">Tanpa batas</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-center">{{ $promo->orders_count }}</td>
                                                    <td class="text-center">
                                                        <span class="badge {{ $statusBadge[$status][1] }}">{{ $statusBadge[$status][0] }}</span>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex justify-content-center" style="gap:4px;">
                                                            @can('update', $promo)
                                                                <form action="{{ route('promo.toggle', $promo->id) }}" method="POST" class="m-0">
                                                                    @csrf
                                                                    <button type="submit" class="btn btn-sm {{ $promo->active ? 'btn-outline-warning' : 'btn-outline-success' }} btn-icon"
                                                                        title="{{ $promo->active ? 'Nonaktifkan' : 'Aktifkan' }}">
                                                                        <i class="fas fa-{{ $promo->active ? 'pause' : 'play' }}"></i>
                                                                    </button>
                                                                </form>
                                                                <a href="{{ route('promo.edit', $promo->id) }}" class="btn btn-sm btn-info btn-icon" title="Edit">
                                                                    <i class="fas fa-edit"></i>
                                                                </a>
                                                            @endcan
                                                            @can('delete', $promo)
                                                                <form action="{{ route('promo.destroy', $promo->id) }}" method="POST" class="m-0">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button type="submit" class="btn btn-sm btn-danger btn-icon confirm-delete"
                                                                        data-title="Hapus promo?"
                                                                        data-text="Promo '{{ $promo->name }}' akan dihapus.">
                                                                        <i class="fas fa-trash"></i>
                                                                    </button>
                                                                </form>
                                                            @endcan
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <div class="text-muted" style="font-size:13px;">
                                        Menampilkan {{ $promos->firstItem() }}–{{ $promos->lastItem() }}
                                        dari {{ $promos->total() }}
                                    </div>
                                    <div>{{ $promos->links() }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="row">
                        <div class="col-12">
                            <div class="card-clean">
                                <x-empty-state
                                    icon="percent"
                                    title="Belum ada promo"
                                    description="Buat promo atau voucher untuk diskon di aplikasi kasir."
                                    :action-label="auth()->user()->can('create', App\Models\Promo::class) ? 'Tambah Promo' : null"
                                    :action-url="auth()->user()->can('create', App\Models\Promo::class) ? route('promo.create') : null"
                                />
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </section>
    </div>
@endsection
