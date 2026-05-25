<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Drop penilaian_verifikator table (deprecated — replaced by unified penilaian table).
     * Must run AFTER dropping file_perbaikan_id FK from penilaian_history,
     * and BEFORE dropping file_bukti_dukung (penilaian_verifikator has FK to it).
     */
    public function up(): void
    {
        Schema::dropIfExists('penilaian_verifikator');
    }

    public function down(): void
    {
        // Table was deprecated — no need to recreate in rollback
        // Original creation: 2025_12_01_033157_create_penilaian_verifikators_table.php
    }
};
