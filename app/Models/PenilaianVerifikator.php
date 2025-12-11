<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenilaianVerifikator extends Model
{
    protected $table = 'penilaian_verifikator';

    protected $guarded = ['id'];

    public function penilaian_mandiri()
    {
        return $this->belongsTo(PenilaianMandiri::class, 'penilaian_mandiri_id');
    }

    public function opd()
    {
        return $this->belongsTo(Opd::class, 'opd_id');
    }
}
