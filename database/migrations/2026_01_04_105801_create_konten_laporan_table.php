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
        Schema::create('konten_laporan', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['deskripsi', 'catatan', 'rekomendasi']);
            $table->foreignId('komponen_id')->nullable()->constrained('komponen')->onDelete('cascade');
            $table->foreignId('sub_komponen_id')->nullable()->constrained('sub_komponen')->onDelete('cascade');
            $table->foreignId('opd_id')->constrained('opd')->onDelete('cascade');
            $table->foreignId('tahun_id')->constrained('tahun')->onDelete('cascade');
            $table->text('konten');
            $table->integer('urutan')->default(0);
            $table->timestamps();

            // Index untuk performance query
            $table->index(['type', 'opd_id', 'tahun_id']);
            $table->index(['komponen_id', 'opd_id', 'tahun_id']);
            $table->index(['sub_komponen_id', 'opd_id', 'tahun_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('konten_laporan');
    }
};
