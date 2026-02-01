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
        Schema::table('penilaian_history', function (Blueprint $table) {
            $table->enum('status_perbaikan', [
                'belum_diperbaiki',
                'sudah_diperbaiki',
                'diterima_setelah_perbaikan'
            ])->default('belum_diperbaiki')->after('keterangan');

            $table->timestamp('tanggal_perbaikan')->nullable()->after('status_perbaikan');
            $table->unsignedBigInteger('file_perbaikan_id')->nullable()->after('tanggal_perbaikan');

            // Foreign key untuk file perbaikan (mengacu ke file_bukti_dukung)
            $table->foreign('file_perbaikan_id')
                ->references('id')
                ->on('file_bukti_dukung')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('penilaian_history', function (Blueprint $table) {
            $table->dropForeign(['file_perbaikan_id']);
            $table->dropColumn(['status_perbaikan', 'tanggal_perbaikan', 'file_perbaikan_id']);
        });
    }
};
