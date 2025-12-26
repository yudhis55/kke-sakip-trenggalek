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
use Illuminate\Support\Facades\DB;

class Monitoring extends Component
{
    use WithPagination, WithoutUrlPagination;
    protected $paginationTheme = 'bootstrap';
    #[Session(key: 'opd_session')]
    public $opd_session;
    public $tahun_id;
    public $selected_komponen_id = null;
    public $perPage = 10;
    public $searchOpd = '';

    public function mount()
    {
        $this->tahun_id = session('tahun_session') ?? Tahun::where('is_active', true)->first()->id;
    }

    public function updatedSearchOpd()
    {
        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.dashboard.monitoring');
    }

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

        // Gunakan pagination (10 OPD per halaman) dengan filter pencarian
        $opdList = Opd::when($this->searchOpd, function ($query) {
            $query->where('nama', 'like', '%' . $this->searchOpd . '%');
        })
            ->paginate($this->perPage);
        $opdCollection = $opdList->getCollection();

        $opdIds = $opdCollection->pluck('id')->toArray();

        // Tentukan role_id yang akan difilter berdasarkan jenis role user
        $userRoleJenis = Auth::user()->role->jenis;

        // Admin menampilkan progress penilaian mandiri OPD
        // Role lain menampilkan progress pekerjaan mereka sendiri
        $targetRoleId = null;
        if ($userRoleJenis == 'admin') {
            // Admin melihat progress OPD (penilaian mandiri)
            $targetRoleId = Role::where('jenis', 'opd')->first()->id;
        } else {
            // Verifikator, Penilai, Penjamin melihat progress mereka sendiri
            $targetRoleId = Auth::user()->role_id;
        }

        // STEP 1: Hitung total items yang harus dinilai per OPD
        // Berdasarkan penilaian_di: jika kriteria maka hitung kriteria, jika bukti maka hitung bukti dukung
        $totalItemsPerOpd = [];

        // Get semua kriteria komponen dengan penilaian_di
        $kriteriaList = DB::table('kriteria_komponen')
            ->select('kriteria_komponen.id', 'kriteria_komponen.penilaian_di')
            ->join('sub_komponen', 'kriteria_komponen.sub_komponen_id', '=', 'sub_komponen.id')
            ->join('komponen', 'sub_komponen.komponen_id', '=', 'komponen.id')
            ->where('komponen.tahun_id', $this->tahun_id)
            ->get();

        $totalItems = 0;
        foreach ($kriteriaList as $kriteria) {
            if ($kriteria->penilaian_di == 'kriteria') {
                // Hitung sebagai 1 kriteria
                $totalItems += 1;
            } else {
                // Hitung jumlah bukti dukung untuk kriteria ini
                $buktiCount = DB::table('bukti_dukung')
                    ->where('kriteria_komponen_id', $kriteria->id)
                    ->count();
                $totalItems += $buktiCount;
            }
        }

        // Setiap OPD punya total items yang sama
        foreach ($opdIds as $opdId) {
            $totalItemsPerOpd[$opdId] = $totalItems;
        }

        // STEP 2: Hitung items yang sudah selesai dinilai per OPD
        $itemsSelesaiPerOpd = [];
        $itemsSelesaiOpdMandiri = []; // Untuk progress OPD (role verifikator, penilai, penjamin juga perlu lihat ini)

        // Get role_id OPD untuk hitung progress penilaian mandiri
        $opdRoleId = Role::where('jenis', 'opd')->first()->id;

        foreach ($opdIds as $opdId) {
            $selesai = 0;
            $selesaiOpdMandiri = 0;

            foreach ($kriteriaList as $kriteria) {
                if ($kriteria->penilaian_di == 'kriteria') {
                    // Cek apakah kriteria ini sudah dinilai/diverifikasi (untuk role target)
                    if ($userRoleJenis == 'verifikator') {
                        // Verifikator: cek is_verified
                        $exists = DB::table('penilaian')
                            ->where('kriteria_komponen_id', $kriteria->id)
                            ->where('opd_id', $opdId)
                            ->where('role_id', $targetRoleId)
                            ->whereNotNull('is_verified')
                            ->exists();
                    } else {
                        // Penilai/Penjamin: cek tingkatan_nilai_id
                        $exists = DB::table('penilaian')
                            ->where('kriteria_komponen_id', $kriteria->id)
                            ->where('opd_id', $opdId)
                            ->where('role_id', $targetRoleId)
                            ->whereNotNull('tingkatan_nilai_id')
                            ->exists();
                    }

                    if ($exists) {
                        $selesai += 1;
                    }

                    // Jika bukan admin, hitung juga progress penilaian mandiri OPD
                    if ($userRoleJenis != 'admin') {
                        $existsOpdMandiri = DB::table('penilaian')
                            ->where('kriteria_komponen_id', $kriteria->id)
                            ->where('opd_id', $opdId)
                            ->where('role_id', $opdRoleId)
                            ->whereNotNull('tingkatan_nilai_id')
                            ->exists();

                        if ($existsOpdMandiri) {
                            $selesaiOpdMandiri += 1;
                        }
                    }
                } else {
                    // Hitung bukti dukung yang sudah dinilai/diverifikasi (untuk role target)
                    if ($userRoleJenis == 'verifikator') {
                        // Verifikator: cek is_verified
                        $buktiSelesai = DB::table('penilaian')
                            ->join('bukti_dukung', 'penilaian.bukti_dukung_id', '=', 'bukti_dukung.id')
                            ->where('bukti_dukung.kriteria_komponen_id', $kriteria->id)
                            ->where('penilaian.opd_id', $opdId)
                            ->where('penilaian.role_id', $targetRoleId)
                            ->whereNotNull('penilaian.is_verified')
                            ->distinct()
                            ->count('penilaian.bukti_dukung_id');
                    } else {
                        // Penilai/Penjamin: cek tingkatan_nilai_id
                        $buktiSelesai = DB::table('penilaian')
                            ->join('bukti_dukung', 'penilaian.bukti_dukung_id', '=', 'bukti_dukung.id')
                            ->where('bukti_dukung.kriteria_komponen_id', $kriteria->id)
                            ->where('penilaian.opd_id', $opdId)
                            ->where('penilaian.role_id', $targetRoleId)
                            ->whereNotNull('penilaian.tingkatan_nilai_id')
                            ->distinct()
                            ->count('penilaian.bukti_dukung_id');
                    }

                    $selesai += $buktiSelesai;

                    // Jika bukan admin, hitung juga progress penilaian mandiri OPD
                    if ($userRoleJenis != 'admin') {
                        $buktiSelesaiOpdMandiri = DB::table('penilaian')
                            ->join('bukti_dukung', 'penilaian.bukti_dukung_id', '=', 'bukti_dukung.id')
                            ->where('bukti_dukung.kriteria_komponen_id', $kriteria->id)
                            ->where('penilaian.opd_id', $opdId)
                            ->where('penilaian.role_id', $opdRoleId)
                            ->whereNotNull('penilaian.tingkatan_nilai_id')
                            ->distinct()
                            ->count('penilaian.bukti_dukung_id');

                        $selesaiOpdMandiri += $buktiSelesaiOpdMandiri;
                    }
                }
            }

            $itemsSelesaiPerOpd[$opdId] = $selesai;
            $itemsSelesaiOpdMandiri[$opdId] = $selesaiOpdMandiri;
        }

        // STEP 3: Set progress untuk setiap OPD
        $opdCollection->each(function ($opd) use ($totalItemsPerOpd, $itemsSelesaiPerOpd, $itemsSelesaiOpdMandiri, $userRoleJenis) {
            $total = $totalItemsPerOpd[$opd->id] ?? 0;
            $selesai = $itemsSelesaiPerOpd[$opd->id] ?? 0;
            $selesaiOpdMandiri = $itemsSelesaiOpdMandiri[$opd->id] ?? 0;

            if ($total > 0) {
                $opd->progress = round(($selesai / $total) * 100, 2);

                // Untuk role non-admin, tambahkan juga progress penilaian mandiri OPD
                if ($userRoleJenis != 'admin') {
                    $opd->progress_opd = round(($selesaiOpdMandiri / $total) * 100, 2);
                }
            } else {
                $opd->progress = 0;
                if ($userRoleJenis != 'admin') {
                    $opd->progress_opd = 0;
                }
            }

            // Skip nilai calculation di list view
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
