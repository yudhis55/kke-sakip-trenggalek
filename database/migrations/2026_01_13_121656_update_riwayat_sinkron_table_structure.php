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
        Schema::table('riwayat_sinkron', function (Blueprint $table) {
            // Tambah foreignId tahun_id
            $table->foreignId('tahun_id')->after('opd_id')->constrained('tahun')->restrictOnDelete();

            // Ubah kolom dokumen menjadi document_type dan document_name
            $table->renameColumn('dokumen', 'document_type'); // renja, iku, lkjip
            $table->string('document_name')->after('document_type')->nullable(); // nama dokumen spesifik

            // Tambah kolom file_url untuk menyimpan URL dokumen dari esakip
            $table->text('file_url')->after('document_name')->nullable();

            // Ubah kolom tahun menjadi tahun_value untuk value tahun (2024, 2025, dll)
            $table->renameColumn('tahun', 'tahun_value');

            // Kolom untuk tracking penilaian yang ter-affected
            $table->json('penilaian_ids')->after('file_url')->nullable(); // [1,2,3,4]
            $table->integer('affected_count')->default(0)->after('penilaian_ids');
            $table->integer('auto_verified_count')->default(0)->after('affected_count');

            // Ubah status menjadi enum
            $table->dropColumn('status');
            $table->enum('status', ['success', 'failed', 'partial'])->default('success')->after('auto_verified_count');

            // Timestamp sinkronisasi
            $table->timestamp('synced_at')->after('status')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('riwayat_sinkron', function (Blueprint $table) {
            // Rollback semua perubahan
            $table->dropForeign(['tahun_id']);
            $table->dropColumn([
                'tahun_id',
                'document_name',
                'file_url',
                'penilaian_ids',
                'affected_count',
                'auto_verified_count',
                'synced_at'
            ]);

            // Kembalikan nama kolom
            $table->renameColumn('document_type', 'dokumen');
            $table->renameColumn('tahun_value', 'tahun');

            // Kembalikan status ke string
            $table->dropColumn('status');
            $table->string('status');
        });
    }
};
