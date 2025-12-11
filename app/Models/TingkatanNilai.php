<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TingkatanNilai extends Model
{
    protected $table = 'tingkatan_nilai';
    protected $guarded = ['id'];

    public function jenis_nilai()
    {
        return $this->belongsTo(JenisNilai::class, 'jenis_nilai_id');
    }

    public function penilaian_mandiri()
    {
        return $this->hasMany(PenilaianMandiri::class, 'tingkatan_nilai_id');
    }
}
