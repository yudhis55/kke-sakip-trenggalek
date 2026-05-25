<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Drop file_bukti_dukung table (deprecated — file storage moved to penilaian.link_file JSON).
     * Must run AFTER dropping penilaian_verifikator (which had FK to this table)
     * and AFTER dropping file_perbaikan_id FK from penilaian_history.
     */
    public function up(): void
    {
        Schema::dropIfExists('file_bukti_dukung');
    }

    public function down(): void
    {
        // Table was deprecated — no need to recreate in rollback
        // Original creation: 2025_12_01_033155_create_file_bukti_dukungs_table.php
    }
};
