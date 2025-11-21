<?php

use Illuminate\Support\Facades\Route;

// DASHBOARD UTAMA (hanya butuh login)
Route::middleware(['auth'])->group(function () {

    Route::get('/dashboard', function () {
        return view('dashboard.index'); // halaman dashboard utama
    })->name('dashboard');

    // Halaman khusus ADMIN / OWNER
    Route::middleware(['role:admin,owner'])->group(function () {
        Route::get('/admin', function () {
            return view('welcome'); // halaman admin khusus
        })->name('admin.home');

        // Kalau mau tetap pakai '/' sebagai home admin:
        Route::get('/', function () {
            return view('welcome');
        })->name('home');
    });
});
