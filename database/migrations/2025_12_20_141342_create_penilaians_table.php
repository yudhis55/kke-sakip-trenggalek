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
        Schema::create('penilaian', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_verified')->default(null)->nullable();
            $table->text('keterangan')->nullable();
            $table->foreignId('tingkatan_nilai_id')->nullable()->constrained('tingkatan_nilai')->restrictOnDelete();
            $table->foreignId('file_bukti_dukung_id')->nullable()->constrained('file_bukti_dukung')->restrictOnDelete();
            $table->foreignId('bukti_dukung_id')->nullable()->constrained('bukti_dukung')->restrictOnDelete();
            $table->foreignId('kriteria_komponen_id')->constrained('kriteria_komponen')->restrictOnDelete();
            $table->foreignId('opd_id')->constrained('opd')->restrictOnDelete();
            $table->foreignId('role_id')->constrained('role')->restrictOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penilaian');
    }
};
