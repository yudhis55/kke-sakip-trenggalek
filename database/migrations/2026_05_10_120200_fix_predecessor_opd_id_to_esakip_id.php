<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * No-op migration: predecessor_opd_id sudah di-setup dengan benar di migration 120000
     */
    public function up(): void
    {
        // No operation needed - predecessor_opd_id sudah di-setup dengan benar
        // sebagai unsignedBigInteger (bukan foreign key) di migration 120000
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No operation needed - revert dilakukan melalui migration 120000
    }
};
