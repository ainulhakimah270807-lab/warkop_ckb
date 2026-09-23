<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <title>Daftar Akun - Warkop Cak Kebo ADMIN PANEL</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Favicon -->
    <link href="{{ asset('dashmin/img/favicon.ico') }}" rel="icon">

    <!-- Google Web Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Icon Font Stylesheet -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <!-- Customized Bootstrap Stylesheet -->
    <link href="{{ asset('dashmin/css/bootstrap.min.css') }}" rel="stylesheet">

    <!-- Warkop Cak Kebo Modern Theme Stylesheet -->
    <link href="{{ asset('dashmin/css/warkop-theme.css') }}" rel="stylesheet">
</head>

<body>
    <div class="warkop-auth-wrapper">
        <div class="warkop-auth-card">
            <!-- Brand Logo & Header -->
            <div class="text-center mb-4">
                <div class="warkop-auth-brand-badge">
                    <i class="fa-solid fa-mug-hot"></i>
                </div>
                <h2 class="fs-4 fw-bold mb-1 text-dark">Warkop Cak Kebo</h2>
                <p class="text-muted small mb-0" style="letter-spacing: 0.5px;">ADMIN PANEL — Buat Akun Baru</p>
            </div>

            <!-- Register Form -->
            <form method="POST" action="{{ route('register') }}">
                @csrf

                <!-- Name -->
                <div class="warkop-input-group">
                    <label for="name" class="warkop-input-label">Nama Lengkap</label>
                    <input type="text" id="name" name="name" class="warkop-input @error('name') is-invalid @enderror" placeholder="Contoh: Pak Heru" value="{{ old('name') }}" required autofocus autocomplete="name">
                    @error('name')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Email Address -->
                <div class="warkop-input-group">
                    <label for="email" class="warkop-input-label">Alamat Email</label>
                    <input type="email" id="email" name="email" class="warkop-input @error('email') is-invalid @enderror" placeholder="nama@warkopcakkebo.com" value="{{ old('email') }}" required autocomplete="username">
                    @error('email')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Password -->
                <div class="warkop-input-group">
                    <label for="password" class="warkop-input-label">Kata Sandi</label>
                    <input type="password" id="password" name="password" class="warkop-input @error('password') is-invalid @enderror" placeholder="Minimal 8 karakter" required autocomplete="new-password">
                    @error('password')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Confirm Password -->
                <div class="warkop-input-group mb-4">
                    <label for="password_confirmation" class="warkop-input-label">Konfirmasi Kata Sandi</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" class="warkop-input @error('password_confirmation') is-invalid @enderror" placeholder="Ulangi kata sandi" required autocomplete="new-password">
                    @error('password_confirmation')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Submit Button -->
                <button type="submit" class="warkop-btn-primary mb-3">
                    <span>Daftar Akun</span>
                    <i class="fa-solid fa-user-plus ms-2" style="font-size: 12px;"></i>
                </button>

                <!-- Footer Sign In Link -->
                <div class="text-center mt-3 pt-3 border-top">
                    <p class="text-muted small mb-0">
                        Sudah punya akun? <a href="{{ route('login') }}" class="fw-bold text-dark text-decoration-none">Masuk Sekarang</a>
                    </p>
                </div>
            </form>
        </div>
    </div>
</body>

</html>
