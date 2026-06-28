<?php

use App\Http\Controllers\ProfileController;
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

require __DIR__.'/auth.php';
