<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Drop penilaian_penilai table (deprecated — replaced by unified penilaian table).
     * No FK dependencies from other tables.
     */
    public function up(): void
    {
        Schema::dropIfExists('penilaian_penilai');
    }

    public function down(): void
    {
        // Table was deprecated — no need to recreate in rollback
        // Original creation: 2025_12_16_123407_create_penilaian_penilais_table.php
    }
};
