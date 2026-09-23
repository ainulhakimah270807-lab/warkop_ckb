<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <title>Login - Warkop Cak Kebo ADMIN PANEL</title>
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
                <p class="text-muted small mb-0" style="letter-spacing: 0.5px;">ADMIN PANEL — Masuk ke Sistem</p>
            </div>

            <!-- Session Status -->
            @if (session('status'))
                <div class="alert alert-success py-2 px-3 small rounded-3 mb-3">
                    {{ session('status') }}
                </div>
            @endif

            <!-- Login Form -->
            <form method="POST" action="{{ route('login') }}">
                @csrf

                <!-- Email Address -->
                <div class="warkop-input-group">
                    <label for="email" class="warkop-input-label">Alamat Email</label>
                    <div class="position-relative">
                        <input type="email" id="email" name="email" class="warkop-input @error('email') is-invalid @enderror" placeholder="nama@warkopcakkebo.com" value="{{ old('email') }}" required autofocus autocomplete="username">
                    </div>
                    @error('email')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Password -->
                <div class="warkop-input-group">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <label for="password" class="warkop-input-label mb-0">Kata Sandi</label>
                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="text-decoration-none small text-muted" style="font-size: 12px;">Lupa Sandi?</a>
                        @endif
                    </div>
                    <div class="position-relative">
                        <input type="password" id="password" name="password" class="warkop-input @error('password') is-invalid @enderror" placeholder="••••••••" required autocomplete="current-password">
                    </div>
                    @error('password')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Remember Me -->
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <div class="form-check">
                        <input type="checkbox" name="remember" class="form-check-input" id="rememberMe" style="cursor: pointer;">
                        <label class="form-check-label text-muted small" for="rememberMe" style="cursor: pointer;">Ingat Saya</label>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="warkop-btn-primary mb-3">
                    <span>Masuk ke Sistem</span>
                    <i class="fa-solid fa-arrow-right ms-2" style="font-size: 12px;"></i>
                </button>

                <!-- Footer Sign Up Link -->
                @if (Route::has('register'))
                    <div class="text-center mt-3 pt-3 border-top">
                        <p class="text-muted small mb-0">
                            Belum punya akun? <a href="{{ route('register') }}" class="fw-bold text-dark text-decoration-none">Daftar Akun Baru</a>
                        </p>
                    </div>
                @endif
            </form>
        </div>
    </div>
</body>

</html>
