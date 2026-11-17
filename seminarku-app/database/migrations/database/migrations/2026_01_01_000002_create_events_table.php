<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organizer_id')->constrained('users');
            $table->string('title', 200);
            $table->text('description');
            $table->string('category', 50);
            $table->date('event_date')->index();
            $table->string('start_time', 5);
            $table->date('reg_deadline')->nullable();
            $table->string('location', 200);
            $table->string('mode', 10);                              // online | offline
            $table->unsignedInteger('fee')->default(0);
            $table->unsignedInteger('quota');
            $table->boolean('has_certificate')->default(true);
            $table->string('status', 10)->default('terbit');         // draft | terbit | ditutup
            $table->string('presence_code', 6);
            $table->string('approval_mode', 10)->default('manual');  // manual | auto
            $table->text('payment_info')->nullable();
            $table->string('qris_image')->nullable();
            $table->string('poster')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
