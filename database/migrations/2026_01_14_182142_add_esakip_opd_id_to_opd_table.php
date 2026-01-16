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
            $table->string('esakip_opd_id')->nullable()->after('nama')->comment('ID OPD di sistem E-SAKIP untuk mapping saat sinkronisasi');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('opd', function (Blueprint $table) {
            $table->dropColumn('esakip_opd_id');
        });
    }
};
