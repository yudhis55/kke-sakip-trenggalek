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
        Schema::create('penilaian_history', function (Blueprint $table) {
            $table->id();

            // Foreign Keys - Relasi
            $table->foreignId('penilaian_id')->constrained('penilaian')->onDelete('cascade');
            $table->foreignId('bukti_dukung_id')->nullable()->constrained('bukti_dukung')->onDelete('cascade');
            $table->foreignId('kriteria_komponen_id')->constrained('kriteria_komponen')->onDelete('cascade');
            $table->foreignId('opd_id')->constrained('opd')->onDelete('cascade');
            $table->foreignId('role_id')->constrained('role')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // Data Penilaian (Snapshot)
            $table->foreignId('tingkatan_nilai_id')->nullable()->constrained('tingkatan_nilai')->onDelete('set null');
            $table->boolean('is_verified')->nullable();
            $table->text('keterangan')->nullable();
            $table->boolean('is_perubahan')->default(false);

            // Timestamps
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penilaian_history');
    }
};
