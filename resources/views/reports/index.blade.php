@extends('layouts.dashmin')

@section('title', 'Laporan Penjualan - Warkop Cak Kebo')
@section('page_title', 'Laporan')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <div>
        <h1 class="h3 fw-bold text-dark mb-1">Laporan Penjualan</h1>
        <p class="text-muted small mb-0">Pantau omzet dan transaksi operasional secara ringkas.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('reports.export') }}" class="btn btn-light border rounded-3 px-3 py-2 fw-semibold text-dark shadow-sm" style="font-size: 13px;">
            <i class="fa-solid fa-download me-1"></i> Ekspor CSV
        </a>
        <button type="button" onclick="window.print()" class="btn text-white rounded-3 px-3 py-2 fw-semibold shadow-sm" style="background-color: #23120b; font-size: 13px;">
            <i class="fa-solid fa-print me-1"></i> Cetak Laporan
        </button>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success border-0 shadow-sm rounded-3">{{ session('success') }}</div>
@endif

<form method="GET" action="{{ route('reports.index') }}" class="card border-0 shadow-sm rounded-4 p-3 mb-4 bg-white">
    <div class="row align-items-end g-3">
        <div class="col-md-4">
            <label for="period" class="form-label small fw-semibold text-dark">Periode laporan</label>
            <select name="period" id="period" class="form-select rounded-3" onchange="this.form.submit()">
                <option value="hari_ini" @selected($period === 'hari_ini')>Hari ini</option>
                <option value="7_hari" @selected($period === '7_hari')>7 hari terakhir</option>
                <option value="bulan_ini" @selected($period === 'bulan_ini')>Bulan ini</option>
            </select>
        </div>
        <div class="col-md-8 d-flex justify-content-md-end">
            <span class="small text-muted pb-2">Periode aktif: <strong class="text-dark">{{ str_replace('_', ' ', ucwords($period)) }}</strong></span>
        </div>
    </div>
</form>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3"><div class="warkop-card h-100"><div class="warkop-stat-title">Total Omzet</div><div class="warkop-stat-value">Rp 0</div><div class="warkop-stat-trend trend-neutral">Belum ada transaksi</div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="warkop-card h-100"><div class="warkop-stat-title">Total Transaksi</div><div class="warkop-stat-value">0</div><div class="warkop-stat-trend trend-neutral">Belum ada transaksi</div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="warkop-card h-100"><div class="warkop-stat-title">Rata-rata Transaksi</div><div class="warkop-stat-value">Rp 0</div><div class="warkop-stat-trend trend-neutral">Belum tersedia</div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="warkop-card h-100"><div class="warkop-stat-title">Menu Terlaris</div><div class="warkop-stat-value fs-5">-</div><div class="warkop-stat-trend trend-neutral">Belum ada data</div></div></div>
</div>

<div class="warkop-card">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div><h2 class="fs-6 fw-bold mb-1 text-dark">Ringkasan Penjualan Harian</h2><p class="text-muted small mb-0">Rincian kinerja penjualan per hari.</p></div>
        <button type="button" class="btn btn-sm btn-light border rounded-3" onclick="document.getElementById('reportSearch').focus()"><i class="fa-solid fa-magnifying-glass me-1"></i> Cari tanggal</button>
    </div>
    <input id="reportSearch" type="search" class="form-control form-control-sm rounded-3 mb-3" placeholder="Cari tanggal laporan..." oninput="filterReportRows(this.value)">
    <div class="table-responsive">
        <table class="warkop-table" id="reportTable">
            <thead><tr><th>TANGGAL</th><th>TRANSAKSI</th><th>OMZET</th><th>RATA-RATA</th><th class="text-end">AKSI</th></tr></thead>
            <tbody>
                @if($reports->isEmpty())<tr><td colspan="5" class="text-center py-5 text-muted"><i class="fa-solid fa-chart-line fs-2 mb-2"></i><p class="mb-0">Belum ada data transaksi pada periode ini.</p></td></tr>@endif
                @foreach($reports as $report)
                <tr>
                    <td class="fw-semibold text-dark">{{ $report['date'] }}</td>
                    <td>{{ $report['transactions'] }} transaksi</td>
                    <td class="fw-bold text-dark">Rp {{ number_format($report['revenue'], 0, ',', '.') }}</td>
                    <td class="text-muted">Rp {{ number_format($report['average'], 0, ',', '.') }}</td>
                    <td class="text-end"><button type="button" class="btn btn-sm btn-light border rounded-2" onclick="alert('Detail {{ $report['date'] }}: {{ $report['transactions'] }} transaksi dengan omzet Rp {{ number_format($report['revenue'], 0, ',', '.') }}.')"><i class="fa-regular fa-eye me-1"></i> Detail</button></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
function filterReportRows(query) {
    const value = query.toLowerCase();
    document.querySelectorAll('#reportTable tbody tr').forEach(row => {
        row.hidden = !row.textContent.toLowerCase().includes(value);
    });
}
</script>
@endpush
