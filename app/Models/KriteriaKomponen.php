<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KriteriaKomponen extends Model
{
    protected $table = 'kriteria_komponen';
    protected $guarded = ['id'];

    public function sub_komponen(): BelongsTo
    {
        return $this->belongsTo(SubKomponen::class, 'sub_komponen_id');
    }

    public function komponen(): BelongsTo
    {
        return $this->belongsTo(Komponen::class, 'komponen_id');
    }

    public function jenis_nilai(): BelongsTo
    {
        return $this->belongsTo(JenisNilai::class, 'jenis_nilai_id');
    }

    public function bukti_dukung(): HasMany
    {
        return $this->hasMany(BuktiDukung::class, 'kriteria_komponen_id');
    }

    public function penilaian_mandiri(): HasMany
    {
        return $this->hasMany(PenilaianMandiri::class, 'kriteria_komponen_id');
    }
}
