<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Penilaian extends Model
{
    protected $table = 'penilaian';

    protected $guarded = ['id'];

    protected $casts = [
        'link_file' => 'array',
        'is_perubahan' => 'boolean',
        'esakip_synced_at' => 'datetime',
        'page_number' => 'integer',
    ];

    public function tingkatan_nilai()
    {
        return $this->belongsTo(TingkatanNilai::class, 'tingkatan_nilai_id');
    }

    // Relasi ke FileBuktiDukung sudah tidak digunakan
    // public function file_bukti_dukung()
    // {
    //     return $this->belongsTo(FileBuktiDukung::class, 'file_bukti_dukung_id');
    // }

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

    public function history()
    {
        return $this->hasMany(PenilaianHistory::class, 'penilaian_id')->orderBy('created_at', 'desc');
    }

    /**
     * Helper method untuk record history setiap perubahan
     */
    public function recordHistory(
        $userId,
        $roleId,
        $opdId,
        $kriteriaKomponenId,
        $buktiDukungId = null,
        $tingkatanNilaiId = null,
        $isVerified = null,
        $keterangan = null,
        $isPerubahan = false
    ) {
        return PenilaianHistory::create([
            'penilaian_id' => $this->id,
            'bukti_dukung_id' => $buktiDukungId,
            'kriteria_komponen_id' => $kriteriaKomponenId,
            'opd_id' => $opdId,
            'role_id' => $roleId,
            'user_id' => $userId,
            'tingkatan_nilai_id' => $tingkatanNilaiId,
            'is_verified' => $isVerified,
            'keterangan' => $keterangan,
            'is_perubahan' => $isPerubahan,
        ]);
    }
}
