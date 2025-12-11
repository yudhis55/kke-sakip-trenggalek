<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tahun extends Model
{
    protected $table = 'tahun';

    protected $guarded = ['id'];

    public function setting()
    {
        return $this->hasMany(Setting::class, 'tahun_id');
    }

    public function komponen()
    {
        return $this->hasMany(Komponen::class, 'tahun_id');
    }

    public function sub_komponen()
    {
        return $this->hasMany(SubKomponen::class, 'tahun_id');
    }

    public function kriteria_komponen()
    {
        return $this->hasMany(KriteriaKomponen::class, 'tahun_id');
    }

    public function bukti_dukung()
    {
        return $this->hasMany(BuktiDukung::class, 'tahun_id');
    }
}
