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
            // Update enum status untuk menambahkan 'no_document'
            $table->dropColumn('status');
        });

        Schema::table('riwayat_sinkron', function (Blueprint $table) {
            $table->enum('status', ['success', 'failed', 'partial', 'no_document'])
                ->default('success')
                ->after('auto_verified_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('riwayat_sinkron', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('riwayat_sinkron', function (Blueprint $table) {
            $table->enum('status', ['success', 'failed', 'partial'])
                ->default('success');
        });
    }
};
