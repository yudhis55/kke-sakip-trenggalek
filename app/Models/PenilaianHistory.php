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
        'tanggal_perbaikan' => 'datetime',
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
     *
     * Untuk role OPD, tipe aksi DITENTUKAN OLEH KOMBINASI FIELD, bukan flag is_perubahan.
     *
     * BUG SEBELUMNYA: kode lama membaca `is_perubahan` (yang artinya "row Penilaian sudah
     * pernah ada sebelum aksi ini") sebagai sinyal "revisi". Padahal flow OPD adalah:
     *   1. Upload pertama        → is_perubahan = false, tingkatan_nilai_id = null
     *   2. Skoring pertama       → is_perubahan = true (row sudah ada dari upload),
     *                              tingkatan_nilai_id = set
     *   3. Re-upload / re-score  → is_perubahan = true
     *
     * Jadi `is_perubahan=true` BUKAN berarti revisi — bisa jadi skoring pertama kali.
     *
     * FIX: bedakan UPLOAD vs SCORING dengan `tingkatan_nilai_id`:
     *   - tingkatan_nilai_id !== null → aksi skoring (memberikan penilaian)
     *   - tingkatan_nilai_id === null → aksi upload dokumen
     *
     * Aksi DELETE dideteksi via EXACT MATCH terhadap string konstanta yang di-set oleh
     * LembarKerja.php (`'Menghapus penilaian'`, `'Menghapus file dokumen'`). EXACT MATCH
     * dipakai (bukan str_contains/stripos) supaya keterangan bebas dari user tidak memicu
     * false-positive — string ini hanya bisa muncul kalau di-set oleh kode aplikasi.
     */
    public function getActionDescription(): string
    {
        $roleJenis = $this->role->jenis;

        if ($roleJenis == 'opd') {
            // Tentukan tipe aksi berdasarkan kombinasi field di history record
            $hasTingkatanNilai = $this->tingkatan_nilai_id !== null;
            $keterangan = $this->keterangan ?? '';

            // EXACT MATCH terhadap string yang di-SET oleh code (LembarKerja.php)
            // BUKAN substring match — untuk hindari false positive dari user keterangan input
            if ($keterangan === 'Menghapus penilaian') {
                return 'menghapus penilaian';
            }
            if ($keterangan === 'Menghapus file dokumen') {
                return 'menghapus dokumen';
            }

            if ($hasTingkatanNilai) {
                // Scoring action — Penilaian punya tingkatan_nilai_id
                return $this->is_perubahan
                    ? 'memberikan penilaian mandiri (revisi)'
                    : 'memberikan penilaian mandiri';
            }

            // Upload action — tingkatan_nilai null AND bukan delete keterangan
            return $this->is_perubahan
                ? 'mengupload dokumen (revisi)'
                : 'mengupload dokumen';
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
