@extends('layouts.dashmin')

@section('title', 'Tambah Produk Baru - Warkop Cak Kebo')
@section('page_title', 'Tambah Produk')

@section('content')
<div class="warkop-card p-4 rounded-4 bg-white shadow-sm border-0">
    <!-- Header Form -->
    <div class="d-flex justify-content-between align-items-start mb-4 pb-3 border-bottom">
        <div>
            <h1 class="h4 fw-bold text-dark mb-1">Tambah Produk Baru</h1>
            <p class="text-muted small mb-0">Lengkapi informasi produk lalu simpan untuk menambahkan ke katalog kasir.</p>
        </div>
        <a href="{{ route('posts.index') }}" class="btn btn-light btn-sm rounded-circle p-2 text-muted" style="width: 36px; height: 36px;">
            <i class="fa-solid fa-xmark"></i>
        </a>
    </div>

    <form action="{{ route('posts.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="row g-4">

            <!-- KOLOM 1: INFO DASAR -->
            <div class="col-lg-4 border-end-lg pe-lg-4">
                <h6 class="text-uppercase text-muted fw-bold mb-3" style="font-size: 11px; letter-spacing: 0.5px;">Info Dasar</h6>

                <div class="mb-3">
                    <label for="title" class="form-label small fw-semibold text-dark">Nama Produk <span class="text-danger">*</span></label>
                    <input type="text" class="form-control rounded-3 py-2 fs-6 @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title') }}" placeholder="cth: Kopi Susu Gula Aren" required style="background-color: #fafafa;">
                    @error('title')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="category" class="form-label small fw-semibold text-dark">Kategori <span class="text-danger">*</span></label>
                    <select class="form-select rounded-3 py-2 fs-6" id="category" name="category" style="background-color: #fafafa;">
                        <option value="Kopi Panas" selected>Kopi Panas</option>
                        <option value="Kopi Dingin">Kopi Dingin</option>
                        <option value="Non-Kopi">Non-Kopi</option>
                        <option value="Makanan">Makanan</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="price" class="form-label small fw-semibold text-dark">Harga Dasar (Rp) <span class="text-danger">*</span></label>
                    <input type="number" class="form-control rounded-3 py-2 fs-6" id="price" name="price" min="0" step="1" required value="{{ old('price') }}" placeholder="cth: 12000" style="background-color: #fafafa;">
                </div>

                <div class="mb-3">
                    <label for="content" class="form-label small fw-semibold text-dark">Deskripsi</label>
                    <textarea class="form-control rounded-3 py-2 fs-6 @error('content') is-invalid @enderror" id="content" name="content" rows="3" placeholder="Keterangan singkat produk (opsional)" style="background-color: #fafafa;">{{ old('content') }}</textarea>
                    @error('content')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Status Switch -->
                <div class="p-3 rounded-3 d-flex justify-content-between align-items-center" style="background-color: #fdfbf7; border: 1px solid #f1ece4;">
                    <div>
                        <div class="fw-bold text-dark small">Status Produk</div>
                        <span class="text-muted" style="font-size: 11px;">Tersedia di kasir</span>
                    </div>
                    <div class="form-check form-switch m-0 fs-4">
                        <input class="form-check-input" type="checkbox" role="switch" id="status" name="status" checked style="cursor: pointer;">
                    </div>
                </div>
            </div>

            <!-- KOLOM 2: TIPE MANAJEMEN STOK -->
            <div class="col-lg-4 border-end-lg px-lg-4">
                <h6 class="text-uppercase text-muted fw-bold mb-3" style="font-size: 11px; letter-spacing: 0.5px;">Tipe Manajemen Stok</h6>

                <div class="d-flex flex-column gap-3">
                    <!-- Option 1: Resep (BoM) -->
                    <label class="p-3 rounded-3 border d-flex gap-3 position-relative" style="background-color: #fcf8f6; border-color: #e8d0c5 !important; cursor: pointer;">
                        <input class="form-check-input mt-1" type="radio" name="stock_type" value="bom" checked>
                        <div>
                            <div class="fw-bold text-dark small">Resep (BoM)</div>
                            <p class="text-muted mb-0" style="font-size: 11px; line-height: 1.3;">Stok bahan baku berkurang sesuai resep tiap transaksi. Untuk produk racikan.</p>
                        </div>
                    </label>

                    <!-- Option 2: Barang Statis -->
                    <label class="p-3 rounded-3 border d-flex gap-3" style="background-color: #fafafa; border-color: #eeeeee; cursor: pointer;">
                        <input class="form-check-input mt-1" type="radio" name="stock_type" value="static">
                        <div>
                            <div class="fw-bold text-dark small">Barang Statis</div>
                            <p class="text-muted mb-0" style="font-size: 11px; line-height: 1.3;">Stok dikurangi per unit terjual. Untuk produk kemasan jadi (Aqua, snack).</p>
                        </div>
                    </label>

                    <!-- Option 3: Tidak Dihitung -->
                    <label class="p-3 rounded-3 border d-flex gap-3" style="background-color: #fafafa; border-color: #eeeeee; cursor: pointer;">
                        <input class="form-check-input mt-1" type="radio" name="stock_type" value="none">
                        <div>
                            <div class="fw-bold text-dark small">Tidak Dihitung</div>
                            <p class="text-muted mb-0" style="font-size: 11px; line-height: 1.3;">Stok tidak dihitung sistem. Untuk produk harian (gorengan fresh, dll).</p>
                        </div>
                    </label>
                </div>
            </div>

            <!-- KOLOM 3: FOTO PRODUK -->
            <div class="col-lg-4 ps-lg-4">
                <h6 class="text-uppercase text-muted fw-bold mb-3" style="font-size: 11px; letter-spacing: 0.5px;">Foto Produk</h6>

                <div class="p-4 rounded-4 border border-dashed text-center d-flex flex-column align-items-center justify-content-center" style="background-color: #fafafa; border-style: dashed !important; border-color: #ddd !important; min-height: 220px;">
                    <div class="mb-2 fs-2 text-warning">📸</div>
                    <div class="fw-semibold text-dark small mb-1">Drag & drop foto</div>
                    <p class="text-muted mb-3" style="font-size: 11px;">PNG, JPG maks 2MB · 1:1</p>

                    <label for="photo" class="btn btn-white btn-sm border rounded-3 px-3 py-1 shadow-sm fw-semibold" style="cursor: pointer; background: #fff; font-size: 12px;">
                        Pilih File
                    </label>
                    <input type="file" id="photo" name="photo" class="d-none" accept="image/*">
                </div>
            </div>

        </div>

        @include('posts.recipe')
        <!-- FOOTER / BUTTONS -->
        <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
            <a href="{{ route('posts.index') }}" class="btn btn-light rounded-3 px-4 fw-semibold text-dark" style="background-color: #f2f2f2; font-size: 13px;">Batal</a>
            <button type="submit" class="btn rounded-3 px-4 fw-semibold text-white" style="background-color: #7d7268; font-size: 13px;">
                <i class="fa-solid fa-box-archive me-1"></i> Simpan Produk
            </button>
        </div>
    </form>
</div>
@endsection
