<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Opd extends Model
{
    protected $table = 'opd';

    protected $guarded = ['id'];

    public function file_bukti_dukung()
    {
        return $this->hasMany(FileBuktiDukung::class, 'opd_id');
    }

    public function penilaian_mandiri()
    {
        return $this->hasMany(PenilaianMandiri::class, 'opd_id');
    }

    public function penilaian_verifikator()
    {
        return $this->hasMany(PenilaianVerifikator::class, 'opd_id');
    }

    public function user()
    {
        return $this->hasMany(User::class, 'opd_id');
    }
}
