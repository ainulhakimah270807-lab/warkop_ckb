@extends('layouts.dashmin')
@section('page_title', 'Pembelian Supplier')
@section('content')
<h1 class="h3 fw-bold">Pembelian bahan baku</h1><p class="text-muted">Stok bertambah sesuai satuan pembelian. Invoice supplier yang sama tidak dapat dicatat dua kali.</p>
<form method="POST" action="{{ route('purchases.store') }}" class="warkop-card mb-4">@csrf
<div class="row g-3"><div class="col-md-4"><label class="form-label">Supplier</label><select class="form-select" name="supplier_id" required><option value="">Pilih supplier</option>@foreach($suppliers as $supplier)<option value="{{ $supplier->id }}">{{ $supplier->name }}</option>@endforeach</select></div><div class="col-md-4"><label class="form-label">Nomor invoice</label><input class="form-control" name="nomor_invoice" maxlength="100" required value="{{ old('nomor_invoice') }}"></div><div class="col-md-4"><label class="form-label">Tanggal</label><input class="form-control" type="date" name="tanggal_beli" required value="{{ old('tanggal_beli', now('Asia/Jakarta')->toDateString()) }}"></div></div>
<div class="table-responsive mt-3"><table class="table"><thead><tr><th>Bahan</th><th>Jumlah dibeli</th><th>Satuan</th><th>Total biaya baris (Rp)</th><th></th></tr></thead><tbody id="purchaseRows"></tbody></table></div>
<button type="button" id="addPurchase" class="btn btn-light border">+ Tambah bahan</button><button class="btn btn-dark">Simpan pembelian & tambah stok</button></form>
<details class="warkop-card mb-4"><summary class="fw-bold">Tambah supplier</summary><form method="POST" action="{{ route('suppliers.store') }}" class="mt-3">@csrf<div class="row g-2"><div class="col-md-4"><input class="form-control" name="name" placeholder="Nama supplier" required></div><div class="col-md-4"><input class="form-control" name="phone" placeholder="Telepon"></div><div class="col-md-4"><input class="form-control" name="address" placeholder="Alamat"></div></div><button class="btn btn-dark mt-2">Tambah supplier</button></form></details>
<div class="warkop-card"><h2 class="h5">Riwayat pembelian</h2>@forelse($purchases as $purchase)<div class="border-bottom py-3"><strong>{{ $purchase->invoice }} · {{ $purchase->supplier_name }}</strong><div>{{ $purchase->purchased_on }} · Rp {{ number_format($purchase->total_cost, 0, ',', '.') }}</div></div>@empty<p>Belum ada pembelian.</p>@endforelse<div class="d-flex justify-content-between mt-3">@if($purchases->previousPageUrl())<a href="{{ $purchases->previousPageUrl() }}">Sebelumnya</a>@endif<span>Halaman {{ $purchases->currentPage() }}</span>@if($purchases->nextPageUrl())<a href="{{ $purchases->nextPageUrl() }}">Berikutnya</a>@endif</div></div>
@endsection
@push('scripts')
@php($purchaseIngredients = $ingredients->map(fn($i) => ['id' => $i->id, 'name' => $i->name, 'unit' => $i->unit])->all())
<script>
(() => {
 const ingredients = @json($purchaseIngredients); let index = 0;
 function add() {
  const key = index++, row = document.createElement('tr');
  const select = document.createElement('select'); select.name = `items[${key}][ingredient_id]`; select.required = true; select.className = 'form-select'; select.add(new Option('Pilih bahan', '')); ingredients.forEach(i => select.add(new Option(i.name, i.id))); row.insertCell().append(select);
  const quantity = document.createElement('input'); quantity.type = 'number'; quantity.step = '0.000001'; quantity.min = '0.000001'; quantity.required = true; quantity.name = `items[${key}][quantity]`; quantity.className = 'form-control'; row.insertCell().append(quantity);
  const unit = document.createElement('input'); unit.name = `items[${key}][unit]`; unit.required = true; unit.className = 'form-control'; unit.placeholder = 'liter / ml / kg / gram / pcs'; row.insertCell().append(unit);
  select.onchange = () => { const item = ingredients.find(i => String(i.id) === select.value); if (item) unit.value = item.unit; };
  const cost = document.createElement('input'); cost.type = 'number'; cost.min = '0'; cost.step = '1'; cost.required = true; cost.value = '0'; cost.name = `items[${key}][cost]`; cost.className = 'form-control'; row.insertCell().append(cost);
  const remove = document.createElement('button'); remove.type = 'button'; remove.textContent = 'Hapus'; remove.className = 'btn btn-light text-danger'; remove.onclick = () => row.remove(); row.insertCell().append(remove);
  document.getElementById('purchaseRows').append(row);
 }
 document.getElementById('addPurchase').onclick = add; add();
})();
</script>
@endpush
