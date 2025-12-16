<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FileBuktiDukung extends Model
{
    protected $table = 'file_bukti_dukung';
    protected $guarded = ['id'];

    protected $casts = [
        'is_perubahan' => 'boolean',
    ];

    public function bukti_dukung(): BelongsTo
    {
        return $this->belongsTo(BuktiDukung::class, 'bukti_dukung_id');
    }

    public function opd(): BelongsTo
    {
        return $this->belongsTo(Opd::class, 'opd_id');
    }

    public function penilaian_verifikator(): BelongsTo
    {
        return $this->belongsTo(PenilaianVerifikator::class, 'id', 'file_bukti_dukung_id');
    }
}
