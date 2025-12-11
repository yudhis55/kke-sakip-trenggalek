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
        Schema::create('kriteria_komponen', function (Blueprint $table) {
            $table->id();
            $table->string('kode');
            $table->string('nama');
            $table->foreignId('sub_komponen_id')->constrained('sub_komponen')->restrictOnDelete();
            $table->foreignId('komponen_id')->constrained('komponen')->restrictOnDelete();
            $table->foreignId('jenis_nilai_id')->constrained('jenis_nilai')->restrictOnDelete();
            $table->foreignId('tahun_id')->constrained('tahun')->restrictOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kriteria_komponen');
    }
};
