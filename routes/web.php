<?php

use App\Http\Controllers\{ProfileController, PostController, IngredientController, SupplierController, PosController, OwnerController, StockPurchaseController, ReportController};
use App\Http\Middleware\RequireOwner;
use App\Models\Ingredient;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('welcome'));
Route::get('/menu-images/{post}', [PosController::class, 'image'])->name('menu.image');
Route::get('/owner/mobile-login', [OwnerController::class, 'webLogin'])->middleware('throttle:10,1,owner-web-login');
Route::middleware(['auth', RequireOwner::class])->group(function () {
    Route::get('/dashboard', [OwnerController::class, 'index'])->name('dashboard');
    Route::get('/admin', [OwnerController::class, 'index'])->name('admin');
    Route::get('/owner', [OwnerController::class, 'index'])->name('owner.index');
    Route::get('/owner/sales/{sale}', [OwnerController::class, 'sale'])->name('owner.sale');
    Route::resource('posts', PostController::class)->except('show')->parameters(['posts' => 'id']);
    Route::patch('/posts/{post}/toggle', [PostController::class, 'toggle'])->name('posts.toggle');
    Route::get('/ingredients', [IngredientController::class, 'index'])->name('ingredients.index');
    Route::post('/ingredients', [IngredientController::class, 'store'])->name('ingredients.store');
    Route::put('/ingredients/{ingredient}', [IngredientController::class, 'update'])->name('ingredients.update');
    Route::delete('/ingredients/{ingredient}', [IngredientController::class, 'destroy'])->name('ingredients.destroy');
    Route::get('/purchases', [StockPurchaseController::class, 'index'])->name('purchases.index');
    Route::post('/purchases', [StockPurchaseController::class, 'store'])->name('purchases.store');
    Route::post('/suppliers', [SupplierController::class, 'store'])->name('suppliers.store');
    Route::put('/suppliers/{supplier}', [SupplierController::class, 'update'])->name('suppliers.update');
    Route::delete('/suppliers/{supplier}', [SupplierController::class, 'destroy'])->name('suppliers.destroy');
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');
    Route::get('/settings', [OwnerController::class, 'settings'])->name('settings.index');
    Route::post('/settings', [OwnerController::class, 'saveSettings'])->name('settings.update');
    Route::get('/notifications', fn () => view('owner.notifications', ['ingredients' => Ingredient::whereColumn('stock', '<=', 'minimum_stock')->get()]))->name('notifications.index');
    Route::get('/pos-status', [OwnerController::class, 'index'])->name('pos-status');
});
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});
require __DIR__.'/auth.php';
