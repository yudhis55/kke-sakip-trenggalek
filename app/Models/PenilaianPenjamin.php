<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenilaianPenjamin extends Model
{
    protected $table = 'penilaian_penjamin';

    protected $guarded = ['id'];

    public function tingkatan_nilai()
    {
        return $this->belongsTo(TingkatanNilai::class, 'tingkatan_nilai_id');
    }

    public function kriteria_komponen()
    {
        return $this->belongsTo(KriteriaKomponen::class, 'kriteria_komponen_id');
    }
}
