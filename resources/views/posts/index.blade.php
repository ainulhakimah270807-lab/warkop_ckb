@extends('layouts.dashmin')

@section('title', 'Master Produk - Warkop Cak Kebo')
@section('page_title', 'Master Produk')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <div>
        <h1 class="h3 fw-bold text-dark mb-1">Katalog Master Produk</h1>
        <p class="text-muted small mb-0">{{ $posts->where('is_active', true)->count() }} produk aktif dari {{ $posts->count() }} produk.</p>
    </div>
    <button type="button" class="btn text-white px-3 py-2 rounded-3 shadow-sm fw-semibold" style="background-color: #23120b; font-size: 14px;" data-bs-toggle="modal" data-bs-target="#modalTambahProduk"><i class="fa-solid fa-plus me-1"></i> Tambah Produk</button>
</div>

@if(session('success'))
<div class="alert alert-success d-flex align-items-center gap-2 border-0 shadow-sm rounded-3 mb-4"><i class="fa-solid fa-circle-check"></i>{{ session('success') }}</div>
@endif

<div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
    <form method="GET" action="{{ route('posts.index') }}" class="row g-2 align-items-end mb-4">
        <div class="col-md-5"><label for="search" class="form-label small fw-semibold">Cari produk atau kategori</label><input id="search" name="search" value="{{ request('search') }}" type="search" class="form-control form-control-sm rounded-3" placeholder="Nama produk..."></div>
        <div class="col-md-4"><label for="category" class="form-label small fw-semibold">Kategori</label><select id="category" name="category" class="form-select form-select-sm rounded-3"><option value="">Semua Kategori</option>@foreach($categories as $category)<option value="{{ $category }}" @selected(request('category') === $category)>{{ $category }}</option>@endforeach</select></div>
        <div class="col-md-3 d-flex gap-2"><button type="submit" class="btn btn-sm text-white rounded-3 flex-grow-1" style="background-color:#23120b"><i class="fa-solid fa-filter me-1"></i> Terapkan</button><a href="{{ route('posts.index') }}" class="btn btn-sm btn-light border rounded-3">Reset</a></div>
    </form>

    <div class="table-responsive">
        <table class="table table-borderless align-middle" style="font-size: 13px;">
            <thead><tr class="text-uppercase text-muted border-bottom" style="font-size: 11px; letter-spacing: .5px;"><th class="pb-3">NAMA MENU</th><th class="pb-3">KATEGORI</th><th class="pb-3">HARGA</th><th class="pb-3">TIPE STOK</th><th class="pb-3">STATUS</th><th class="pb-3 text-center">AKSI</th></tr></thead>
            <tbody>
            @forelse($posts as $post)
                <tr class="border-bottom">
                    <td class="fw-bold text-dark">{{ $post->title }}<small class="d-block text-muted fw-normal">{{ $post->content ?: 'Tanpa deskripsi' }}</small></td>
                    <td><span class="badge rounded-pill fw-normal text-secondary px-3 py-1" style="background:#f3f4f6">{{ $post->category }}</span></td>
                    <td class="fw-bold text-dark">Rp {{ number_format($post->price, 0, ',', '.') }}</td>
                    <td class="text-muted">{{ $post->stock_type === 'bom' ? 'Resep (BoM)' : ($post->stock_type === 'static' ? 'Barang Statis' : 'Tidak Dihitung') }}</td>
                    <td><span class="badge rounded-pill px-3 py-1 {{ $post->is_active ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">● {{ $post->is_active ? 'Tersedia' : 'Nonaktif' }}</span></td>
                    <td class="text-center"><div class="d-flex justify-content-center gap-1">
                        <form method="POST" action="{{ route('posts.toggle', $post) }}">@csrf @method('PATCH')<button type="submit" class="btn btn-sm btn-light border rounded-2" title="{{ $post->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">{{ $post->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button></form>
                        <a href="{{ route('posts.edit', $post->id) }}" class="btn btn-sm btn-light border rounded-2"><i class="fa-regular fa-pen-to-square me-1"></i>Edit</a>
                        <form method="POST" action="{{ route('posts.destroy', $post->id) }}" onsubmit="return confirm('Hapus produk ini?')">@csrf @method('DELETE')<button type="submit" class="btn btn-sm btn-light border rounded-2 text-danger"><i class="fa-regular fa-trash-can"></i></button></form>
                    </div></td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center py-5"><i class="fa-solid fa-box-open text-muted fs-2 mb-2"></i><p class="text-muted mb-0">Belum ada produk. Tambahkan produk pertama untuk memulai.</p></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="modalTambahProduk" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content rounded-4 border-0 shadow-lg p-3"><div class="modal-header"><div><h5 class="modal-title fw-bold">Tambah Produk Baru</h5><p class="text-muted small mb-0">Data akan disimpan ke katalog produk.</p></div><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><form action="{{ route('posts.store') }}" method="POST">@csrf<div class="modal-body"><div class="row g-3"><div class="col-md-6"><label class="form-label small fw-semibold">Nama Produk *</label><input name="title" class="form-control rounded-3" required></div><div class="col-md-6"><label class="form-label small fw-semibold">Kategori *</label><select name="category" class="form-select rounded-3" required><option value="Kopi Panas">Kopi Panas</option><option value="Kopi Dingin">Kopi Dingin</option><option value="Non-Kopi">Non-Kopi</option><option value="Makanan">Makanan</option></select></div><div class="col-md-6"><label class="form-label small fw-semibold">Harga *</label><input type="number" name="price" min="0" class="form-control rounded-3" required></div><div class="col-md-6"><label class="form-label small fw-semibold">Tipe Stok *</label><select name="stock_type" class="form-select rounded-3" required><option value="bom">Resep (BoM)</option><option value="static">Barang Statis</option><option value="none">Tidak Dihitung</option></select></div><div class="col-12"><label class="form-label small fw-semibold">Deskripsi</label><textarea name="content" rows="3" class="form-control rounded-3"></textarea></div><div class="col-12"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="status" value="1" checked><label class="form-check-label small">Produk aktif di kasir</label></div></div></div></div><div class="modal-footer"><button type="button" class="btn btn-light border rounded-3" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn text-white rounded-3" style="background:#23120b"><i class="fa-solid fa-floppy-disk me-1"></i>Simpan Produk</button></div></form></div></div></div>
@endsection
