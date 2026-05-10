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
            // Flag untuk dokumen yang menggunakan tahun sebelumnya (n-1)
            $table->boolean('is_n_minus_1')->default(false)->after('esakip_document_code')
                ->comment('Apakah bukti dukung ini mengambil dokumen dari tahun sebelumnya (n-1)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bukti_dukung', function (Blueprint $table) {
            $table->dropColumn('is_n_minus_1');
        });
    }
};
