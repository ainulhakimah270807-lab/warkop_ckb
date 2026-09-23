<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <title>Warkop Cak Kebo - Portal & Admin Panel</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">

    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Icon Font Stylesheet -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <!-- Bootstrap Stylesheet -->
    <link href="{{ asset('dashmin/css/bootstrap.min.css') }}" rel="stylesheet">

    <!-- Warkop Cak Kebo Modern Theme -->
    <link href="{{ asset('dashmin/css/warkop-theme.css') }}" rel="stylesheet">

    <style>
        .hero-banner {
            max-width: 580px;
            margin: auto;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="warkop-auth-wrapper flex-column justify-content-between py-5">
        <div class="container my-auto">
            <div class="hero-banner">
                <div class="warkop-auth-brand-badge mx-auto mb-3" style="width: 70px; height: 70px; font-size: 32px;">
                    <i class="fa-solid fa-mug-hot"></i>
                </div>
                <h1 class="display-6 fw-bold text-dark mb-2">Warkop Cak Kebo</h1>
                <p class="text-muted mb-4 fs-6">Sistem Manajemen & Point of Sales (POS) Modern</p>

                <div class="warkop-card p-4 text-center mb-4">
                    <p class="text-muted small mb-3">Pilih akses masuk untuk mengelola operasional warkop:</p>
                    <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center">
                        @auth
                            <a href="{{ url('/admin') }}" class="warkop-btn-primary">
                                <i class="fa-solid fa-table-cells-large me-2"></i>Buka Dashboard Admin
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="warkop-btn-primary">
                                <i class="fa-solid fa-right-to-bracket me-2"></i>Masuk ke Admin Panel
                            </a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="warkop-btn-period justify-content-center">
                                    <i class="fa-solid fa-user-plus me-1"></i>Daftar Akun Baru
                                </a>
                            @endif
                        @endauth
                    </div>
                </div>

                <div class="d-flex justify-content-center gap-4 text-muted small">
                    <span><i class="fa-solid fa-circle-check text-success me-1"></i>Shift & Omzet Realtime</span>
                    <span><i class="fa-solid fa-circle-check text-success me-1"></i>Manajemen Stok Kritis</span>
                    <span><i class="fa-solid fa-circle-check text-success me-1"></i>Ranking Top Menu</span>
                </div>
            </div>
        </div>

        <div class="text-center text-muted small mt-4">
            &copy; {{ date('Y') }} Warkop Cak Kebo. All rights reserved.
        </div>
    </div>
</body>

</html>
