<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KriteriaKomponen extends Model
{
    protected $table = 'kriteria_komponen';
    protected $guarded = ['id'];
    protected $appends = ['bobot'];

    public function sub_komponen(): BelongsTo
    {
        return $this->belongsTo(SubKomponen::class, 'sub_komponen_id');
    }

    public function komponen(): BelongsTo
    {
        return $this->belongsTo(Komponen::class, 'komponen_id');
    }

    public function jenis_nilai(): BelongsTo
    {
        return $this->belongsTo(JenisNilai::class, 'jenis_nilai_id');
    }

    public function bukti_dukung(): HasMany
    {
        return $this->hasMany(BuktiDukung::class, 'kriteria_komponen_id');
    }

    public function penilaian(): HasMany
    {
        return $this->hasMany(Penilaian::class, 'kriteria_komponen_id');
    }

    /**
     * Preload penilaian data untuk banyak kriteria komponen sekaligus
     * Simpan dalam static cache untuk digunakan oleh getNilai()
     * 
     * @param array $kriteriaIds
     * @param array $opdIds
     * @param array $roleIds
     */
    public static function preloadPenilaian($kriteriaIds, $opdIds, $roleIds)
    {
        // 1. Load penilaian kriteria-level (yang tidak pakai bukti dukung)
        $penilaianKriteria = Penilaian::whereIn('kriteria_komponen_id', $kriteriaIds)
            ->whereIn('opd_id', $opdIds)
            ->whereIn('role_id', $roleIds)
            ->whereNull('bukti_dukung_id')
            ->with('tingkatan_nilai')
            ->get();

        $count = 0;
        foreach ($penilaianKriteria as $penilaian) {
            $key = "{$penilaian->kriteria_komponen_id}_{$penilaian->opd_id}_{$penilaian->role_id}";
            self::$penilaianCacheStatic[$key] = $penilaian;
            $count++;
        }

        // 2. Load semua bukti dukung untuk kriteria ini
        $buktiDukungList = BuktiDukung::whereIn('kriteria_komponen_id', $kriteriaIds)->get();
        $buktiIds = $buktiDukungList->pluck('id')->toArray();

        // Simpan bukti dukung ke cache (key: kriteria_id)
        foreach ($buktiDukungList as $bukti) {
            if (!isset(self::$buktiDukungCache[$bukti->kriteria_komponen_id])) {
                self::$buktiDukungCache[$bukti->kriteria_komponen_id] = [];
            }
            self::$buktiDukungCache[$bukti->kriteria_komponen_id][] = $bukti;
        }

        // Preload count untuk bobot calculation
        $buktiCounts = BuktiDukung::whereIn('kriteria_komponen_id', $kriteriaIds)
            ->selectRaw('kriteria_komponen_id, COUNT(*) as count')
            ->groupBy('kriteria_komponen_id')
            ->get()
            ->pluck('count', 'kriteria_komponen_id')
            ->toArray();
        self::$buktiDukungCountCache = $buktiCounts;

        // 3. Load penilaian bukti-level (yang pakai bukti_dukung_id)
        if (!empty($buktiIds)) {
            $penilaianBukti = Penilaian::whereIn('bukti_dukung_id', $buktiIds)
                ->whereIn('opd_id', $opdIds)
                ->whereIn('role_id', $roleIds)
                ->with('tingkatan_nilai')
                ->get();

            foreach ($penilaianBukti as $penilaian) {
                $key = "bukti_{$penilaian->bukti_dukung_id}_{$penilaian->opd_id}_{$penilaian->role_id}";
                self::$penilaianBuktiCache[$key] = $penilaian;
                $count++;
            }
        }

        return $count; // Return jumlah total penilaian yang di-cache
    }

    /**
     * Get penilaian bukti dari cache
     */
    public static function getPenilaianBuktiCache($key)
    {
        return self::$penilaianBuktiCache[$key] ?? null;
    }

    /**
     * Get bukti dukung count dari cache
     */
    public static function getBuktiDukungCount($kriteriaId)
    {
        return self::$buktiDukungCountCache[$kriteriaId] ?? null;
    }

    // Static cache untuk penilaian
    private static $penilaianCacheStatic = [];
    private static $penilaianBuktiCache = [];
    private static $buktiDukungCache = [];
    private static $buktiDukungCountCache = [];

    // Debug counters
    public static $cacheHits = 0;
    public static $cacheMisses = 0;

    public static function resetCacheStats()
    {
        self::$cacheHits = 0;
        self::$cacheMisses = 0;
    }

    public static function getCacheStats()
    {
        return [
            'hits' => self::$cacheHits,
            'misses' => self::$cacheMisses,
            'total' => self::$cacheHits + self::$cacheMisses,
            'hit_rate' => self::$cacheHits + self::$cacheMisses > 0
                ? round(self::$cacheHits / (self::$cacheHits + self::$cacheMisses) * 100, 2)
                : 0
        ];
    }

    public function penilaian_mandiri(): HasMany
    {
        return $this->hasMany(PenilaianMandiri::class, 'kriteria_komponen_id');
    }

    protected function bobot(): Attribute
    {
        return Attribute::make(
            get: function () {
                // Check if sub_komponen relationship is loaded
                if (!$this->relationLoaded('sub_komponen') || !$this->sub_komponen) {
                    return 0;
                }

                // Check if kriteria_komponen_count exists
                if (
                    !isset($this->sub_komponen->kriteria_komponen_count) ||
                    $this->sub_komponen->kriteria_komponen_count == 0
                ) {
                    return 0;
                }

                return round($this->sub_komponen->bobot / $this->sub_komponen->kriteria_komponen_count, 2);
            }
        );
    }

    /**
     * Hitung nilai kriteria komponen untuk OPD tertentu dan role tertentu
     *
     * Jika penilaian di bukti dukung: SUM nilai semua bukti dukung
     * Jika penilaian di kriteria: tingkatan_nilai.bobot × bobot_kriteria
     *
     * @param int $opdId
     * @param int $roleId
     * @return float
     */
    public function getNilai($opdId, $roleId)
    {
        $cacheKey = "{$this->id}_{$opdId}_{$roleId}";

        // 1. CEK CACHE GLOBAL DULU (dari preload)
        if (isset(self::$penilaianCacheStatic[$cacheKey])) {
            self::$cacheHits++;
            $cachedResult = self::$penilaianCacheStatic[$cacheKey];
            // Jika sudah ada hasil kalkulasi, return langsung
            if (is_numeric($cachedResult)) {
                return $cachedResult;
            }
            // Jika cached berupa object Penilaian, lanjut proses
            $penilaian = $cachedResult;
        } else {
            self::$cacheMisses++;
            // 2. Jika tidak ada di cache global, query
            // Load sub_komponen jika belum
            $subKomponen = $this->sub_komponen ?? SubKomponen::find($this->sub_komponen_id);

            // Cek apakah penilaian di level kriteria atau bukti
            if ($subKomponen && $subKomponen->penilaian_di === 'kriteria') {
                // Mode Kriteria: Ambil penilaian langsung di kriteria
                // Gunakan relasi jika sudah di-eager load
                if ($this->relationLoaded('penilaian')) {
                    $penilaian = $this->penilaian->where('bukti_dukung_id', null)
                        ->where('opd_id', $opdId)
                        ->where('role_id', $roleId)
                        ->first();
                } else {
                    $penilaian = Penilaian::where('kriteria_komponen_id', $this->id)
                        ->whereNull('bukti_dukung_id')
                        ->where('opd_id', $opdId)
                        ->where('role_id', $roleId)
                        ->with('tingkatan_nilai')
                        ->first();
                }

                // CACHE NULL RESULT JUGA!
                if (!$penilaian) {
                    self::$penilaianCacheStatic[$cacheKey] = 0; // Cache as 0
                    return 0;
                }

                $penilaian = $penilaian; // Set for later use
            } else {
                // Mode Bukti: SUM nilai semua bukti dukung
                // Cek cache bukti dukung dulu
                $buktiDukungList = self::$buktiDukungCache[$this->id] ?? null;

                if (!$buktiDukungList) {
                    // Fallback: query jika tidak ada di cache
                    $buktiDukungList = BuktiDukung::where('kriteria_komponen_id', $this->id)
                        ->with('kriteria_komponen')
                        ->get();
                }

                $totalNilai = 0;
                foreach ($buktiDukungList as $buktiDukung) {
                    $totalNilai += $buktiDukung->getNilai($opdId, $roleId);
                }

                $result = round($totalNilai, 2);
                self::$penilaianCacheStatic[$cacheKey] = $result;
                return $result;
            }
        }

        // Load sub_komponen jika belum
        $subKomponen = $this->sub_komponen ?? SubKomponen::find($this->sub_komponen_id);

        if (!$penilaian || !$penilaian->tingkatan_nilai) {
            self::$penilaianCacheStatic[$cacheKey] = 0;
            return 0;
        }

        // Hitung bobot kriteria: sub_komponen.bobot / jumlah kriteria
        // Gunakan withCount jika sudah diload, kalau tidak query
        if (isset($subKomponen->kriteria_komponen_count)) {
            $jumlahKriteria = $subKomponen->kriteria_komponen_count;
        } else {
            $jumlahKriteria = KriteriaKomponen::where('sub_komponen_id', $subKomponen->id)->count();
        }

        if ($jumlahKriteria == 0) {
            return 0;
        }
        $bobotKriteria = $subKomponen->bobot / $jumlahKriteria;

        // Nilai = bobot tingkatan nilai × bobot_kriteria
        // Bobot tingkatan nilai sudah dalam bentuk desimal (0-1), jadi tidak perlu dibagi 100
        $bobotTingkatanNilai = $penilaian->tingkatan_nilai->bobot ?? 0;

        $result = round($bobotTingkatanNilai * $bobotKriteria, 2);
        self::$penilaianCacheStatic[$cacheKey] = $result;
        return $result;
    }

    /**
     * Hitung nilai kriteria komponen per role untuk OPD tertentu
     * Return array dengan role_id sebagai key dan nilai sebagai value
     *
     * @param int $opdId
     * @return array ['role_id' => nilai, 'role_nama' => nama role]
     */
    public function getNilaiPerRole($opdId)
    {
        // Load sub_komponen untuk cek mode penilaian
        $subKomponen = $this->sub_komponen ?? SubKomponen::find($this->sub_komponen_id);

        // Build query berdasarkan mode penilaian
        $query = Penilaian::where('kriteria_komponen_id', $this->id)
            ->where('opd_id', $opdId)
            ->whereNotNull('tingkatan_nilai_id')
            ->with('role');

        // Jika penilaian di kriteria, cari yang bukti_dukung_id NULL
        // Jika penilaian di bukti, cari yang bukti_dukung_id NOT NULL
        if ($subKomponen && $subKomponen->penilaian_di === 'kriteria') {
            $query->whereNull('bukti_dukung_id');
        } else {
            $query->whereNotNull('bukti_dukung_id')
                ->where('bukti_dukung_id', '>', 0);
        }

        $roleList = $query->select('role_id')
            ->distinct()
            ->get()
            ->pluck('role')
            ->filter(); // Remove null values

        $nilaiPerRole = [];
        foreach ($roleList as $role) {
            $nilai = $this->getNilai($opdId, $role->id);
            if ($nilai > 0) { // Hanya tampilkan role yang punya nilai
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
     * Hitung rata-rata nilai dari semua role yang sudah melakukan penilaian
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
