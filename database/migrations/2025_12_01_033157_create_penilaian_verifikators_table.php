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
        Schema::create('penilaian_verifikator', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_verified')->nullable();
            $table->text('keterangan')->nullable();
            // $table->foreignId('penilaian_mandiri_id')->constrained('penilaian_mandiri')->restrictOnDelete();
            // $table->foreignId('opd_id')->constrained('opd')->restrictOnDelete();
            $table->foreignId('file_bukti_dukung_id')->constrained('file_bukti_dukung')->restrictOnDelete();
            $table->foreignId('role_id')->constrained('role')->restrictOnDelete();
            $table->boolean('is_perubahan');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penilaian_verifikator');
    }
};
