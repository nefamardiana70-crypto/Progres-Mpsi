<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('email', 150)->unique();
            $table->string('password');
            $table->string('role', 20)->default('peserta');        // peserta | penyelenggara | admin
            $table->string('nim', 30)->nullable();
            $table->string('institution', 150)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('status', 20)->default('aktif');        // aktif | menunggu | nonaktif
            $table->rememberToken();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
