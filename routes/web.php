<?php

use App\Http\Controllers\CsvImportController;
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

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
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
});

require __DIR__.'/auth.php';
