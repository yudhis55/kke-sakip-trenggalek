<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BuktiDukung extends Model
{
    protected $table = 'bukti_dukung';
    protected $guarded = ['id'];

    public function kriteria_komponen(): BelongsTo
    {
        return $this->belongsTo(KriteriaKomponen::class, 'kriteria_komponen_id');
    }

    public function sub_komponen(): BelongsTo
    {
        return $this->belongsTo(SubKomponen::class, 'sub_komponen_id');
    }

    public function komponen(): BelongsTo
    {
        return $this->belongsTo(Komponen::class, 'komponen_id');
    }

    public function tahun(): BelongsTo
    {
        return $this->belongsTo(Tahun::class, 'tahun_id');
    }

    public function file_bukti_dukung(): HasMany
    {
        return $this->hasMany(FileBuktiDukung::class, 'bukti_dukung_id');
    }
}
