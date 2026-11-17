<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

/* Masuk, daftar, keluar. */
Route::middleware('guest')->group(function () {
    Route::get('/masuk', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/masuk', [AuthController::class, 'login'])->middleware('throttle:10,1')->name('login.post');
    Route::get('/daftar', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/daftar', [AuthController::class, 'register'])->name('register.post');
});

Route::get('/keluar', [AuthController::class, 'logout'])->name('logout');
