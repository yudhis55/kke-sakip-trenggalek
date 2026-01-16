<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BuktiDukung extends Model
{
    protected $table = 'bukti_dukung';
    protected $guarded = ['id'];
    protected $appends = ['bobot'];

    protected $casts = [
        'is_auto_verified' => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    public function kriteria_komponen(): BelongsTo
    {
        return $this->belongsTo(KriteriaKomponen::class, 'kriteria_komponen_id');
    }

    public function sub_komponen(): BelongsTo
    {
        return $this->belongsTo(SubKomponen::class, 'sub_komponen_id');
    }

    public function komponen(): BelongsTo
    {
        return $this->belongsTo(Komponen::class, 'komponen_id');
    }

    public function tahun(): BelongsTo
    {
        return $this->belongsTo(Tahun::class, 'tahun_id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function penilaian(): HasMany
    {
        return $this->hasMany(Penilaian::class, 'bukti_dukung_id');
    }

    // Deprecated: File storage now in penilaian table
    // public function file_bukti_dukung(): HasMany
    // {
    //     return $this->hasMany(FileBuktiDukung::class, 'bukti_dukung_id');
    // }

    protected function bobot(): Attribute
    {
        return Attribute::make(
            get: function () {
                // Check if kriteria_komponen relationship is loaded
                if (!$this->relationLoaded('kriteria_komponen') || !$this->kriteria_komponen) {
                    return 0;
                }

                // Check if bukti_dukung_count exists
                if (
                    !isset($this->kriteria_komponen->bukti_dukung_count) ||
                    $this->kriteria_komponen->bukti_dukung_count == 0
                ) {
                    return 0;
                }

                // Get bobot from kriteria komponen and divide by count
                $kriteriaBobot = $this->kriteria_komponen->bobot;
                return round($kriteriaBobot / $this->kriteria_komponen->bukti_dukung_count, 2);
            }
        );
    }

    /**
     * Hitung nilai bukti dukung untuk OPD tertentu dan role tertentu
     * Nilai = tingkatan_nilai.bobot × bobot bukti dukung
     *
     * @param int $opdId
     * @param int $roleId
     * @return float
     */
    public function getNilai($opdId, $roleId)
    {
        // Cek cache penilaian bukti dari KriteriaKomponen
        $cacheKey = "bukti_{$this->id}_{$opdId}_{$roleId}";
        $penilaian = \App\Models\KriteriaKomponen::getPenilaianBuktiCache($cacheKey);

        if (!$penilaian) {
            // Fallback: query jika tidak ada di cache
            $penilaian = Penilaian::where('bukti_dukung_id', $this->id)
                ->where('kriteria_komponen_id', $this->kriteria_komponen_id)
                ->where('opd_id', $opdId)
                ->where('role_id', $roleId)
                ->with('tingkatan_nilai')
                ->first();
        }

        if (!$penilaian || !$penilaian->tingkatan_nilai) {
            return 0;
        }

        // Ambil kriteria komponen dan sub komponen
        $kriteriaKomponen = $this->kriteria_komponen ?? KriteriaKomponen::find($this->kriteria_komponen_id);
        if (!$kriteriaKomponen) {
            return 0;
        }

        $subKomponen = $kriteriaKomponen->sub_komponen ?? SubKomponen::find($kriteriaKomponen->sub_komponen_id);
        if (!$subKomponen) {
            return 0;
        }

        // Hitung bobot kriteria: sub_komponen.bobot / jumlah kriteria di sub_komponen
        $jumlahKriteria = KriteriaKomponen::where('sub_komponen_id', $subKomponen->id)->count();
        if ($jumlahKriteria == 0) {
            return 0;
        }
        $bobotKriteria = $subKomponen->bobot / $jumlahKriteria;

        // Hitung bobot bukti dukung: bobot_kriteria / jumlah bukti di kriteria
        // Cek cache dulu
        $jumlahBuktiDukung = \App\Models\KriteriaKomponen::getBuktiDukungCount($this->kriteria_komponen_id);
        if ($jumlahBuktiDukung === null) {
            // Fallback: query jika tidak di cache
            $jumlahBuktiDukung = BuktiDukung::where('kriteria_komponen_id', $this->kriteria_komponen_id)->count();
        }
        if ($jumlahBuktiDukung == 0) {
            return 0;
        }
        $bobotBuktiDukung = $bobotKriteria / $jumlahBuktiDukung;

        // Nilai = bobot tingkatan nilai × bobot bukti dukung
        // Bobot tingkatan nilai sudah dalam bentuk desimal (0-1), jadi tidak perlu dibagi 100
        $bobotTingkatanNilai = $penilaian->tingkatan_nilai->bobot ?? 0;

        return round($bobotTingkatanNilai * $bobotBuktiDukung, 2);
    }

    /**
     * Hitung nilai bukti dukung per role untuk OPD tertentu
     *
     * @param int $opdId
     * @return array
     */
    public function getNilaiPerRole($opdId)
    {
        $roleList = Penilaian::where('bukti_dukung_id', $this->id)
            ->where('kriteria_komponen_id', $this->kriteria_komponen_id)
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

    /**
     * Hitung rata-rata nilai dari semua role
     *
     * @param int $opdId
     * @return float
     */
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
}
