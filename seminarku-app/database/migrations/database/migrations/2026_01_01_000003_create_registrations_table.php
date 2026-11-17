<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('ticket_code', 30)->unique();
            $table->string('status', 12)->default('menunggu');       // menunggu | disetujui | ditolak
            $table->string('payment_proof')->nullable();
            $table->dateTime('proof_at')->nullable();
            $table->string('reject_reason')->nullable();
            $table->boolean('attended')->default(false);
            $table->dateTime('attended_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['event_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registrations');
    }
};
