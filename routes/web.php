<?php

use App\Http\Controllers\Api\CheckLogIndexController;
use App\Http\Controllers\Api\DisposalLogController;
use App\Http\Controllers\Api\ExpiryCheckLogController;
use App\Http\Controllers\Api\ProcessCheckLogController;
use App\Http\Controllers\Api\ProductLookupController;
use App\Http\Controllers\Api\UncheckedAlertController;
use App\Http\Controllers\CheckLogListController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\BarcodeScanController;
use App\Http\Controllers\CsvImportController;
use App\Http\Controllers\ProductConfirmationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

if (! app()->environment('production')) {
    Route::post('/dev-login', function () {
        $user = User::firstOrCreate(
            ['email' => 'demo@example.com'],
            ['name' => '開発用デモユーザー', 'password' => bcrypt(str()->random(32))]
        );

        Auth::login($user);

        return redirect()->route('dashboard');
    })->name('dev-login');
}

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware('auth')->prefix('barcode-scan')->name('barcode-scan.')->group(function () {
    Route::get('/', [BarcodeScanController::class, 'create'])->name('create');
});

Route::middleware('auth')->prefix('api')->name('api.')->group(function () {
    Route::get('/products/lookup', [ProductLookupController::class, 'show'])->name('products.lookup');
    Route::get('/check-logs', [CheckLogIndexController::class, 'index'])->name('check-logs.index');
    Route::post('/check-logs', [ExpiryCheckLogController::class, 'store'])->name('check-logs.store');
    Route::patch('/check-logs/{log}/process', ProcessCheckLogController::class)->name('check-logs.process');
    Route::post('/check-logs/{log}/dispose', [DisposalLogController::class, 'store'])->name('check-logs.dispose');
    Route::get('/alerts/uncheck', [UncheckedAlertController::class, 'index'])->name('alerts.uncheck');
});

Route::middleware('auth')->prefix('check-logs')->name('check-logs.')->group(function () {
    Route::get('/', [CheckLogListController::class, 'index'])->name('index');
});

Route::middleware('auth')->prefix('products')->name('products.')->group(function () {
    Route::get('/confirm', [ProductConfirmationController::class, 'show'])->name('confirm');
});

Route::middleware(['auth', 'role:admin'])->prefix('users')->name('users.')->group(function () {
    Route::get('/', [UserController::class, 'index'])->name('index');
    Route::get('/create', [UserController::class, 'create'])->name('create');
    Route::post('/', [UserController::class, 'store'])->name('store');
    Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit');
    Route::put('/{user}', [UserController::class, 'update'])->name('update');
    Route::patch('/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('toggle-active');
});

Route::middleware(['auth', 'role:admin,hq_staff'])->prefix('csv-imports')->name('csv-imports.')->group(function () {
    Route::get('/create', [CsvImportController::class, 'create'])->name('create');
    Route::post('/preview', [CsvImportController::class, 'preview'])->name('preview');
    Route::post('/confirm', [CsvImportController::class, 'confirm'])->name('confirm');
});

require __DIR__.'/auth.php';
