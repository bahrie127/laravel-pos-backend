@props(['action', 'from', 'to', 'kasirList' => null, 'showKasir' => false])

<div class="card-clean">
    <form method="GET" action="{{ $action }}">
        <div class="form-row">
            <div class="col-md-3 form-group">
                <label class="text-muted" style="font-size:12px;">Dari Tanggal</label>
                <input type="date" name="date_from" value="{{ request('date_from', $from->toDateString()) }}" class="form-control">
            </div>
            <div class="col-md-3 form-group">
                <label class="text-muted" style="font-size:12px;">Sampai Tanggal</label>
                <input type="date" name="date_to" value="{{ request('date_to', $to->toDateString()) }}" class="form-control">
            </div>
            @if ($showKasir && $kasirList)
                <div class="col-md-3 form-group">
                    <label class="text-muted" style="font-size:12px;">Kasir</label>
                    <select name="kasir_id" class="form-control">
                        <option value="">Semua kasir</option>
                        @foreach ($kasirList as $k)
                            <option value="{{ $k->id }}" @selected(request('kasir_id') == $k->id)>{{ $k->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="col-md-3 form-group d-flex align-items-end" style="gap:8px;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter mr-1"></i> Tampilkan
                </button>
            </div>
        </div>
    </form>
</div>
