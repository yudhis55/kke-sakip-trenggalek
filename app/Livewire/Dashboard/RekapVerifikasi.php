<?php

namespace App\Livewire\Dashboard;

use App\Models\BuktiDukung;
use App\Models\KriteriaKomponen;
use App\Models\Opd;
use App\Models\Penilaian;
use App\Models\Role;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Session;
use Livewire\Component;

class RekapVerifikasi extends Component
{
    #[Session(key: 'tahun_session')]
    public $tahun_session;

    public $selected_opd = null;
    public $filter_status = 'semua'; // 'semua' | 'sudah' | 'belum'
    public $filter_verifikasi_role = 'sendiri'; // 'sendiri' | 'semua' | 'verifikator' | 'penjamin' | 'penilai'

    public function mount()
    {
        // Penilai default ke 'verifikator' (monitoring bawahan, bukan verifikasi sendiri)
        if (in_array(Auth::user()->role->jenis, ['penjamin', 'penilai'])) {
            $this->filter_verifikasi_role = 'semua';
        }
    }

    public function updatedSelectedOpd()
    {
        // trigger re-render
    }

    public function updatedFilterStatus()
    {
        // trigger re-render
    }

    public function updatedFilterVerifikasiRole()
    {
        // trigger re-render
    }

    #[Computed]
    public function opdList()
    {
        return Opd::orderBy('nama')->get();
    }

    #[Computed]
    public function rekapVerifikasi()
    {
        if (!in_array(Auth::user()->role->jenis, ['verifikator', 'penjamin', 'penilai'])) {
            return collect();
        }

        // Determine which role's verifikasi to check based on filter
        $targetRoleId = Auth::user()->role_id; // default: sendiri
        if ($this->filter_verifikasi_role === 'semua') {
            $targetRoleId = null; // will be handled specially below
        } elseif ($this->filter_verifikasi_role === 'verifikator') {
            $targetRoleId = Role::where('jenis', 'verifikator')->first()?->id;
        } elseif ($this->filter_verifikasi_role === 'penjamin') {
            $targetRoleId = Role::where('jenis', 'penjamin')->first()?->id;
        } elseif ($this->filter_verifikasi_role === 'penilai') {
            $targetRoleId = Role::where('jenis', 'penilai')->first()?->id;
        }
        $verifikatorRoleId = $targetRoleId;
        $opdRoleId = Role::where('jenis', 'opd')->first()?->id;

        if (!$opdRoleId) {
            return collect();
        }

        // Get all bukti_dukung assigned to this verifikator
        $buktiDukungList = BuktiDukung::when($verifikatorRoleId, fn($q) => $q->where('role_id', $verifikatorRoleId))
            ->when($this->tahun_session, fn($q) => $q->where('tahun_id', $this->tahun_session))
            ->with('kriteria_komponen')
            ->get();

        if ($buktiDukungList->isEmpty()) {
            return collect();
        }

        $result = collect();

        // Split by penilaian_di mode
        $buktiModeBukti = $buktiDukungList->filter(
            fn($bd) => $bd->kriteria_komponen && $bd->kriteria_komponen->penilaian_di === 'bukti'
        );
        $buktiModeKriteria = $buktiDukungList->filter(
            fn($bd) => $bd->kriteria_komponen && $bd->kriteria_komponen->penilaian_di === 'kriteria'
        );

        // === MODE BUKTI: per bukti_dukung ===
        if ($buktiModeBukti->isNotEmpty()) {
            $buktiIds = $buktiModeBukti->pluck('id');

            $query = Penilaian::with(['opd', 'bukti_dukung.kriteria_komponen.sub_komponen.komponen'])
                ->whereIn('bukti_dukung_id', $buktiIds)
                ->where('role_id', $opdRoleId)
                ->whereNotNull('link_file');

            if ($this->selected_opd) {
                $query->where('opd_id', $this->selected_opd);
            }

            foreach ($query->get() as $p) {
                $verifPenilaian = Penilaian::where('kriteria_komponen_id', $p->kriteria_komponen_id)
                    ->where('opd_id', $p->opd_id)
                    ->where('bukti_dukung_id', $p->bukti_dukung_id)
                    ->when($verifikatorRoleId, fn($q) => $q->where('role_id', $verifikatorRoleId))
                    ->when(!$verifikatorRoleId, fn($q) => $q->whereIn('role_id', Role::whereIn('jenis', ['verifikator', 'penjamin', 'penilai'])->pluck('id')))
                    ->whereNotNull('is_verified')
                    ->first();

                $item = new \stdClass();
                $item->type = 'bukti';
                $item->opd = $p->opd;
                $item->opd_id = $p->opd_id;
                $item->kriteria_komponen = $p->bukti_dukung?->kriteria_komponen;
                $item->bukti_dukung = $p->bukti_dukung;
                $item->bukti_dukung_list = collect([$p]);
                $item->file_count = is_array($p->link_file) ? count($p->link_file) : 0;
                $item->verifikasi_status = $verifPenilaian
                    ? ($verifPenilaian->is_verified ? 'disetujui' : 'ditolak')
                    : 'belum_diverifikasi';
                $item->verifikasi_keterangan = $verifPenilaian?->keterangan;
                $item->verifikasi_tanggal = $verifPenilaian?->updated_at;

                $result->push($item);
            }
        }

        // === MODE KRITERIA: per kriteria_komponen per OPD (grouped) ===
        if ($buktiModeKriteria->isNotEmpty()) {
            $kriteriaIds = $buktiModeKriteria->pluck('kriteria_komponen_id')->unique();

            foreach ($kriteriaIds as $kriteriaId) {
                $kriteria = KriteriaKomponen::with('sub_komponen.komponen')->find($kriteriaId);
                if (!$kriteria) {
                    continue;
                }

                $buktiIdsForKriteria = $buktiModeKriteria
                    ->where('kriteria_komponen_id', $kriteriaId)
                    ->pluck('id');

                // Find OPDs that uploaded files for any bukti in this kriteria
                $opdPenilaians = Penilaian::where('kriteria_komponen_id', $kriteriaId)
                    ->whereIn('bukti_dukung_id', $buktiIdsForKriteria)
                    ->where('role_id', $opdRoleId)
                    ->whereNotNull('link_file')
                    ->when($this->selected_opd, fn($q) => $q->where('opd_id', $this->selected_opd))
                    ->with(['opd', 'bukti_dukung'])
                    ->get()
                    ->groupBy('opd_id');

                foreach ($opdPenilaians as $opdId => $penilaians) {
                    // Check verifikasi at KRITERIA level (bukti_dukung_id = NULL)
                    $verifPenilaian = Penilaian::where('kriteria_komponen_id', $kriteriaId)
                        ->where('opd_id', $opdId)
                        ->where('role_id', $verifikatorRoleId)
                        ->whereNull('bukti_dukung_id')
                        ->whereNotNull('is_verified')
                        ->first();

                    $item = new \stdClass();
                    $item->type = 'kriteria';
                    $item->opd = $penilaians->first()->opd;
                    $item->opd_id = $opdId;
                    $item->kriteria_komponen = $kriteria;
                    $item->bukti_dukung = null;
                    $item->bukti_dukung_list = $penilaians;
                    $item->file_count = $penilaians->sum(
                        fn($p) => is_array($p->link_file) ? count($p->link_file) : 0
                    );
                    $item->verifikasi_status = $verifPenilaian
                        ? ($verifPenilaian->is_verified ? 'disetujui' : 'ditolak')
                        : 'belum_diverifikasi';
                    $item->verifikasi_keterangan = $verifPenilaian?->keterangan;
                    $item->verifikasi_tanggal = $verifPenilaian?->updated_at;

                    $result->push($item);
                }
            }
        }

        // Apply filter status
        if ($this->filter_status === 'sudah') {
            $result = $result->filter(fn($item) => in_array($item->verifikasi_status, ['disetujui', 'ditolak']));
        } elseif ($this->filter_status === 'belum') {
            $result = $result->filter(fn($item) => $item->verifikasi_status === 'belum_diverifikasi');
        }

        return $result->values();
    }

    #[Computed]
    public function statsCount()
    {
        $all = $this->rekapVerifikasi;

        return [
            'total' => count($all),
            'sudah' => $all->filter(fn($item) => $item->verifikasi_status !== 'belum_diverifikasi')->count(),
            'belum' => $all->filter(fn($item) => $item->verifikasi_status === 'belum_diverifikasi')->count(),
        ];
    }

    // Session properties untuk redirect ke lembar kerja
    #[Session(key: 'opd_session')]
    public $opd_session;
    #[Session(key: 'komponen_session')]
    public $komponen_session;
    #[Session(key: 'sub_komponen_session')]
    public $sub_komponen_session;
    #[Session(key: 'kriteria_komponen_session')]
    public $kriteria_komponen_session;

    /**
     * Redirect ke lembar kerja dengan set session yang tepat.
     * Set OPD, komponen, sub_komponen, kriteria_komponen agar LembarKerja langsung tampil di lokasi yang benar.
     */
    public function redirectToKriteria($opdId, $kriteriaKomponenId)
    {
        $kriteria = KriteriaKomponen::with('sub_komponen.komponen')->find($kriteriaKomponenId);

        if (!$kriteria) {
            flash()->use('theme.ruby')->option('position', 'bottom-right')->error('Data kriteria tidak ditemukan.');
            return;
        }

        // Set semua session yang diperlukan untuk lembar kerja
        $this->opd_session = $opdId;
        $this->komponen_session = $kriteria->sub_komponen->komponen_id;
        $this->sub_komponen_session = $kriteria->sub_komponen_id;
        $this->kriteria_komponen_session = $kriteriaKomponenId;

        return $this->redirectRoute('lembar-kerja');
    }

    public function render()
    {
        return view('livewire.dashboard.rekap-verifikasi');
    }
}
