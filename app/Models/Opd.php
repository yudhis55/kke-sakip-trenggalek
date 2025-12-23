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
     * Hitung nilai per role untuk OPD ini
     * Return array: [['role_id' => ..., 'role_nama' => ..., 'role_jenis' => ..., 'nilai' => ...], ...]
     *
     * @param int|null $tahunId - Optional, untuk filter berdasarkan tahun tertentu
     * @return array
     */
    public function getNilaiPerRole($tahunId = null)
    {
        // Cache hasil calculation per opd_id dan tahun_id
        static $nilaiCache = [];
        $cacheKey = "{$this->id}_{$tahunId}";

        if (isset($nilaiCache[$cacheKey])) {
            return $nilaiCache[$cacheKey];
        }

        $roleList = Role::whereIn('jenis', ['opd', 'verifikator', 'penilai', 'penjamin'])->get();
        $nilaiPerRole = [];

        foreach ($roleList as $role) {
            $nilai = $this->getNilai($role->id, $tahunId);
            if ($nilai > 0) {
                $nilaiPerRole[] = [
                    'role_id' => $role->id,
                    'role_nama' => $role->nama,
                    'role_jenis' => $role->jenis,
                    'nilai' => $nilai
                ];
            }
        }

        $nilaiCache[$cacheKey] = $nilaiPerRole;
        return $nilaiPerRole;
    }

    /**
     * Hitung rata-rata nilai dari semua role
     *
     * @param int|null $tahunId - Optional, untuk filter berdasarkan tahun tertentu
     * @return float
     */
    public function getNilaiRataRata($tahunId = null)
    {
        $nilaiPerRole = $this->getNilaiPerRole($tahunId);

        if (empty($nilaiPerRole)) {
            return 0;
        }

        // Filter hanya 3 role untuk perhitungan rata-rata (exclude verifikator)
        $nilaiForAverage = array_filter($nilaiPerRole, function ($item) {
            return in_array($item['role_jenis'], ['opd', 'penilai', 'penjamin']);
        });

        if (empty($nilaiForAverage)) {
            return 0;
        }

        $totalNilai = array_sum(array_column($nilaiForAverage, 'nilai'));
        // Rata-rata dibagi 3: penilaian mandiri (opd), penilai (evaluator), penjamin
        // Verifikator tidak termasuk dalam rata-rata karena hanya melakukan verifikasi
        return round($totalNilai / 3, 2);
    }

    /**
     * Hitung total nilai OPD (rata-rata dari semua role)
     *
     * @param int|null $tahunId - Optional, untuk filter berdasarkan tahun tertentu
     * @return float
     */
    public function getNilaiTotal($tahunId = null)
    {
        return $this->getNilaiRataRata($tahunId);
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
            ->leftJoin('penilaian', function ($join) {
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
