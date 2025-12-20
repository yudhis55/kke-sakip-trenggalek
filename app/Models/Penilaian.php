<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Penilaian extends Model
{
    protected $table = 'penilaian';

    protected $guarded = ['id'];

    public function tingkatan_nilai()
    {
        return $this->belongsTo(TingkatanNilai::class, 'tingkatan_nilai_id');
    }

    public function file_bukti_dukung()
    {
        return $this->belongsTo(FileBuktiDukung::class, 'file_bukti_dukung_id');
    }

    public function bukti_dukung()
    {
        return $this->belongsTo(BuktiDukung::class, 'bukti_dukung_id');
    }

    public function kriteria_komponen()
    {
        return $this->belongsTo(KriteriaKomponen::class, 'kriteria_komponen_id');
    }

    public function opd()
    {
        return $this->belongsTo(Opd::class, 'opd_id');
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }
}
