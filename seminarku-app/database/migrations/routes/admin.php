<?php

use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FileController;
use Illuminate\Support\Facades\Route;

/* Beranda/kalender (semua peran yang sudah masuk). */
Route::get('/beranda', [DashboardController::class, 'index'])->middleware('app')->name('dashboard');

/* Khusus admin: kelola pengguna dan laporan. */
Route::middleware('app:admin')->prefix('admin')->group(function () {
    Route::get('/pengguna', [UserController::class, 'index'])->name('admin.users');
    Route::post('/pengguna', [UserController::class, 'action'])->name('admin.users.action');
    Route::get('/laporan', [ReportController::class, 'index'])->name('admin.reports');
    Route::get('/laporan/ekspor', [ReportController::class, 'export'])->name('admin.reports.export');
});

/* Pesan: hanya peserta dan panitia (admin TIDAK punya akses, demi privasi). */
Route::middleware('app:peserta,penyelenggara')->group(function () {
    Route::get('/pesan', [ChatController::class, 'index'])->name('chat.index');
    Route::post('/pesan', [ChatController::class, 'send'])->name('chat.send');
});
// Titik akhir JSON (polling/kirim/unread). Otentikasi diperiksa di controller agar balasan selalu JSON.
Route::match(['get', 'post'], '/pesan/api', [ChatController::class, 'api'])->name('chat.api');

/* Penyaji berkas unggahan (poster publik; QRIS dan bukti bayar dicek hak aksesnya di controller). */
Route::get('/berkas/{type}/{id}', [FileController::class, 'show'])
    ->where('type', 'poster|qris|proof')->whereNumber('id')->name('file');
