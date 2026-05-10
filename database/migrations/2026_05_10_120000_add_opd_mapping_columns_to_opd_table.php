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
        Schema::table('opd', function (Blueprint $table) {
            // Tahun mulai berlakunya OPD ini (untuk OPD yang baru di tahun tertentu)
            $table->integer('tahun_mulai_berlaku')->nullable()->after('esakip_opd_id')
                ->comment('Tahun mulai berlaku OPD ini (untuk OPD baru/reorganisasi)');

            // ID E-SAKIP OPD lama jika ada reorganisasi (split/merger) - BUKAN foreign key ke aplikasi
            $table->unsignedBigInteger('predecessor_opd_id')->nullable()->after('tahun_mulai_berlaku')
                ->comment('ID OPD di E-SAKIP yang menjadi predecessor (untuk mapping saat reorganisasi)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('opd', function (Blueprint $table) {
            $table->dropForeignKeyIfExists(['predecessor_opd_id']);
            $table->dropColumn(['tahun_mulai_berlaku', 'predecessor_opd_id']);
        });
    }
};
