@extends('layouts.dashmin')

@section('title', 'Pembelian Supplier - Warkop Cak Kebo')
@section('page_title', 'Pembelian Supplier')

@section('content')
@if(session('success'))
<div class="alert alert-success border-0 shadow-sm rounded-3 mb-4"><i class="fa-solid fa-circle-check me-2"></i>{{ session('success') }}</div>
@endif
<!-- Header Halaman -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 fw-bold text-dark mb-1">Input Pembelian Supplier</h1>
        <p class="text-muted small mb-0">Catat nota belanja dan update stok secara otomatis</p>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn text-white rounded-3 px-3 py-2 fw-semibold shadow-sm" style="font-size:13px;background:#23120b" data-bs-toggle="modal" data-bs-target="#modalTambahSupplier"><i class="fa-solid fa-plus me-1"></i> Tambah Supplier</button>
            <a href="{{ route('purchases.index') }}" class="btn btn-light border rounded-3 px-3 py-2 fw-semibold text-dark shadow-sm" style="font-size: 13px; background-color: #fff;">
            <i class="fa-regular fa-folder-open me-1"></i> Riwayat PO
            </a>
            <button type="button" onclick="window.print()" class="btn btn-light border rounded-3 px-3 py-2 fw-semibold text-dark shadow-sm" style="font-size: 13px; background-color: #fff;">
            <i class="fa-solid fa-print me-1"></i> Cetak Draft
        </button>
    </div>
</div>

<form action="{{ route('purchases.store') }}" method="POST" id="formPembelian">
    @csrf

    <!-- CARD 1: INFORMASI NOTA PEMBELIAN -->
    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
        <h5 class="fw-bold text-dark mb-3 fs-6">Informasi Nota Pembelian</h5>

        <div class="row g-3">
            <div class="col-md-4">
                <label for="nomor_invoice" class="form-label small fw-semibold text-dark">Nomor Invoice <span class="text-danger">*</span></label>
                <input type="text" class="form-control rounded-3 py-2 fs-6 border-light-subtle" id="nomor_invoice" name="nomor_invoice" placeholder="cth: INV-2026-0047" required style="background-color: #fafafa;">
            </div>
            <div class="col-md-4">
                <label for="nama_supplier" class="form-label small fw-semibold text-dark">Nama Supplier <span class="text-danger">*</span></label>
                <select class="form-select rounded-3 py-2 fs-6 border-light-subtle" id="nama_supplier" name="supplier_id" required style="background-color: #fafafa;">
                    <option value="">Pilih supplier</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                    @endforeach
                </select>
                @if($suppliers->isEmpty())<small class="text-danger">Belum ada supplier. Tambahkan supplier terlebih dahulu.</small>@endif
            </div>
            <div class="col-md-4">
                <label for="tanggal_beli" class="form-label small fw-semibold text-dark">Tanggal Beli <span class="text-danger">*</span></label>
                <input type="date" class="form-control rounded-3 py-2 fs-6 border-light-subtle" id="tanggal_beli" name="tanggal_beli" required style="background-color: #fafafa;" value="{{ date('Y-m-d') }}">
            </div>
        </div>
    </div>

    <!-- CARD 2: DAFTAR ITEM BELANJA -->
    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold text-dark m-0 fs-6">Daftar Item Belanja</h5>
            <button type="button" class="btn btn-sm btn-light border rounded-3 px-3 py-1 fw-semibold text-dark" style="font-size: 13px; background-color: #fff;" onclick="tambahBarisItem()">
                + Tambah Baris
            </button>
        </div>

        <div class="table-responsive mb-3">
            <table class="table table-borderless align-middle m-0" style="font-size: 13px;">
                <thead>
                    <tr class="text-uppercase text-muted border-bottom" style="font-size: 11px; letter-spacing: 0.5px; background-color: #fdfbf7;">
                        <th class="py-2 px-3 fw-bold" style="min-width: 250px;">BARANG / BAHAN BAKU</th>
                        <th class="py-2 px-2 fw-bold" style="width: 140px;">SATUAN BELI</th>
                        <th class="py-2 px-2 fw-bold" style="width: 100px;">JUMLAH</th>
                        <th class="py-2 px-2 fw-bold" style="width: 150px;">ISI / KONVERSI</th>
                        <th class="py-2 px-2 fw-bold" style="width: 150px;">HARGA SATUAN</th>
                        <th class="py-2 px-3 fw-bold text-end" style="width: 140px;">SUBTOTAL</th>
                    </tr>
                </thead>
                <tbody id="containerItemBelanja">
                    <!-- BARIS ITEM 1 (DEFAULT) -->
                    <tr class="item-row">
                        <td class="p-2">
                            <input type="text" class="form-control rounded-3 border-light-subtle py-2 px-3" name="barang[]" placeholder="Ketik nama bahan/produk..." style="background-color: #fafafa;" required>
                        </td>
                        <td class="p-2">
                            <select class="form-select rounded-3 border-light-subtle py-2" name="satuan[]" style="background-color: #fafafa;">
                                <option value="Karton" selected>Karton</option>
                                <option value="Dus">Dus</option>
                                <option value="Pack">Pack</option>
                                <option value="Kg">Kg</option>
                                <option value="Pcs">Pcs</option>
                            </select>
                        </td>
                        <td class="p-2">
                            <input type="number" class="form-control rounded-3 border-light-subtle py-2 input-jumlah" name="jumlah[]" value="1" min="1" style="background-color: #fafafa;" oninput="hitungSubtotal(this)">
                        </td>
                        <td class="p-2">
                            <input type="number" class="form-control rounded-3 border-light-subtle py-2 input-konversi" name="konversi[]" value="1" min="1" style="background-color: #fafafa;">
                            <span class="text-muted d-block mt-1" style="font-size: 10px;">unit per karton</span>
                        </td>
                        <td class="p-2">
                            <input type="number" class="form-control rounded-3 border-light-subtle py-2 input-harga" name="harga[]" value="0" min="0" style="background-color: #fafafa;" oninput="hitungSubtotal(this)">
                        </td>
                        <td class="p-2 text-end">
                            <div class="d-flex align-items-center justify-content-end gap-2">
                                <span class="fw-bold text-dark subtotal-text">--</span>
                                <button type="button" class="btn btn-sm btn-light border rounded-3 p-2 text-danger opacity-75" title="Hapus Baris" onclick="hapusBarisItem(this)">
                                    <i class="fa-regular fa-trash-can"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Tombol Tambah Item Belanja Area Tengah -->
        <button type="button" class="btn w-100 py-2 border rounded-3 text-muted fw-semibold mb-4" style="background-color: #fdfbf7; border-style: dashed !important; font-size: 13px;" onclick="tambahBarisItem()">
            + Tambah item belanja
        </button>

        <!-- FOOTER TOTAL & SIMPAN -->
        <div class="p-3 rounded-4 d-flex justify-content-between align-items-center flex-wrap gap-3" style="background-color: #fcf4f2; border: 1px solid #f2e2df;">
            <div>
                <div class="fw-bold text-dark fs-6">Total Nilai Pembelian</div>
                <div class="text-muted small" style="font-size: 12px;"><span id="totalValidCount">0</span> item valid · stok akan diperbarui otomatis</div>
            </div>
            <div class="d-flex align-items-center gap-3">
                <div class="fs-4 fw-extrabold text-dark">
                    Rp <span id="grandTotalText">0</span>
                </div>
                <button type="submit" class="btn text-white rounded-3 px-4 py-2 fw-semibold d-inline-flex align-items-center gap-2" style="background-color: #16a34a; font-size: 14px;">
                    <i class="fa-solid fa-circle-check"></i> Simpan & Update Stok
                </button>
            </div>
        </div>
    </div>
</form>

<div class="modal fade" id="modalTambahSupplier" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content rounded-4 border-0 p-3"><div class="modal-header"><h5 class="modal-title fw-bold">Tambah Supplier</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><form method="POST" action="{{ route('suppliers.store') }}">@csrf<div class="modal-body"><div class="mb-3"><label class="form-label small fw-semibold">Nama Supplier *</label><input name="name" class="form-control rounded-3" required></div><div class="mb-3"><label class="form-label small fw-semibold">Nomor Telepon</label><input name="phone" class="form-control rounded-3"></div><div><label class="form-label small fw-semibold">Alamat</label><textarea name="address" rows="3" class="form-control rounded-3"></textarea></div></div><div class="modal-footer"><button type="button" class="btn btn-light border rounded-3" data-bs-dismiss="modal">Batal</button><button class="btn text-white rounded-3" style="background:#23120b"><i class="fa-solid fa-floppy-disk me-1"></i>Simpan Supplier</button></div></form></div></div></div>

<!-- SCRIPT JAVASCRIPT DINAMIS -->
<script>
    function hitungSubtotal(element) {
        const row = element.closest('.item-row');
        const jumlah = parseFloat(row.querySelector('.input-jumlah').value) || 0;
        const harga = parseFloat(row.querySelector('.input-harga').value) || 0;
        const subtotal = jumlah * harga;

        row.querySelector('.subtotal-text').textContent = subtotal > 0 ? subtotal.toLocaleString('id-ID') : '--';
        hitungGrandTotal();
    }

    function hitungGrandTotal() {
        let total = 0;
        let count = 0;

        document.querySelectorAll('.item-row').forEach(row => {
            const jumlah = parseFloat(row.querySelector('.input-jumlah').value) || 0;
            const harga = parseFloat(row.querySelector('.input-harga').value) || 0;
            const barang = row.querySelector('input[name="barang[]"]').value.trim();

            if (barang !== '' && harga > 0) {
                count++;
            }
            total += (jumlah * harga);
        });

        document.getElementById('grandTotalText').textContent = total.toLocaleString('id-ID');
        document.getElementById('totalValidCount').textContent = count;
    }

    function tambahBarisItem() {
        const container = document.getElementById('containerItemBelanja');
        const newRow = document.createElement('tr');
        newRow.className = 'item-row';
        newRow.innerHTML = `
            <td class="p-2">
                <input type="text" class="form-control rounded-3 border-light-subtle py-2 px-3" name="barang[]" placeholder="Ketik nama bahan/produk..." style="background-color: #fafafa;" required oninput="hitungGrandTotal()">
            </td>
            <td class="p-2">
                <select class="form-select rounded-3 border-light-subtle py-2" name="satuan[]" style="background-color: #fafafa;">
                    <option value="Karton" selected>Karton</option>
                    <option value="Dus">Dus</option>
                    <option value="Pack">Pack</option>
                    <option value="Kg">Kg</option>
                    <option value="Pcs">Pcs</option>
                </select>
            </td>
            <td class="p-2">
                <input type="number" class="form-control rounded-3 border-light-subtle py-2 input-jumlah" name="jumlah[]" value="1" min="1" style="background-color: #fafafa;" oninput="hitungSubtotal(this)">
            </td>
            <td class="p-2">
                <input type="number" class="form-control rounded-3 border-light-subtle py-2 input-konversi" name="konversi[]" value="1" min="1" style="background-color: #fafafa;">
                <span class="text-muted d-block mt-1" style="font-size: 10px;">unit per karton</span>
            </td>
            <td class="p-2">
                <input type="number" class="form-control rounded-3 border-light-subtle py-2 input-harga" name="harga[]" value="0" min="0" style="background-color: #fafafa;" oninput="hitungSubtotal(this)">
            </td>
            <td class="p-2 text-end">
                <div class="d-flex align-items-center justify-content-end gap-2">
                    <span class="fw-bold text-dark subtotal-text">--</span>
                    <button type="button" class="btn btn-sm btn-light border rounded-3 p-2 text-danger opacity-75" title="Hapus Baris" onclick="hapusBarisItem(this)">
                        <i class="fa-regular fa-trash-can"></i>
                    </button>
                </div>
            </td>
        `;
        container.appendChild(newRow);
        hitungGrandTotal();
    }

    function hapusBarisItem(button) {
        const rowCount = document.querySelectorAll('.item-row').length;
        if (rowCount > 1) {
            button.closest('.item-row').remove();
            hitungGrandTotal();
        } else {
            alert('Minimal harus ada 1 item belanja.');
        }
    }
</script>
@endsection
