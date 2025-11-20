<?php
use App\Http\Controllers\Auth\LoginController;

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.post');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware(['auth', 'role:admin,owner'])->group(function () {
    // halaman admin
    Route::get('/', function () {
        return view('dashboard'); // halaman utama
    });
});
