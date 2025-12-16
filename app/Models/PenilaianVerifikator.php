<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenilaianVerifikator extends Model
{
    protected $table = 'penilaian_verifikator';

    protected $guarded = ['id'];

    public function file_bukti_dukung()
    {
        return $this->belongsTo(FileBuktiDukung::class, 'file_bukti_dukung_id');
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }
}
