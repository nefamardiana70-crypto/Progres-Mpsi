<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Pengguna yang sudah masuk dan membuka halaman tamu (masuk/daftar) diarahkan ke beranda
        $middleware->redirectUsersTo(fn () => route('dashboard'));
        $middleware->alias([
            'app' => \App\Http\Middleware\AppAuth::class,
            'organizer' => \App\Http\Middleware\OrganizerOnly::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
