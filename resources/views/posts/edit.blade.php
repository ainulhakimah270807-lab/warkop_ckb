@extends('layouts.dashmin')

@section('title', 'Edit Produk - Warkop Cak Kebo')
@section('page_title', 'Edit Produk')

@section('content')
<div class="warkop-card p-4 rounded-4 bg-white shadow-sm border-0">
    <!-- Header Form -->
    <div class="d-flex justify-content-between align-items-start mb-4 pb-3 border-bottom">
        <div>
            <h1 class="h4 fw-bold text-dark mb-1">Edit Produk</h1>
            <p class="text-muted small mb-0">Perbarui informasi produk di bawah ini.</p>
        </div>
        <a href="{{ route('posts.index') }}" class="btn btn-light btn-sm rounded-circle p-2 text-muted" style="width: 36px; height: 36px;">
            <i class="fa-solid fa-xmark"></i>
        </a>
    </div>

    <form action="{{ route('posts.update', $post->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="row g-4">

            <div class="col-lg-6">
                <div class="mb-3">
                    <label for="title" class="form-label small fw-semibold text-dark">Nama Produk <span class="text-danger">*</span></label>
                    <input type="text" class="form-control rounded-3 py-2 fs-6 @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title', $post->title) }}" required style="background-color: #fafafa;">
                    @error('title')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="content" class="form-label small fw-semibold text-dark">Deskripsi & Bahan <span class="text-danger">*</span></label>
                    <textarea class="form-control rounded-3 py-2 fs-6 @error('content') is-invalid @enderror" id="content" name="content" rows="4" style="background-color: #fafafa;">{{ old('content', $post->content) }}</textarea>
                    @error('content')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-3">
                    <label for="category" class="form-label small fw-semibold text-dark">Kategori *</label>
                    <select id="category" name="category" class="form-select rounded-3" required>
                        @foreach(['Kopi Panas', 'Kopi Dingin', 'Non-Kopi', 'Makanan', 'Umum'] as $category)
                            <option value="{{ $category }}" @selected(old('category', $post->category) === $category)>{{ $category }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="row g-3">
                    <div class="col-md-6"><label for="price" class="form-label small fw-semibold">Harga *</label><input id="price" name="price" type="number" min="0" class="form-control rounded-3" value="{{ old('price', $post->price) }}" required></div>
                    <div class="col-md-6"><label for="stock_type" class="form-label small fw-semibold">Tipe Stok *</label><select id="stock_type" name="stock_type" class="form-select rounded-3" required><option value="bom" @selected(old('stock_type', $post->stock_type) === 'bom')>Resep (BoM)</option><option value="static" @selected(old('stock_type', $post->stock_type) === 'static')>Barang Statis</option><option value="none" @selected(old('stock_type', $post->stock_type) === 'none')>Tidak Dihitung</option></select></div>
                </div>
            </div>

        </div>
        <div class="form-check form-switch mt-3"><input class="form-check-input" type="checkbox" name="status" value="1" @checked(old('status', $post->is_active))><label class="form-check-label small">Produk aktif di kasir</label></div>

        <!-- FOOTER BUTTONS -->
        <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
            <a href="{{ route('posts.index') }}" class="btn btn-light rounded-3 px-4 fw-semibold text-dark" style="background-color: #f2f2f2; font-size: 13px;">Batal</a>
            <button type="submit" class="btn rounded-3 px-4 fw-semibold text-white" style="background-color: #7d7268; font-size: 13px;">
                Perbarui Produk
            </button>
        </div>
    </form>
</div>
@endsection
