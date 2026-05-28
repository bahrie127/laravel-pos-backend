@props(['action', 'from', 'to', 'kasirList' => null, 'showKasir' => false])

<div class="reports-filter">
    <form method="GET" action="{{ $action }}" class="d-flex align-items-end flex-wrap" style="gap:12px;">
        <div class="rf-field">
            <label>Dari Tanggal</label>
            <input type="date" name="date_from" value="{{ request('date_from', $from->toDateString()) }}" class="form-control">
        </div>
        <div class="rf-field">
            <label>Sampai Tanggal</label>
            <input type="date" name="date_to" value="{{ request('date_to', $to->toDateString()) }}" class="form-control">
        </div>
        @if ($showKasir && $kasirList)
            <div class="rf-field" style="min-width:200px;">
                <label>Kasir</label>
                <select name="kasir_id" class="form-control">
                    <option value="">Semua kasir</option>
                    @foreach ($kasirList as $k)
                        <option value="{{ $k->id }}" @selected(request('kasir_id') == $k->id)>{{ $k->name }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-filter mr-1"></i> Tampilkan
        </button>
        <div class="ml-auto text-muted d-none d-md-block" style="font-size:13px;">
            Periode: <strong>{{ formatDate($from, 'd M Y') }}</strong> &mdash; <strong>{{ formatDate($to, 'd M Y') }}</strong>
        </div>
    </form>
</div>
