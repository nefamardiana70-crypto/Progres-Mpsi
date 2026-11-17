<?php

use App\Http\Controllers\Organizer\BrowseController;
use App\Http\Controllers\Organizer\EventController;
use App\Http\Controllers\Organizer\ParticipantController;
use App\Http\Controllers\Organizer\ScanController;
use Illuminate\Support\Facades\Route;

/*
 * Rute panitia. Middleware 'organizer' = penyelenggara yang sudah divalidasi admin, atau admin.
 * Semua parameter tunggal bernama {id}.
 */
Route::middleware('organizer')->prefix('panitia')->group(function () {
    // Daftar, buat, ubah, hapus acara
    Route::get('/acara', [EventController::class, 'index'])->name('org.events');
    Route::get('/acara/buat', [EventController::class, 'create'])->name('org.event.create');
    Route::post('/acara', [EventController::class, 'store'])->name('org.event.store');
    Route::get('/acara/{id}/ubah', [EventController::class, 'edit'])->whereNumber('id')->name('org.event.edit');
    Route::post('/acara/{id}', [EventController::class, 'update'])->whereNumber('id')->name('org.event.update');
    Route::post('/acara/{id}/hapus', [EventController::class, 'destroy'])->whereNumber('id')->name('org.event.delete');

    // Peserta acara: daftar, ekspor CSV, aksi (setujui/tolak/hadir/hapus/kode presensi baru)
    Route::get('/acara/{id}/peserta', [ParticipantController::class, 'show'])->whereNumber('id')->name('org.participants');
    Route::get('/acara/{id}/peserta/csv', [ParticipantController::class, 'csv'])->whereNumber('id')->name('org.participants.csv');
    Route::post('/acara/{id}/peserta/aksi', [ParticipantController::class, 'action'])->whereNumber('id')->name('org.participants.action');

    // Jelajah acara semua penyelenggara + titik cek tanggal untuk formulir acara
    Route::get('/jelajah', [BrowseController::class, 'index'])->name('org.browse');
    Route::get('/jelajah/cek', [BrowseController::class, 'check'])->name('org.browse.check');

    // Presensi (pemindai QR / kode manual); POST mengembalikan JSON
    Route::get('/presensi', [ScanController::class, 'index'])->name('org.scan');
    Route::post('/presensi', [ScanController::class, 'checkin'])->name('org.scan.checkin');
});
