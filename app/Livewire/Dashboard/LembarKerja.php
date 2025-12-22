<?php

namespace App\Livewire\Dashboard;

use Livewire\Attributes\Computed;
use Livewire\Component;
use App\Models\Komponen;
use App\Models\KriteriaKomponen;
use App\Models\SubKomponen;
use App\Models\Tahun;
use Illuminate\Support\Facades\Auth;
use App\Models\Opd;
use Livewire\Attributes\Session;
use App\Models\Role;
use Livewire\WithoutUrlPagination;
use Livewire\WithPagination;

class LembarKerja extends Component
{
    use WithPagination, WithoutUrlPagination;
    protected $paginationTheme = 'bootstrap';
    #[Session(key: 'opd_session')]
    public $opd_session;
    public $tahun_id;
    public $selected_komponen_id = null;
    public $perPage = 10;

    public function mount()
    {
        $this->tahun_id = session('tahun_session') ?? Tahun::where('is_active', true)->first()->id;
    }

    // public function render()
    // {
    //     return view('livewire.dashboard.lembar-kerja');
    // }

    public function selectOpd($opdId)
    {
        $this->opd_session = $opdId;
        session(['opd_session' => $opdId]);
        // Reset komponen selection saat ganti OPD
        $this->selected_komponen_id = null;
    }

    public function selectKomponen($komponenId)
    {
        $this->selected_komponen_id = $komponenId;
        // unset($this->subKomponenOptions);
    }

    public function backToKomponen()
    {
        $this->selected_komponen_id = null;
        // unset($this->subKomponenOptions);
    }

    public function backToOpd()
    {
        $this->opd_session = null;
        $this->selected_komponen_id = null;
        session()->forget('opd_session');
        // Clear cache computed properties
        // unset($this->komponenOptions, $this->subKomponenOptions);
    }

    #[Computed]
    public function opdList()
    {
        // Hanya load untuk role non-OPD
        if (Auth::user()->role->jenis == 'opd') {
            return collect([]); // Return empty collection untuk OPD
        }

        // Gunakan pagination (10 OPD per halaman)
        $opdList = Opd::paginate($this->perPage);
        $opdCollection = $opdList->getCollection();

        // Preload ALL penilaian data untuk semua OPD sekaligus
        $komponenList = Komponen::where('tahun_id', $this->tahun_id)
            ->with(['sub_komponen.kriteria_komponen'])
            ->get();

        // Collect semua kriteria_id, opd_id, dan role_id yang dibutuhkan
        $kriteriaIds = [];
        foreach ($komponenList as $komponen) {
            foreach ($komponen->sub_komponen as $subKomponen) {
                foreach ($subKomponen->kriteria_komponen as $kriteria) {
                    $kriteriaIds[] = $kriteria->id;
                }
            }
        }

        $opdIds = $opdCollection->pluck('id')->toArray();
        $roleIds = Role::whereIn('jenis', ['opd', 'penilai', 'penjamin'])->pluck('id')->toArray();

        // Preload semua penilaian sekaligus (1 query saja!)
        $cachedCount = 0;
        if (!empty($kriteriaIds)) {
            $cachedCount = KriteriaKomponen::preloadPenilaian($kriteriaIds, $opdIds, $roleIds);

            // OPTIMASI: Skip calculation jika tidak ada data penilaian
            if ($cachedCount == 0) {
                $opdCollection->each(function ($opd) {
                    $opd->progress = 0;
                    $opd->nilai_total = 0;
                });
                return $opdList;
            }
        }

        // OPTIMASI BESAR: Batch calculate progress untuk semua OPD sekaligus
        // Gunakan single query dengan GROUP BY
        $progressData = \DB::table('kriteria_komponen')
            ->selectRaw('penilaian.opd_id, COUNT(kriteria_komponen.id) as total, COUNT(DISTINCT penilaian.kriteria_komponen_id) as dinilai')
            ->leftJoin('penilaian', function ($join) use ($opdIds) {
                $join->on('kriteria_komponen.id', '=', 'penilaian.kriteria_komponen_id')
                    ->whereIn('penilaian.opd_id', $opdIds)
                    ->whereNotNull('penilaian.tingkatan_nilai_id');
            })
            ->join('komponen', 'kriteria_komponen.komponen_id', '=', 'komponen.id')
            ->where('komponen.tahun_id', $this->tahun_id)
            ->groupBy('penilaian.opd_id')
            ->get()
            ->keyBy('opd_id');

        // Set progress dan nilai untuk setiap OPD
        $opdCollection->each(function ($opd) use ($progressData) {
            // Set progress dari batch calculation
            $stats = $progressData->get($opd->id);
            if ($stats && $stats->total > 0) {
                $opd->progress = round(($stats->dinilai / $stats->total) * 100, 2);
            } else {
                $opd->progress = 0;
            }

            // Skip nilai calculation di list view (terlalu kompleks)
            // Nilai akan dihitung saat detail dibuka (lazy load)
            $opd->nilai_total = 0;
        });

        return $opdList;
    }

    #[Computed]
    public function kriteriaKomponenList()
    {
        // return Opd::find($this->opd_session)->kriteria_komponen()->where('tahun_id', $this->tahun_id)->get();
        // return KriteriaKomponen::whereHas('komponen', function ($query) {
        //     $query->where('tahun_id', $this->tahun_id);

        //     if (Auth::user()->role->jenis != 'opd' && Auth::user()->role->jenis != 'penjamin' && Auth::user()->role->jenis != 'penilai' && Auth::user()->role->jenis != 'admin') {
        //         $query->where('role_id', Auth::user()->role_id);
        //     }
        // })->get();
        return KriteriaKomponen::all();
    }

    #[Computed]
    public function komponenOptions()
    {
        $query = Komponen::where('tahun_id', $this->tahun_id)
            ->with([
                'sub_komponen' => function ($q) {
                    $q->withCount('kriteria_komponen');
                },
                'sub_komponen.kriteria_komponen',
                'sub_komponen.kriteria_komponen.sub_komponen' => function ($q) {
                    $q->withCount('kriteria_komponen');
                }
            ]);

        // Filter role: verifikator bisa akses semua komponen
        if (!in_array(Auth::user()->role->jenis, ['opd', 'penjamin', 'penilai', 'admin', 'verifikator'])) {
            $query->where('role_id', Auth::user()->role_id);
        }

        $komponenList = $query->get();
        $opdId = Auth::user()->opd_id ?? $this->opd_session;

        if (!$opdId) {
            return $komponenList;
        }

        // Collect semua kriteria_id yang dibutuhkan
        $kriteriaIds = [];
        foreach ($komponenList as $komponen) {
            foreach ($komponen->sub_komponen as $subKomponen) {
                foreach ($subKomponen->kriteria_komponen as $kriteria) {
                    $kriteriaIds[] = $kriteria->id;
                }
            }
        }

        $roleIds = Role::whereIn('jenis', ['opd', 'penilai', 'penjamin'])->pluck('id')->toArray();

        // Preload semua penilaian sekaligus
        if (!empty($kriteriaIds)) {
            $cachedCount = KriteriaKomponen::preloadPenilaian($kriteriaIds, [$opdId], $roleIds);

            // OPTIMASI: Jika tidak ada penilaian sama sekali, skip nilai calculation
            if ($cachedCount == 0) {
                // Set nilai default tanpa query
                $komponenList->each(function ($komponen) {
                    $komponen->nilai_rata_rata = 0;
                    $komponen->progress = 0;
                });
                return $komponenList;
            }
        }

        // Tambahkan data nilai untuk setiap komponen
        $komponenList->each(function ($komponen) use ($opdId) {
            $komponen->nilai_rata_rata = $komponen->getNilaiRataRata($opdId);
            $komponen->progress = $komponen->getProgress($opdId);
        });

        return $komponenList;
    }

    #[Computed]
    public function subKomponenOptions()
    {
        $query = SubKomponen::where('tahun_id', $this->tahun_id)
            ->with(['kriteria_komponen']);

        // Filter berdasarkan komponen yang dipilih
        if ($this->selected_komponen_id) {
            $query->where('komponen_id', $this->selected_komponen_id);
        }

        $subKomponenList = $query->get();
        $opdId = Auth::user()->opd_id ?? $this->opd_session;

        if (!$opdId) {
            return $subKomponenList;
        }

        // Collect semua kriteria_id yang dibutuhkan
        $kriteriaIds = [];
        foreach ($subKomponenList as $subKomponen) {
            foreach ($subKomponen->kriteria_komponen as $kriteria) {
                $kriteriaIds[] = $kriteria->id;
            }
        }

        $roleIds = Role::whereIn('jenis', ['opd', 'penilai', 'penjamin'])->pluck('id')->toArray();

        // Preload semua penilaian sekaligus
        $cachedCount = 0;
        if (!empty($kriteriaIds)) {
            $cachedCount = KriteriaKomponen::preloadPenilaian($kriteriaIds, [$opdId], $roleIds);

            // OPTIMASI: Skip calculation jika tidak ada data penilaian
            if ($cachedCount == 0) {
                $subKomponenList->each(function ($subKomponen) {
                    $subKomponen->nilai_rata_rata = 0;
                    $subKomponen->progress = 0;
                });
                return $subKomponenList;
            }
        }

        // Tambahkan data nilai untuk setiap sub komponen
        $subKomponenList->each(function ($subKomponen) use ($opdId) {
            $subKomponen->nilai_rata_rata = $subKomponen->getNilaiRataRata($opdId);
            $subKomponen->progress = $subKomponen->getProgress($opdId);
        });

        return $subKomponenList;
    }
}
