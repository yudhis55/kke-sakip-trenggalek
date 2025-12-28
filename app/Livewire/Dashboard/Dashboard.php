<?php

namespace App\Livewire\Dashboard;

use App\Models\BuktiDukung;
use App\Models\KriteriaKomponen;
use App\Models\Komponen;
use App\Models\SubKomponen;
use App\Models\Tahun;
use App\Models\Penilaian;
use App\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Session;

class Dashboard extends Component
{
    #[Session(key: 'tahun_session')]
    public $tahun_session;
    public function render()
    {
        return view('livewire.dashboard.dashboard');
    }

    #[Computed]
    public function tahun()
    {
        return Tahun::find($this->tahun_session);
    }

    #[Computed]
    public function jumlahKomponen()
    {
        return Komponen::where('tahun_id', $this->tahun_session)->count();
    }

    #[Computed]
    public function jumlahSubKomponen()
    {
        return SubKomponen::where('tahun_id', $this->tahun_session)->count();
    }

    #[Computed]
    public function jumlahKriteriaKomponen()
    {
        return KriteriaKomponen::where('tahun_id', $this->tahun_session)->count();
    }

    #[Computed]
    public function jumlahBuktiDukung()
    {
        return BuktiDukung::where('tahun_id', $this->tahun_session)->count();
    }

    /**
     * Hitung jumlah kriteria komponen yang telah dinilai
     * Berdasarkan role: OPD = penilaian mandiri, Verifikator = verifikasi, Penjamin = penjaminan, Penilai = evaluasi
     */
    #[Computed]
    public function jumlahKriteriaKomponenDinilai()
    {
        $userRoleJenis = Auth::user()->role->jenis;

        // Tentukan role_id yang akan dicek
        if ($userRoleJenis == 'admin') {
            // Admin: Lihat semua penilaian mandiri OPD
            $targetRoleId = Role::where('jenis', 'opd')->first()->id;
        } else {
            // Role lain: Lihat penilaian mereka sendiri
            $targetRoleId = Auth::user()->role_id;
        }

        // Query kriteria komponen dengan penilaian_di = 'kriteria'
        $query = DB::table('kriteria_komponen')
            ->join('sub_komponen', 'kriteria_komponen.sub_komponen_id', '=', 'sub_komponen.id')
            ->join('komponen', 'sub_komponen.komponen_id', '=', 'komponen.id')
            ->where('komponen.tahun_id', $this->tahun_session)
            ->where('kriteria_komponen.penilaian_di', 'kriteria');

        // Filter OPD jika role adalah OPD
        if ($userRoleJenis == 'opd') {
            $opdId = Auth::user()->opd_id;

            // Hitung kriteria yang sudah dinilai oleh OPD ini
            $count = $query->whereExists(function ($subquery) use ($targetRoleId, $opdId) {
                $subquery->select(DB::raw(1))
                    ->from('penilaian')
                    ->whereColumn('penilaian.kriteria_komponen_id', 'kriteria_komponen.id')
                    ->where('penilaian.opd_id', $opdId)
                    ->where('penilaian.role_id', $targetRoleId)
                    ->whereNotNull('penilaian.tingkatan_nilai_id');
            })->count();
        } else {
            // Admin, Verifikator, Penjamin, Penilai: Hitung total di semua OPD
            if ($userRoleJenis == 'verifikator') {
                // Verifikator: Cek is_verified
                $count = $query->whereExists(function ($subquery) use ($targetRoleId) {
                    $subquery->select(DB::raw(1))
                        ->from('penilaian')
                        ->whereColumn('penilaian.kriteria_komponen_id', 'kriteria_komponen.id')
                        ->where('penilaian.role_id', $targetRoleId)
                        ->whereNotNull('penilaian.is_verified');
                })->count();
            } else {
                // Admin, Penjamin, Penilai: Cek tingkatan_nilai_id
                $count = $query->whereExists(function ($subquery) use ($targetRoleId) {
                    $subquery->select(DB::raw(1))
                        ->from('penilaian')
                        ->whereColumn('penilaian.kriteria_komponen_id', 'kriteria_komponen.id')
                        ->where('penilaian.role_id', $targetRoleId)
                        ->whereNotNull('penilaian.tingkatan_nilai_id');
                })->count();
            }
        }

        return $count;
    }

    /**
     * Hitung total kriteria komponen dengan penilaian_di = 'kriteria'
     */
    #[Computed]
    public function totalKriteriaKomponenDiKriteria()
    {
        return DB::table('kriteria_komponen')
            ->join('sub_komponen', 'kriteria_komponen.sub_komponen_id', '=', 'sub_komponen.id')
            ->join('komponen', 'sub_komponen.komponen_id', '=', 'komponen.id')
            ->where('komponen.tahun_id', $this->tahun_session)
            ->where('kriteria_komponen.penilaian_di', 'kriteria')
            ->count();
    }

    /**
     * Hitung jumlah bukti dukung yang telah dinilai
     * Berdasarkan role: OPD = penilaian mandiri, Verifikator = verifikasi, Penjamin = penjaminan, Penilai = evaluasi
     */
    #[Computed]
    public function jumlahBuktiDukungDinilai()
    {
        $userRoleJenis = Auth::user()->role->jenis;

        // Tentukan role_id yang akan dicek
        if ($userRoleJenis == 'admin') {
            // Admin: Lihat semua penilaian mandiri OPD
            $targetRoleId = Role::where('jenis', 'opd')->first()->id;
        } else {
            // Role lain: Lihat penilaian mereka sendiri
            $targetRoleId = Auth::user()->role_id;
        }

        // Query bukti dukung dengan penilaian_di = 'bukti'
        $query = DB::table('bukti_dukung')
            ->join('kriteria_komponen', 'bukti_dukung.kriteria_komponen_id', '=', 'kriteria_komponen.id')
            ->join('sub_komponen', 'kriteria_komponen.sub_komponen_id', '=', 'sub_komponen.id')
            ->join('komponen', 'sub_komponen.komponen_id', '=', 'komponen.id')
            ->where('komponen.tahun_id', $this->tahun_session)
            ->where('kriteria_komponen.penilaian_di', 'bukti');

        // Filter OPD jika role adalah OPD
        if ($userRoleJenis == 'opd') {
            $opdId = Auth::user()->opd_id;

            // Hitung bukti dukung yang sudah dinilai oleh OPD ini
            $count = $query->whereExists(function ($subquery) use ($targetRoleId, $opdId) {
                $subquery->select(DB::raw(1))
                    ->from('penilaian')
                    ->whereColumn('penilaian.bukti_dukung_id', 'bukti_dukung.id')
                    ->where('penilaian.opd_id', $opdId)
                    ->where('penilaian.role_id', $targetRoleId)
                    ->whereNotNull('penilaian.tingkatan_nilai_id');
            })->count();
        } else {
            // Admin, Verifikator, Penjamin, Penilai: Hitung total di semua OPD
            if ($userRoleJenis == 'verifikator') {
                // Verifikator: Cek is_verified
                $count = $query->whereExists(function ($subquery) use ($targetRoleId) {
                    $subquery->select(DB::raw(1))
                        ->from('penilaian')
                        ->whereColumn('penilaian.bukti_dukung_id', 'bukti_dukung.id')
                        ->where('penilaian.role_id', $targetRoleId)
                        ->whereNotNull('penilaian.is_verified');
                })->count();
            } else {
                // Admin, Penjamin, Penilai: Cek tingkatan_nilai_id
                $count = $query->whereExists(function ($subquery) use ($targetRoleId) {
                    $subquery->select(DB::raw(1))
                        ->from('penilaian')
                        ->whereColumn('penilaian.bukti_dukung_id', 'bukti_dukung.id')
                        ->where('penilaian.role_id', $targetRoleId)
                        ->whereNotNull('penilaian.tingkatan_nilai_id');
                })->count();
            }
        }

        return $count;
    }

    /**
     * Hitung total bukti dukung dengan penilaian_di = 'bukti'
     */
    #[Computed]
    public function totalBuktiDukungDiBukti()
    {
        return DB::table('bukti_dukung')
            ->join('kriteria_komponen', 'bukti_dukung.kriteria_komponen_id', '=', 'kriteria_komponen.id')
            ->join('sub_komponen', 'kriteria_komponen.sub_komponen_id', '=', 'sub_komponen.id')
            ->join('komponen', 'sub_komponen.komponen_id', '=', 'komponen.id')
            ->where('komponen.tahun_id', $this->tahun_session)
            ->where('kriteria_komponen.penilaian_di', 'bukti')
            ->count();
    }
}
