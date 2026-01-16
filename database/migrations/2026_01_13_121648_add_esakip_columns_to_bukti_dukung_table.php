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
        Schema::table('bukti_dukung', function (Blueprint $table) {
            // Kolom untuk mapping dengan dokumen esakip
            $table->string('esakip_document_type')->nullable()->after('nama'); // renja, iku, lkjip, dll
            $table->string('esakip_document_code')->nullable()->after('esakip_document_type'); // kode/ID dokumen di esakip (opsional)

            // Status sinkronisasi
            $table->enum('sync_status', ['not_synced', 'synced', 'failed'])->default('not_synced')->after('is_auto_verified');
            $table->timestamp('last_synced_at')->nullable()->after('sync_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bukti_dukung', function (Blueprint $table) {
            $table->dropColumn([
                'esakip_document_type',
                'esakip_document_code',
                'sync_status',
                'last_synced_at'
            ]);
        });
    }
};
