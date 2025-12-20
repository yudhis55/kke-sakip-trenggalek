<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $table = 'setting';

    protected $guarded = ['id'];

    protected $casts = [
        'buka_penilaian_mandiri' => 'datetime',
        'tutup_penilaian_mandiri' => 'datetime',
        'buka_penilaian_verifikator' => 'datetime',
        'tutup_penilaian_verifikator' => 'datetime',
        'buka_penilaian_penjamin' => 'datetime',
        'tutup_penilaian_penjamin' => 'datetime',
        'buka_penilaian_penilai' => 'datetime',
        'tutup_penilaian_penilai' => 'datetime',
    ];

    public function tahun()
    {
        return $this->belongsTo(Tahun::class, 'tahun_id');
    }
}
