<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\{PosController, OwnerController};
use App\Http\Middleware\RequireOwner;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('mobile/server-info', function () {
    return response()->json([
        'app' => 'warkop-ckb', 'protocol' => 1,
        'instance_id' => hash('sha256', (string) config('app.key')),
    ])->header('Cache-Control', 'no-store');
})->middleware('throttle:30,1,server-info');

Route::post('mobile/login', [AttendanceController::class, 'login'])->middleware('throttle:5,1,mobile-login');
Route::middleware(['auth:sanctum'])->prefix('mobile')->group(function () {
    Route::get('pos/catalog', [PosController::class, 'catalog']);
    Route::post('pos/checkout', [PosController::class, 'checkout'])->middleware('throttle:30,1,pos-checkout');
    Route::get('pos/sales/{sale}', [PosController::class, 'receipt']);
    Route::middleware(RequireOwner::class)->prefix('owner')->group(function () {
        Route::get('overview', [OwnerController::class, 'apiOverview']);
        Route::get('data/{section}', [OwnerController::class, 'apiData']);
        Route::post('web-ticket', [OwnerController::class, 'ticket'])->middleware('throttle:10,1,owner-ticket');
    });
    Route::post('logout', [AttendanceController::class, 'logout']);
    Route::get('attendance/status', [AttendanceController::class, 'status'])->middleware('throttle:30,1,attendance-status');
    Route::post('attendance/location', [AttendanceController::class, 'checkLocation'])->middleware('throttle:30,1,attendance-location');
    Route::post('attendance', [AttendanceController::class, 'store'])->middleware('throttle:6,1,attendance-submit');
    Route::get('attendance/employees', [AttendanceController::class, 'employees']);
    Route::post('attendance/outlet', [AttendanceController::class, 'saveOutlet'])->middleware('throttle:30,1,attendance-outlet');
    Route::post('attendance/faces/{user}', [AttendanceController::class, 'enroll'])->middleware('throttle:6,1,attendance-enroll');
});
