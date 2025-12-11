<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenilaianMandiri extends Model
{
    protected $table = 'penilaian_mandiri';

    protected $guarded = ['id'];

    public function tingkatan_nilai()
    {
        return $this->belongsTo(TingkatanNilai::class, 'tingkatan_nilai_id');
    }

    public function kriteria_komponen()
    {
        return $this->belongsTo(KriteriaKomponen::class, 'kriteria_komponen_id');
    }

    public function opd()
    {
        return $this->belongsTo(Opd::class, 'opd_id');
    }

    public function penilaian_verifikator()
    {
        return $this->hasMany(PenilaianVerifikator::class, 'penilaian_mandiri_id');
    }
}
