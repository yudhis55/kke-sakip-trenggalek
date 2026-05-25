<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Drop file_perbaikan_id FK + column from penilaian_history.
     * status_perbaikan and tanggal_perbaikan are KEPT — still actively used
     * by LembarKerja.php, RekapPenolakan.php, RekapPerbaikan.php.
     */
    public function up(): void
    {
        Schema::table('penilaian_history', function (Blueprint $table) {
            $table->dropForeign(['file_perbaikan_id']);
            $table->dropColumn('file_perbaikan_id');
        });
    }

    public function down(): void
    {
        Schema::table('penilaian_history', function (Blueprint $table) {
            $table->unsignedBigInteger('file_perbaikan_id')->nullable()->after('tanggal_perbaikan');
            $table->foreign('file_perbaikan_id')
                ->references('id')
                ->on('file_bukti_dukung')
                ->onDelete('set null');
        });
    }
};
