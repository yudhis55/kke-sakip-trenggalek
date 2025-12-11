<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JenisNilai extends Model
{
    protected $table = 'jenis_nilai';
    protected $guarded = ['id'];

    public function kriteriaKomponen()
    {
        return $this->hasMany(KriteriaKomponen::class, 'jenis_nilai_id');
    }

    public function tingkatan_nilai()
    {
        return $this->hasMany(TingkatanNilai::class, 'jenis_nilai_id');
    }

}
