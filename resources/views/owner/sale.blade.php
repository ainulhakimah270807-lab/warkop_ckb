@extends('layouts.dashmin')
@section('page_title', 'Rincian Penjualan')
@section('content')
<div class="warkop-card"><a href="{{ route('owner.index') }}">← Pusat Owner</a><h1 class="h3 mt-3">Transaksi #{{ $sale->id }}</h1>
<p>{{ $sale->paid_at->timezone('Asia/Jakarta')->format('d-m-Y H:i:s') }} WIB · Kasir {{ $sale->cashier->name }} · {{ $sale->payment_method }} · {{ $sale->order_type }}</p>
<table class="table"><thead><tr><th>Menu</th><th>Jumlah</th><th>Harga</th><th>Total</th></tr></thead><tbody>@foreach($sale->items as $item)<tr><td>{{ $item->product_name }}</td><td>{{ $item->quantity }}</td><td>Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td><td>Rp {{ number_format($item->total, 0, ',', '.') }}</td></tr>@endforeach</tbody></table>
<p>Subtotal Rp {{ number_format($sale->subtotal, 0, ',', '.') }} · Pajak {{ $sale->tax_percent }}%: Rp {{ number_format($sale->tax, 0, ',', '.') }}</p><h2 class="h4">Total Rp {{ number_format($sale->total, 0, ',', '.') }}</h2>
<h2 class="h5 mt-4">Pemakaian bahan / stok barang jadi</h2><div class="table-responsive"><table class="table"><thead><tr><th>Bahan</th><th>Terpakai</th><th>Stok sebelum</th><th>Stok sesudah</th></tr></thead><tbody>@forelse($movements as $move)<tr><td>{{ $move->item_name }}</td><td>{{ -(float)$move->quantity }} {{ $move->unit }}</td><td>{{ (float)$move->balance_before }}</td><td>{{ (float)$move->balance_after }}</td></tr>@empty<tr><td colspan="4">Pesanan ini tidak memakai stok terhitung.</td></tr>@endforelse</tbody></table></div>
<p class="small text-muted">Pemakaian ini direkam saat transaksi; perubahan resep berikutnya tidak mengubah riwayat.</p></div>
@endsection
