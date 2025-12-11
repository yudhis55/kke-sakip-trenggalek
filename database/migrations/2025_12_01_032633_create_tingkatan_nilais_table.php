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
        Schema::create('tingkatan_nilai', function (Blueprint $table) {
            $table->id();
            $table->string('kode_nilai');
            $table->float('bobot');
            $table->foreignId('jenis_nilai_id')->constrained('jenis_nilai')->restrictOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tingkatan_nilai');
    }
};
