<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Fungsi bantu global (format tanggal, ikon, aturan tiket, unggahan, chat, dll.)
        require_once app_path('Support/helpers.php');
    }

    public function boot(): void
    {
        //
    }
}
