<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubKomponen extends Model
{
    protected $table = 'sub_komponen';
    protected $guarded = ['id'];

    protected $casts = [
        'bobot' => 'decimal:2',
    ];

    protected $appends = ['bobot_persen'];

    public function getBobotPersenAttribute()
    {
        // Bobot sudah dalam persen di database, return as-is
        return $this->bobot;
    }

    public function komponen(): BelongsTo
    {
        return $this->belongsTo(Komponen::class, 'komponen_id');
    }

    public function kriteria_komponen(): HasMany
    {
        return $this->hasMany(KriteriaKomponen::class, 'sub_komponen_id');
    }

    public function bukti_dukung(): HasMany
    {
        return $this->hasMany(BuktiDukung::class, 'sub_komponen_id');
    }

    /**
     * Hitung nilai sub komponen untuk OPD tertentu dan role tertentu
     * Nilai = SUM nilai semua kriteria komponennya
     *
     * @param int $opdId
     * @param int $roleId
     * @return float
     */
    public function getNilai($opdId, $roleId)
    {
        $totalNilai = 0;
        $kriteriaList = $this->kriteria_komponen;

        foreach ($kriteriaList as $kriteria) {
            $totalNilai += $kriteria->getNilai($opdId, $roleId);
        }

        return round($totalNilai, 2);
    }

    public function getNilaiPerRole($opdId)
    {
        $roleList = Penilaian::whereIn('kriteria_komponen_id', $this->kriteria_komponen->pluck('id'))
            ->where('opd_id', $opdId)
            ->whereNotNull('tingkatan_nilai_id')
            ->with('role')
            ->select('role_id')
            ->distinct()
            ->get()
            ->pluck('role')
            ->filter();

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

        return $nilaiPerRole;
    }

    public function getNilaiRataRata($opdId)
    {
        $nilaiPerRole = $this->getNilaiPerRole($opdId);
        if (empty($nilaiPerRole)) {
            return 0;
        }

        $totalNilai = array_sum(array_column($nilaiPerRole, 'nilai'));
        // Rata-rata dibagi 3: penilaian mandiri (opd), penilai (evaluator), penjamin
        return round($totalNilai / 3, 2);
    }

    /**
     * Hitung total nilai sub komponen (rata-rata dari semua role)
     */
    public function getNilaiTotal($opdId)
    {
        return $this->getNilaiRataRata($opdId);
    }

    /**
     * Hitung progress evaluasi (berapa % kriteria yang sudah dinilai)
     */
    public function getProgress($opdId)
    {
        // Optimasi: Gunakan single query dengan join
        $stats = \App\Models\KriteriaKomponen::selectRaw('COUNT(*) as total, COUNT(DISTINCT penilaian.kriteria_komponen_id) as dinilai')
            ->leftJoin('penilaian', function ($join) use ($opdId) {
                $join->on('kriteria_komponen.id', '=', 'penilaian.kriteria_komponen_id')
                    ->where('penilaian.opd_id', '=', $opdId)
                    ->whereNotNull('penilaian.tingkatan_nilai_id');
            })
            ->where('kriteria_komponen.sub_komponen_id', $this->id)
            ->first();

        if (!$stats || $stats->total == 0) {
            return 0;
        }

        return round(($stats->dinilai / $stats->total) * 100, 2);
    }

    /**
     * Relationship: Has many KontenLaporan (deskripsi)
     */
    public function kontenLaporan(): HasMany
    {
        return $this->hasMany(KontenLaporan::class, 'sub_komponen_id');
    }

    /**
     * Get deskripsi for specific OPD and Tahun
     */
    public function getDeskripsiForOpdTahun($opdId, $tahunId)
    {
        return $this->kontenLaporan()
            ->deskripsi()
            ->forOpdTahun($opdId, $tahunId)
            ->first()?->konten ?? '';
    }
}
