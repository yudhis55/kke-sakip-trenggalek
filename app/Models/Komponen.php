<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Komponen extends Model
{
    protected $table = 'komponen';
    protected $guarded = ['id'];

    protected $casts = [
        'bobot' => 'decimal:2',
    ];

    public function tahun(): BelongsTo
    {
        return $this->belongsTo(Tahun::class, 'tahun_id');
    }

    public function sub_komponen(): HasMany
    {
        return $this->hasMany(SubKomponen::class, 'komponen_id');
    }

    public function kriteria_komponen(): HasMany
    {
        return $this->hasMany(KriteriaKomponen::class, 'komponen_id');
    }

    public function bukti_dukung(): HasMany
    {
        return $this->hasMany(BuktiDukung::class, 'komponen_id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    /**
     * Hitung nilai komponen untuk OPD tertentu dan role tertentu
     * Nilai = SUM nilai semua sub komponennya
     *
     * @param int $opdId
     * @param int $roleId
     * @return float
     */
    public function getNilai($opdId, $roleId)
    {
        $totalNilai = 0;
        $subKomponenList = $this->sub_komponen;

        foreach ($subKomponenList as $subKomponen) {
            $totalNilai += $subKomponen->getNilai($opdId, $roleId);
        }

        return round($totalNilai, 2);
    }

    /**
     * Get nilai per role untuk komponen
     */
    public function getNilaiPerRole($opdId)
    {
        // Static cache untuk role list
        static $roleList = null;
        if ($roleList === null) {
            $roleList = Role::whereIn('jenis', ['opd', 'penilai', 'penjamin'])->get();
        }

        // Cache hasil calculation per komponen_id dan opd_id
        static $nilaiCache = [];
        $cacheKey = "{$this->id}_{$opdId}";

        if (isset($nilaiCache[$cacheKey])) {
            return $nilaiCache[$cacheKey];
        }

        $nilaiPerRole = [];
        foreach ($roleList as $role) {
            $nilai = $this->getNilai($opdId, $role->id);
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
     */
    public function getNilaiRataRata($opdId)
    {
        // Cache hasil rata-rata per komponen_id dan opd_id
        static $rataCache = [];
        $cacheKey = "{$this->id}_{$opdId}";

        if (isset($rataCache[$cacheKey])) {
            return $rataCache[$cacheKey];
        }

        $nilaiPerRole = $this->getNilaiPerRole($opdId);

        if (empty($nilaiPerRole)) {
            $rataCache[$cacheKey] = 0;
            return 0;
        }

        $totalNilai = array_sum(array_column($nilaiPerRole, 'nilai'));
        // Rata-rata dibagi 3: penilaian mandiri (opd), penilai (evaluator), penjamin
        $rata = round($totalNilai / 3, 2);

        $rataCache[$cacheKey] = $rata;
        return $rata;
    }

    /**
     * Hitung total nilai komponen (rata-rata dari semua role)
     */
    public function getNilaiTotal($opdId)
    {
        return $this->getNilaiRataRata($opdId);
    }

    /**
     * Hitung progress evaluasi (berapa % kriteria yang sudah dinilai)
     * Progress = jumlah kriteria yang punya penilaian / total kriteria
     */
    public function getProgress($opdId)
    {
        // Optimasi: Gunakan single query dengan join
        $stats = KriteriaKomponen::selectRaw('COUNT(*) as total, COUNT(DISTINCT penilaian.kriteria_komponen_id) as dinilai')
            ->leftJoin('penilaian', function ($join) use ($opdId) {
                $join->on('kriteria_komponen.id', '=', 'penilaian.kriteria_komponen_id')
                    ->where('penilaian.opd_id', '=', $opdId)
                    ->whereNotNull('penilaian.tingkatan_nilai_id');
            })
            ->where('kriteria_komponen.komponen_id', $this->id)
            ->first();

        if (!$stats || $stats->total == 0) {
            return 0;
        }

        return round(($stats->dinilai / $stats->total) * 100, 2);
    }

    /**
     * Relationship: Has many KontenLaporan (catatan & rekomendasi)
     */
    public function kontenLaporan(): HasMany
    {
        return $this->hasMany(KontenLaporan::class, 'komponen_id');
    }

    /**
     * Get catatan for specific OPD and Tahun
     */
    public function getCatatanForOpdTahun($opdId, $tahunId)
    {
        return $this->kontenLaporan()
            ->catatan()
            ->forOpdTahun($opdId, $tahunId)
            ->pluck('konten')
            ->toArray();
    }

    /**
     * Get rekomendasi for specific OPD and Tahun
     */
    public function getRekomendasiForOpdTahun($opdId, $tahunId)
    {
        return $this->kontenLaporan()
            ->rekomendasi()
            ->forOpdTahun($opdId, $tahunId)
            ->pluck('konten')
            ->toArray();
    }
}
