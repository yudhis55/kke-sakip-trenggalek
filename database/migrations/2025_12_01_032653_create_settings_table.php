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
        Schema::create('setting', function (Blueprint $table) {
            $table->id();
            $table->dateTime('buka_penilaian_mandiri')->nullable();
            $table->dateTime('tutup_penilaian_mandiri')->nullable();
            $table->dateTime('buka_penilaian_verifikator')->nullable();
            $table->dateTime('tutup_penilaian_verifikator')->nullable();
            $table->foreignId('tahun_id')->nullable()->constrained('tahun')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('setting');
    }
};
