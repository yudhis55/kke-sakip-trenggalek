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
        Schema::create('file_bukti_dukung', function (Blueprint $table) {
            $table->id();
            $table->json('link_file');
            $table->foreignId('bukti_dukung_id')->constrained('bukti_dukung')->restrictOnDelete();
            $table->foreignId('opd_id')->constrained('opd')->restrictOnDelete();
            $table->boolean('is_perubahan');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('file_bukti_dukung');
    }
};
