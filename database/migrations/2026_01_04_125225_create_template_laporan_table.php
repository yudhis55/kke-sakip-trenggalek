<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('template_laporan', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100);
            $table->json('konten'); // Format: {"deskripsi": {...}, "catatan": {...}, "rekomendasi": {...}}
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('template_laporan');
    }
};
