<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <title>@yield('title', 'Dashboard') - Warkop Cak Kebo</title>
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Customized Bootstrap Stylesheet -->
    <link href="{{ asset('dashmin/css/bootstrap.min.css') }}" rel="stylesheet">

    <!-- Warkop Cak Kebo Modern Theme Stylesheet -->
    <link href="{{ asset('dashmin/css/warkop-theme.css') }}" rel="stylesheet">
    @stack('styles')
</head>

<body>
    <div class="app-wrapper">
        <!-- Sidebar Start -->
        <aside class="warkop-sidebar" id="warkopSidebar">
            <!-- Brand -->
            <a href="{{ url('/admin') }}" class="warkop-brand">
                <div class="warkop-brand-logo">
                    <i class="fa-solid fa-mug-hot"></i>
                </div>
                <div>
                    <h1 class="warkop-brand-title">Warkop Cak Kebo</h1>
                    <span class="warkop-brand-subtitle">ADMIN PANEL</span>
                </div>
            </a>

            <!-- User Info -->
            <div class="warkop-user-card">
                <img class="warkop-user-avatar" src="{{ asset('dashmin/img/user.jpg') }}" alt="User Avatar">
                <div class="overflow-hidden">
                    <p class="warkop-user-name text-truncate">{{ Auth::user()->name ?? 'Pak Heru' }}</p>
                    <span class="warkop-user-role-badge">{{ Auth::user()->role ?? '—' }}</span>
                </div>
            </div>

            <!-- Menu Navigation -->
            <div class="warkop-nav-section">MENU UTAMA</div>
            <ul class="warkop-nav-menu">
                <li><a href="{{ route('owner.index') }}" class="warkop-nav-link {{ request()->is('owner*') ? 'active' : '' }}"><i class="fa-solid fa-link"></i><span>Pusat Owner · Aplikasi</span></a></li>
                <li>
                    <a href="{{ url('/admin') }}" class="warkop-nav-link {{ request()->is('admin') || request()->is('dashboard') ? 'active' : '' }}">
                        <i class="fa-solid fa-table-cells-large"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('posts.index') }}" class="warkop-nav-link {{ request()->is('posts*') ? 'active' : '' }}">
                        <i class="fa-solid fa-cube"></i>
                        <span>Master Produk</span>
                    </a>
                </li>
                <li>
                    <a href="{{ url('/ingredients') }}" class="warkop-nav-link {{ request()->is('ingredients*') ? 'active' : '' }}">
                        <i class="fa-solid fa-wheat-awn"></i>
                        <span>Master Bahan Baku</span>
                        <span class="warkop-badge-counter">{{ \App\Models\Ingredient::whereColumn('stock', '<=', 'minimum_stock')->count() }}</span>
                    </a>
                </li>
                <li>
                    <a href="{{ url('/purchases') }}" class="warkop-nav-link {{ request()->is('purchases*') ? 'active' : '' }}">
                        <i class="fa-solid fa-file-invoice-dollar"></i>
                        <span>Pembelian Supplier</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('reports.index') }}" class="warkop-nav-link {{ request()->is('reports*') ? 'active' : '' }}">
                        <i class="fa-solid fa-chart-pie"></i>
                        <span>Laporan</span>
                    </a>
                </li>
                <!-- MENU NOTIFIKASI DITAMBAHKAN DI SINI -->
                <li>
                    <a href="{{ route('notifications.index') }}" class="warkop-nav-link {{ request()->is('notifications*') ? 'active' : '' }}">
                        <i class="fa-solid fa-bell"></i>
                        <span>Notifikasi</span>
                        <span class="warkop-badge-counter">{{ \App\Models\Ingredient::whereColumn('stock', '<=', 'minimum_stock')->count() }}</span>
                    </a>
                </li>
            </ul>

            <div class="warkop-nav-section">SISTEM</div>
            <ul class="warkop-nav-menu">
                <li>
                    <a href="{{ route('settings.index') }}" class="warkop-nav-link {{ request()->is('settings*') ? 'active' : '' }}">
                        <i class="fa-solid fa-gear"></i>
                        <span>Pengaturan</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('owner.index', ['section' => 'employees']) }}" class="warkop-nav-link">
                        <i class="fa-solid fa-user-gear"></i>
                        <span>Akun & jabatan</span>
                    </a>
                </li>
            </ul>
        </aside>
        <!-- Sidebar End -->

        <!-- Content Area Start -->
        <div class="warkop-main-content">
            <!-- Topbar Start -->
            <header class="warkop-topbar">
                <div class="d-flex align-items-center gap-3">
                    <button type="button" class="btn btn-sm d-lg-none text-dark p-0 border-0" id="sidebarToggleBtn" style="font-size: 22px;">
                        <i class="fa-solid fa-bars"></i>
                    </button>
                    <span class="fw-bold fs-6 text-dark">@yield('page_title', 'Dashboard')</span>
                </div>

                <div class="warkop-search-box d-none d-md-flex">
                    <i class="fa-solid fa-magnifying-glass me-2 text-muted" style="font-size: 13px;"></i>
                    <input type="text" placeholder="Cari produk, bahan baku, supplier..." aria-label="Search">
                </div>

                <div class="warkop-topbar-actions">
                    <!-- Notification Button -->
                    <button class="warkop-btn-icon" title="Notifikasi" type="button" onclick="window.location.href='{{ route('notifications.index') }}'">
                        <i class="fa-solid fa-bell text-warning" style="font-size: 15px;"></i>
                    </button>

                    <!-- Keluar / Logout Form -->
                    <form method="POST" action="{{ route('logout') }}" class="d-inline">
                        @csrf
                        <button type="submit" class="warkop-btn-logout" title="Keluar">
                            <i class="fa-solid fa-arrow-right-from-bracket"></i>
                            <span>Keluar</span>
                        </button>
                    </form>
                </div>
            </header>
            <!-- Topbar End -->

            <!-- Body Content -->
            <main class="warkop-page-body">
                @if($errors->any())<div class="alert alert-danger"><strong>Periksa isian:</strong><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
                @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
                @yield('content')
            </main>
        </div>
        <!-- Content Area End -->
    </div>

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Mobile Sidebar Toggle Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toggleBtn = document.getElementById('sidebarToggleBtn');
            const sidebar = document.getElementById('warkopSidebar');

            if (toggleBtn && sidebar) {
                toggleBtn.addEventListener('click', function() {
                    sidebar.classList.toggle('show');
                });

                document.addEventListener('click', function(e) {
                    if (window.innerWidth < 992 && sidebar.classList.contains('show')) {
                        if (!sidebar.contains(e.target) && !toggleBtn.contains(e.target)) {
                            sidebar.classList.remove('show');
                        }
                    }
                });
            }
        });
    </script>
    @stack('scripts')
</body>

</html>
