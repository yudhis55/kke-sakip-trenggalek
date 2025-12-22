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
        Schema::table('penilaian', function (Blueprint $table) {
            // Tambah kolom untuk file upload
            $table->json('link_file')->nullable()->after('keterangan');
            $table->boolean('is_perubahan')->default(false)->after('link_file');

            // Hapus foreign key dan kolom file_bukti_dukung_id
            $table->dropForeign(['file_bukti_dukung_id']);
            $table->dropColumn('file_bukti_dukung_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('penilaian', function (Blueprint $table) {
            // Kembalikan kolom file_bukti_dukung_id
            $table->foreignId('file_bukti_dukung_id')->nullable()->constrained('file_bukti_dukung')->onDelete('cascade');

            // Hapus kolom yang ditambahkan
            $table->dropColumn(['link_file', 'is_perubahan']);
        });
    }
};
