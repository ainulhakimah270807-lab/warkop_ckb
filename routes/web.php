<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\IngredientController;
use App\Http\Controllers\SupplierController;
use App\Models\Ingredient;
use App\Models\Post;
use App\Models\Supplier;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - Warkop Cak Kebo
|--------------------------------------------------------------------------
*/

// Halaman Utama Welcome
Route::get('/', function () {
    return view('welcome');
});

// 1. RUTE DASHBOARD & ADMIN (DILINDUNGI AUTH BREEZE)
Route::get('/dashboard', function () {
    return view('admin', ['productCount' => Post::count(), 'ingredientCount' => Ingredient::count()]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/admin', function () {
    return view('admin', ['productCount' => Post::count(), 'ingredientCount' => Ingredient::count()]);
})->middleware(['auth', 'verified'])->name('admin');

// 2. RUTE MASTER PRODUK (CRUD)
Route::get('/posts', [PostController::class, 'index'])->name('posts.index');
Route::get('/posts/create', [PostController::class, 'create'])->name('posts.create');
Route::post('/posts', [PostController::class, 'store'])->name('posts.store');
Route::get('/posts/{id}/edit', [PostController::class, 'edit'])->name('posts.edit');
Route::put('/posts/{id}', [PostController::class, 'update'])->name('posts.update');
Route::delete('/posts/{id}', [PostController::class, 'destroy'])->name('posts.destroy');
Route::patch('/posts/{post}/toggle', [PostController::class, 'toggle'])->name('posts.toggle');

// 3. RUTE MASTER BAHAN BAKU
Route::get('/ingredients', [IngredientController::class, 'index'])->name('ingredients.index');
Route::post('/ingredients', [IngredientController::class, 'store'])->name('ingredients.store');
Route::put('/ingredients/{ingredient}', [IngredientController::class, 'update'])->name('ingredients.update');
Route::delete('/ingredients/{ingredient}', [IngredientController::class, 'destroy'])->name('ingredients.destroy');

// 4. RUTE PEMBELIAN SUPPLIER
Route::get('/purchases', function () {
    $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();
    return view('purchases.index', compact('suppliers'));
})->name('purchases.index');

Route::post('/purchases', function () {
    return back()->with('success', 'Pembelian berhasil disimpan dan stok diperbarui.');
})->name('purchases.store');

Route::post('/suppliers', [SupplierController::class, 'store'])->name('suppliers.store');
Route::put('/suppliers/{supplier}', [SupplierController::class, 'update'])->name('suppliers.update');
Route::delete('/suppliers/{supplier}', [SupplierController::class, 'destroy'])->name('suppliers.destroy');

// 5. RUTE NOTIFIKASI (BARU)
Route::get('/notifications', function () {
    return view('notifications.index');
})->name('notifications.index');

// 6. RUTE LAPORAN
Route::get('/reports', function () {
    $period = request('period', '7_hari');
    $reports = collect();

    return view('reports.index', compact('period', 'reports'));
})->name('reports.index');

Route::get('/reports/export', function () {
    $rows = [['Tanggal', 'Transaksi', 'Omzet', 'Rata-rata Transaksi']];

    return response()->streamDownload(function () use ($rows) {
        $file = fopen('php://output', 'w');
        foreach ($rows as $row) {
            fputcsv($file, $row);
        }
        fclose($file);
    }, 'laporan-penjualan.csv', ['Content-Type' => 'text/csv']);
})->name('reports.export');

// 7. RUTE PENGATURAN
Route::get('/settings', function () {
    $settings = session('warkop_settings', [
        'store_name' => 'Warkop Cak Kebo',
        'store_address' => 'Jl. Kedung Cowek No. 10, Surabaya',
        'receipt_footer' => 'Terima kasih sudah mampir!',
        'low_stock_alert' => true,
        'auto_print' => false,
    ]);

    return view('settings.index', compact('settings'));
})->name('settings.index');

Route::post('/settings', function () {
    session(['warkop_settings' => [
        'store_name' => request('store_name'),
        'store_address' => request('store_address'),
        'receipt_footer' => request('receipt_footer'),
        'low_stock_alert' => request()->boolean('low_stock_alert'),
        'auto_print' => request()->boolean('auto_print'),
    ]]);

    return back()->with('success', 'Pengaturan berhasil disimpan.');
})->name('settings.update');

// 8. RUTE MONITORING STATUS TOKO
Route::get('/pos-status', function () {
    $jam = request('jam', 10);
    $namaKasir = request('nama', 'Pengguna');
    $isBuka = ($jam >= 8 && $jam <= 21);

    return view('pos-status', compact('jam', 'namaKasir', 'isBuka'));
})->name('pos-status');

// 7. RUTE PROFIL PENGGUNA
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
