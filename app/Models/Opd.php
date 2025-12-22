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

    /**
     * Hitung nilai total OPD untuk role tertentu
     * Nilai = SUM nilai semua komponen
     *
     * @param int $roleId
     * @param int|null $tahunId - Optional, untuk filter berdasarkan tahun tertentu
     * @return float
     */
    public function getNilai($roleId, $tahunId = null)
    {
        $totalNilai = 0;

        // Ambil semua komponen (dengan filter tahun jika disediakan)
        $komponenQuery = Komponen::query();

        if ($tahunId) {
            $komponenQuery->where('tahun_id', $tahunId);
        }

        $komponenList = $komponenQuery->get();

        foreach ($komponenList as $komponen) {
            $totalNilai += $komponen->getNilai($this->id, $roleId);
        }

        return round($totalNilai, 2);
    }

    /**
     * Hitung progress evaluasi OPD (berapa % kriteria yang sudah dinilai)
     *
     * @param int|null $tahunId - Optional, untuk filter berdasarkan tahun tertentu
     * @return float
     */
    public function getProgress($tahunId = null)
    {
        // Optimasi: Gunakan single query dengan multiple joins
        $query = \App\Models\KriteriaKomponen::selectRaw('COUNT(*) as total, COUNT(DISTINCT penilaian.kriteria_komponen_id) as dinilai')
            ->leftJoin('penilaian', function($join) {
                $join->on('kriteria_komponen.id', '=', 'penilaian.kriteria_komponen_id')
                     ->where('penilaian.opd_id', '=', $this->id)
                     ->whereNotNull('penilaian.tingkatan_nilai_id');
            })
            ->join('komponen', 'kriteria_komponen.komponen_id', '=', 'komponen.id');

        if ($tahunId) {
            $query->where('komponen.tahun_id', $tahunId);
        }

        $stats = $query->first();

        if (!$stats || $stats->total == 0) {
            return 0;
        }

        return round(($stats->dinilai / $stats->total) * 100, 2);
    }
}
