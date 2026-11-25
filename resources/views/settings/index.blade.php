@extends('layouts.dashmin')

@section('title', 'Pengaturan - Warkop Cak Kebo')
@section('page_title', 'Pengaturan')

@section('content')
<div class="mb-4">
    <h1 class="h3 fw-bold text-dark mb-1">Pengaturan Toko</h1>
    <p class="text-muted small mb-0">Kelola identitas toko dan perilaku sistem kasir.</p>
</div>

@if(session('success'))
    <div class="alert alert-success d-flex align-items-center gap-2 border-0 shadow-sm rounded-3 mb-4"><i class="fa-solid fa-circle-check"></i>{{ session('success') }}</div>
@endif

<form method="POST" action="{{ route('settings.update') }}">
    @csrf
    <div class="warkop-card mb-4"><label for="tax_percent" class="form-label fw-bold">Pajak penjualan (%)</label><input id="tax_percent" name="tax_percent" type="number" min="0" max="100" step="0.01" required value="{{ old('tax_percent', $settings['tax_percent']) }}" class="form-control" style="max-width:200px"><small class="text-muted">Isi 0 jika tidak memungut pajak. Nilai ini dipakai kasir dan dihitung server.</small></div>
    <div class="row g-4">
        <div class="col-lg-7">
            <div class="warkop-card h-100">
                <div class="d-flex align-items-center gap-3 mb-4"><div class="warkop-stat-icon-circle" style="background-color: #FAF4EB; color: #4A3326;"><i class="fa-solid fa-store"></i></div><div><h2 class="fs-6 fw-bold mb-1 text-dark">Informasi Toko</h2><p class="text-muted small mb-0">Ditampilkan pada laporan dan struk transaksi.</p></div></div>
                <div class="mb-3"><label for="store_name" class="form-label small fw-semibold">Nama toko</label><input id="store_name" name="store_name" value="{{ old('store_name', $settings['store_name']) }}" class="form-control rounded-3" required></div>
                <div class="mb-3"><label for="store_address" class="form-label small fw-semibold">Alamat toko</label><textarea id="store_address" name="store_address" rows="3" class="form-control rounded-3" required>{{ old('store_address', $settings['store_address']) }}</textarea></div>
                <div><label for="receipt_footer" class="form-label small fw-semibold">Pesan penutup struk</label><input id="receipt_footer" name="receipt_footer" value="{{ old('receipt_footer', $settings['receipt_footer']) }}" class="form-control rounded-3"></div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="warkop-card h-100">
                <div class="d-flex align-items-center gap-3 mb-4"><div class="warkop-stat-icon-circle" style="background-color: #EBF8F2; color: #1E824C;"><i class="fa-solid fa-sliders"></i></div><div><h2 class="fs-6 fw-bold mb-1 text-dark">Preferensi Sistem</h2><p class="text-muted small mb-0">Atur notifikasi dan alur kasir.</p></div></div>
                <div class="p-3 border rounded-3 mb-3 d-flex justify-content-between align-items-center"><div><div class="fw-semibold small">Peringatan stok menipis</div><div class="text-muted" style="font-size: 11px;">Tampilkan notifikasi stok kritis.</div></div><div class="form-check form-switch fs-5"><input class="form-check-input" type="checkbox" name="low_stock_alert" value="1" @checked($settings['low_stock_alert'])></div></div>
                <div class="p-3 border rounded-3 d-flex justify-content-between align-items-center"><div><div class="fw-semibold small">Cetak struk otomatis</div><div class="text-muted" style="font-size: 11px;">Siapkan pencetakan setelah pembayaran.</div></div><div class="form-check form-switch fs-5"><input class="form-check-input" type="checkbox" name="auto_print" value="1" @checked($settings['auto_print'])></div></div>
            </div>
        </div>
    </div>
    <div class="d-flex justify-content-end gap-2 mt-4"><a href="{{ url('/admin') }}" class="btn btn-light border rounded-3 px-4 fw-semibold">Batal</a><button type="submit" class="btn text-white rounded-3 px-4 fw-semibold" style="background-color: #23120b;"><i class="fa-solid fa-floppy-disk me-1"></i> Simpan Pengaturan</button></div>
</form>

<div class="warkop-card mt-4">
    <div class="d-flex align-items-center gap-3"><div class="warkop-stat-icon-circle" style="background-color: #FFF4E5; color: #D35400;"><i class="fa-solid fa-user-gear"></i></div><div class="flex-grow-1"><h2 class="fs-6 fw-bold mb-1 text-dark">Profil Pengguna</h2><p class="text-muted small mb-0">Ubah nama, email, atau kata sandi akun admin.</p></div><a href="{{ route('profile.edit') }}" class="btn btn-sm btn-light border rounded-3 fw-semibold">Kelola Profil <i class="fa-solid fa-arrow-right ms-1"></i></a></div>
</div>
@endsection
