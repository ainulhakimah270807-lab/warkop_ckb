@extends('layouts.dashmin')
@section('page_title', 'Pusat Owner')
@section('content')
<h1 class="h3 fw-bold">Pusat Owner</h1><p class="text-muted">Data web dan aplikasi menggunakan database yang sama. Perbarui halaman untuk melihat transaksi terbaru.</p>
<div class="row g-3 mb-4">
@foreach(['Omzet hari ini' => 'Rp '.number_format($overview['revenue_today'], 0, ',', '.'), 'Transaksi hari ini' => $overview['sales_today'], 'Stok menipis' => $overview['low_stock'], 'Presensi hari ini' => $overview['attendances_today']] as $label => $value)
<div class="col-sm-6 col-xl-3"><div class="warkop-card"><div class="small text-muted">{{ $label }}</div><div class="h4 mt-2 fw-bold">{{ $value }}</div></div></div>
@endforeach
</div><div class="d-flex flex-wrap gap-2 mb-3">@foreach($sections as $key => $label)<a class="btn {{ $section === $key ? 'btn-dark' : 'btn-light border' }}" href="{{ route('owner.index', ['section' => $key]) }}">{{ $label }}</a>@endforeach</div>
<form class="d-flex gap-2 mb-3"><input type="hidden" name="section" value="{{ $section }}"><input type="search" class="form-control" name="search" value="{{ request('search') }}" placeholder="Cari nama, nomor transaksi, atau invoice"><button class="btn btn-dark">Cari</button></form>
<div class="warkop-card">@forelse($rows as $row)<div class="py-3 border-bottom"><div class="d-flex justify-content-between gap-3"><strong>{{ $row['title'] }}</strong>
@if($section === 'sales')<a href="{{ route('owner.sale', $row['id']) }}">Rincian & pemakaian bahan</a>@endif
@if($section === 'products')<a href="{{ route('posts.edit', $row['id']) }}">Edit resep</a>@endif
</div><div class="small text-muted">{{ $row['subtitle'] }}</div>@foreach($row['details'] as $detail)<div class="small mt-1">{{ $detail }}</div>@endforeach</div>
@empty<p class="text-muted">Belum ada data pada bagian ini.</p>@endforelse
<div class="d-flex justify-content-between mt-3">@if($rows->previousPageUrl())<a href="{{ $rows->previousPageUrl() }}">← Sebelumnya</a>@endif<span>Halaman {{ $rows->currentPage() }} / {{ $rows->lastPage() }} · {{ $rows->total() }} data</span>@if($rows->nextPageUrl())<a href="{{ $rows->nextPageUrl() }}">Berikutnya →</a>@endif</div></div>
@endsection
