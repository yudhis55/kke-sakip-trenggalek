<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiwayatSinkron extends Model
{
    protected $table = 'riwayat_sinkron';
    protected $guarded = ['id'];

    protected $casts = [
        'penilaian_ids' => 'array',
        'synced_at' => 'datetime',
    ];

    public function opd(): BelongsTo
    {
        return $this->belongsTo(Opd::class, 'opd_id');
    }

    public function tahun(): BelongsTo
    {
        return $this->belongsTo(Tahun::class, 'tahun_id');
    }

    /**
     * Get penilaian yang ter-affected oleh sinkronisasi ini
     */
    public function penilaian()
    {
        if (empty($this->penilaian_ids)) {
            return collect([]);
        }
        return Penilaian::whereIn('id', $this->penilaian_ids)->get();
    }
}
