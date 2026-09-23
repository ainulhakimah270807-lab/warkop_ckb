@extends('layouts.dashmin')

@section('title', 'Manajemen User & Profile - Warkop Cak Kebo')
@section('page_title', 'Manajemen User')

@section('content')
<!-- Dashboard Title & Action -->
<div class="warkop-dashboard-header">
    <div>
        <h1 class="warkop-page-title">Profil Akun Administrator</h1>
        <p class="warkop-page-subtitle">Kelola informasi profil, kata sandi, dan keamanan akun Anda</p>
    </div>
    <div>
        <a href="{{ url('/admin') }}" class="warkop-btn-period">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Kembali ke Dashboard</span>
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- Left Column: Profil & Ubah Password -->
    <div class="col-12 col-lg-7">
        <!-- 1. Informasi Profil -->
        <div class="warkop-card mb-4">
            <div class="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom">
                <img class="warkop-user-avatar" src="{{ asset('dashmin/img/user.jpg') }}" alt="User Avatar" style="width: 54px; height: 54px;">
                <div>
                    <h2 class="fs-6 fw-bold mb-1 text-dark">Informasi Akun</h2>
                    <p class="text-muted small mb-0">Perbarui nama lengkap dan alamat email akun Anda</p>
                </div>
            </div>

            @if (session('status') === 'profile-updated')
                <div class="alert alert-success d-flex align-items-center gap-2 border-0 shadow-sm rounded-3 mb-4" style="background-color: var(--pill-green-bg); color: var(--pill-green-text);">
                    <i class="fa-solid fa-circle-check"></i>
                    <span>Informasi profil berhasil diperbarui.</span>
                </div>
            @endif

            <form method="post" action="{{ route('profile.update') }}">
                @csrf
                @method('patch')

                <!-- Nama -->
                <div class="warkop-input-group">
                    <label for="name" class="warkop-input-label">Nama Lengkap</label>
                    <input type="text" id="name" name="name" class="warkop-input @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}" required autofocus autocomplete="name">
                    @error('name')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Email -->
                <div class="warkop-input-group mb-4">
                    <label for="email" class="warkop-input-label">Alamat Email</label>
                    <input type="email" id="email" name="email" class="warkop-input @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}" required autocomplete="username">
                    @error('email')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror

                    @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                        <div class="mt-2">
                            <p class="small text-muted mb-0">
                                Email Anda belum diverifikasi.
                                <button form="send-verification" class="btn btn-link p-0 small text-decoration-none">
                                    Kirim ulang email verifikasi.
                                </button>
                            </p>
                        </div>
                    @endif
                </div>

                <button type="submit" class="warkop-btn-primary" style="width: auto; padding: 10px 24px;">
                    <i class="fa-solid fa-floppy-disk me-2"></i>
                    <span>Simpan Perubahan</span>
                </button>
            </form>
        </div>

        <!-- 2. Ubah Kata Sandi -->
        <div class="warkop-card">
            <div class="mb-4 pb-3 border-bottom">
                <h2 class="fs-6 fw-bold mb-1 text-dark">Perbarui Kata Sandi</h2>
                <p class="text-muted small mb-0">Pastikan akun Anda menggunakan kata sandi yang kuat dan aman</p>
            </div>

            @if (session('status') === 'password-updated')
                <div class="alert alert-success d-flex align-items-center gap-2 border-0 shadow-sm rounded-3 mb-4" style="background-color: var(--pill-green-bg); color: var(--pill-green-text);">
                    <i class="fa-solid fa-circle-check"></i>
                    <span>Kata sandi berhasil diperbarui.</span>
                </div>
            @endif

            <form method="post" action="{{ route('password.update') }}">
                @csrf
                @method('put')

                <!-- Current Password -->
                <div class="warkop-input-group">
                    <label for="current_password" class="warkop-input-label">Kata Sandi Saat Ini</label>
                    <input type="password" id="current_password" name="current_password" class="warkop-input @if($errors->updatePassword->has('current_password')) is-invalid @endif" placeholder="••••••••" autocomplete="current-password">
                    @if($errors->updatePassword->has('current_password'))
                        <div class="text-danger small mt-1">{{ $errors->updatePassword->first('current_password') }}</div>
                    @endif
                </div>

                <!-- New Password -->
                <div class="warkop-input-group">
                    <label for="password" class="warkop-input-label">Kata Sandi Baru</label>
                    <input type="password" id="password" name="password" class="warkop-input @if($errors->updatePassword->has('password')) is-invalid @endif" placeholder="Minimal 8 karakter" autocomplete="new-password">
                    @if($errors->updatePassword->has('password'))
                        <div class="text-danger small mt-1">{{ $errors->updatePassword->first('password') }}</div>
                    @endif
                </div>

                <!-- Confirm Password -->
                <div class="warkop-input-group mb-4">
                    <label for="password_confirmation" class="warkop-input-label">Konfirmasi Kata Sandi Baru</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" class="warkop-input @if($errors->updatePassword->has('password_confirmation')) is-invalid @endif" placeholder="Ulangi kata sandi baru" autocomplete="new-password">
                    @if($errors->updatePassword->has('password_confirmation'))
                        <div class="text-danger small mt-1">{{ $errors->updatePassword->first('password_confirmation') }}</div>
                    @endif
                </div>

                <button type="submit" class="warkop-btn-primary" style="width: auto; padding: 10px 24px;">
                    <i class="fa-solid fa-key me-2"></i>
                    <span>Perbarui Kata Sandi</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Right Column: Info Akun & Hapus Akun -->
    <div class="col-12 col-lg-5">
        <!-- Detail Info Role & Shift -->
        <div class="warkop-card mb-4">
            <div class="mb-3">
                <h2 class="fs-6 fw-bold mb-1 text-dark">Ringkasan Hak Akses</h2>
                <p class="text-muted small mb-0">Informasi peran dan kredensial pengguna</p>
            </div>

            <div class="p-3 rounded-3 mb-3" style="background-color: #FAF6F0; border: 1px solid var(--border-color);">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small">Status Akun:</span>
                    <span class="badge-pill-custom badge-pill-lunas">
                        <span class="badge-dot"></span> Aktif
                    </span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small">Peran Pengguna:</span>
                    <span class="warkop-user-role-badge" style="background: var(--sidebar-bg); color: #FFF;">Owner / Administrator</span>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted small">Shift Kerja:</span>
                    <span class="fw-bold text-dark small">Pagi (06:00 – 14:00)</span>
                </div>
            </div>

            <ul class="text-muted small ps-3 mb-0">
                <li class="mb-1">Dapat mengakses dan mengelola seluruh menu & produk warkop.</li>
                <li class="mb-1">Dapat melihat dan memfilter grafik omzet mingguan / bulanan.</li>
                <li>Dapat memantau peringatan stok bahan baku kritis.</li>
            </ul>
        </div>

        <!-- Hapus Akun -->
        <div class="warkop-card" style="border-color: rgba(214, 48, 49, 0.2);">
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="text-danger"><i class="fa-solid fa-triangle-exclamation"></i></span>
                <h2 class="fs-6 fw-bold mb-0 text-danger">Hapus Akun</h2>
            </div>
            <p class="text-muted small mb-3">
                Setelah akun Anda dihapus, semua data dan riwayat transaksi terkait akun ini akan dihapus permanen.
            </p>

            <button type="button" class="btn btn-sm py-2 px-3" data-bs-toggle="modal" data-bs-target="#confirmDeleteModal" style="background-color: var(--pill-red-bg); color: var(--pill-red-text); border: 1px solid rgba(214, 48, 49, 0.3); border-radius: 10px; font-weight: 600;">
                <i class="fa-solid fa-trash-can me-2"></i>Hapus Akun Saya
            </button>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi Hapus Akun -->
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header border-0 pb-0 pt-4 px-4">
                <h5 class="modal-title fw-bold text-dark" id="confirmDeleteModalLabel">
                    <i class="fa-solid fa-triangle-exclamation text-danger me-2"></i>Konfirmasi Hapus Akun
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="{{ route('profile.destroy') }}">
                @csrf
                @method('delete')
                <div class="modal-body px-4 py-3">
                    <p class="text-muted small mb-3">
                        Apakah Anda yakin ingin menghapus akun ini? Silakan masukkan kata sandi Anda untuk mengonfirmasi.
                    </p>
                    <div class="warkop-input-group mb-0">
                        <label for="modal_password" class="warkop-input-label">Kata Sandi</label>
                        <input type="password" id="modal_password" name="password" class="warkop-input @if($errors->userDeletion->has('password')) is-invalid @endif" placeholder="Masukkan kata sandi Anda" required>
                        @if($errors->userDeletion->has('password'))
                            <div class="text-danger small mt-1">{{ $errors->userDeletion->first('password') }}</div>
                        @endif
                    </div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4 pt-0">
                    <button type="button" class="warkop-btn-period" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn py-2 px-3 text-white" style="background-color: #D63031; border-radius: 12px; font-weight: 600; font-size: 13px;">
                        Ya, Hapus Akun
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
