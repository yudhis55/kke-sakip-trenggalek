<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PenilaianHistory extends Model
{
    protected $table = 'penilaian_history';
    protected $guarded = ['id'];

    protected $casts = [
        'is_verified' => 'boolean',
        'is_perubahan' => 'boolean',
    ];

    // Relasi
    public function penilaian(): BelongsTo
    {
        return $this->belongsTo(Penilaian::class, 'penilaian_id');
    }

    public function bukti_dukung(): BelongsTo
    {
        return $this->belongsTo(BuktiDukung::class, 'bukti_dukung_id');
    }

    public function kriteria_komponen(): BelongsTo
    {
        return $this->belongsTo(KriteriaKomponen::class, 'kriteria_komponen_id');
    }

    public function opd(): BelongsTo
    {
        return $this->belongsTo(Opd::class, 'opd_id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function tingkatan_nilai(): BelongsTo
    {
        return $this->belongsTo(TingkatanNilai::class, 'tingkatan_nilai_id');
    }

    /**
     * Helper untuk mendapatkan deskripsi aksi
     */
    public function getActionDescription(): string
    {
        $roleJenis = $this->role->jenis;

        if ($roleJenis == 'opd') {
            if ($this->is_perubahan) {
                return 'melakukan revisi/perbaikan';
            }
            return 'melakukan penilaian mandiri';
        }

        if ($roleJenis == 'verifikator') {
            if ($this->is_verified === true) {
                return 'menyetujui';
            }
            if ($this->is_verified === false) {
                return 'menolak';
            }
            return 'memverifikasi';
        }

        if ($roleJenis == 'penjamin') {
            return 'memberikan penilaian penjaminan kualitas';
        }

        if ($roleJenis == 'penilai') {
            return 'memberikan penilaian evaluasi';
        }

        return 'melakukan aksi';
    }

    /**
     * Helper untuk mendapatkan status badge color
     */
    public function getStatusBadgeColor(): string
    {
        if ($this->is_verified === true) {
            return 'success';
        }
        if ($this->is_verified === false) {
            return 'danger';
        }
        if ($this->is_perubahan) {
            return 'warning';
        }
        return 'primary';
    }
}
