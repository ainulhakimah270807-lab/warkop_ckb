<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <title>Lupa Kata Sandi - Warkop Cak Kebo ADMIN PANEL</title>
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
                <h2 class="fs-4 fw-bold mb-1 text-dark">Lupa Kata Sandi</h2>
                <p class="text-muted small mb-0" style="letter-spacing: 0.5px;">Masukkan email akun untuk menerima tautan reset kata sandi</p>
            </div>

            <!-- Session Status -->
            @if (session('status'))
                <div class="alert alert-success py-2 px-3 small rounded-3 mb-3" style="background-color: var(--pill-green-bg); color: var(--pill-green-text); border: none;">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}">
                @csrf

                <!-- Email Address -->
                <div class="warkop-input-group mb-4">
                    <label for="email" class="warkop-input-label">Alamat Email Terdaftar</label>
                    <input type="email" id="email" name="email" class="warkop-input @error('email') is-invalid @enderror" placeholder="nama@warkopcakkebo.com" value="{{ old('email') }}" required autofocus>
                    @error('email')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Submit Button -->
                <button type="submit" class="warkop-btn-primary mb-3">
                    <i class="fa-regular fa-paper-plane me-2"></i>
                    <span>Kirim Tautan Reset Sandi</span>
                </button>

                <!-- Footer Back to Login -->
                <div class="text-center mt-3 pt-3 border-top">
                    <p class="text-muted small mb-0">
                        Ingat kata sandi? <a href="{{ route('login') }}" class="fw-bold text-dark text-decoration-none">Kembali ke Login</a>
                    </p>
                </div>
            </form>
        </div>
    </div>
</body>

</html>
