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
        Schema::create('penilaian_penjamin', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tingkatan_nilai_id')->constrained('tingkatan_nilai')->restrictOnDelete();
            $table->foreignId('kriteria_komponen_id')->constrained('kriteria_komponen')->restrictOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penilaian_penjamin');
    }
};
