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
            // Kolom untuk tracking source file
            $table->enum('source', ['upload', 'esakip'])->default('upload')->after('link_file');

            // ID dokumen di esakip (untuk reference)
            $table->string('esakip_document_id')->nullable()->after('source');

            // Timestamp sinkronisasi dari esakip
            $table->timestamp('esakip_synced_at')->nullable()->after('esakip_document_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('penilaian', function (Blueprint $table) {
            $table->dropColumn([
                'source',
                'esakip_document_id',
                'esakip_synced_at'
            ]);
        });
    }
};
