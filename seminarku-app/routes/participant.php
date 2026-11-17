<?php

use App\Http\Controllers\CertificateController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TicketController;
use Illuminate\Support\Facades\Route;

/* ---- Halaman publik ---- */
Route::get('/', [HomeController::class, 'home'])->name('home');
Route::get('/ketentuan', [HomeController::class, 'ketentuan'])->name('ketentuan');
Route::get('/verifikasi', [HomeController::class, 'verify'])->name('verify');

/* ---- Semua peran yang sudah masuk ---- */
Route::middleware('app')->group(function () {
    Route::get('/acara', [EventController::class, 'index'])->name('events.index');
    Route::get('/acara/{id}', [EventController::class, 'show'])->whereNumber('id')->name('events.show');
    Route::post('/acara/{id}/daftar', [EventController::class, 'register'])->whereNumber('id')->name('events.register');

    Route::get('/profil', [ProfileController::class, 'show'])->name('profile');
    Route::post('/profil', [ProfileController::class, 'update'])->name('profile.update');

    // PDF: pemilik, penyelenggara acara tersebut, atau admin (dicek di controller)
    Route::get('/sertifikat/{id}/unduh', [CertificateController::class, 'pdf'])->whereNumber('id')->name('certs.pdf');
});

/* ---- Khusus peserta ---- */
Route::middleware('app:peserta')->group(function () {
    Route::get('/tiket', [TicketController::class, 'index'])->name('tickets.index');
    Route::get('/tiket/{id}', [TicketController::class, 'show'])->whereNumber('id')->name('tickets.show');
    Route::post('/tiket/{id}/bukti', [TicketController::class, 'proof'])->whereNumber('id')->name('tickets.proof');
    Route::post('/tiket/{id}/presensi', [TicketController::class, 'presence'])->whereNumber('id')->name('tickets.presence');
    Route::post('/tiket/{id}/batal', [TicketController::class, 'cancel'])->whereNumber('id')->name('tickets.cancel');

    Route::get('/sertifikat', [CertificateController::class, 'index'])->name('certs.index');
});
