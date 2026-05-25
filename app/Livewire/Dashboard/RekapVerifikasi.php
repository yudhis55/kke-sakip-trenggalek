<?php

namespace App\Livewire\Dashboard;

use App\Models\BuktiDukung;
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

    public function updatedSelectedOpd()
    {
        // trigger re-render
    }

    public function updatedFilterStatus()
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
        // Hanya verifikator
        if (Auth::user()->role->jenis !== 'verifikator') {
            return collect();
        }

        $verifikatorRoleId = Auth::user()->role_id;
        $opdRoleId = Role::where('jenis', 'opd')->first()?->id;

        if (!$opdRoleId) {
            return collect();
        }

        // Bukti dukung yang assigned ke verifikator ini (role_id match) untuk tahun ini
        $buktiDukungIds = BuktiDukung::where('role_id', $verifikatorRoleId)
            ->when($this->tahun_session, function ($q) {
                $q->where('tahun_id', $this->tahun_session);
            })
            ->pluck('id');

        if ($buktiDukungIds->isEmpty()) {
            return collect();
        }

        // Penilaian OPD yang punya file di bukti dukung tersebut
        $query = Penilaian::with([
            'opd',
            'bukti_dukung.kriteria_komponen.sub_komponen.komponen',
        ])
            ->whereIn('bukti_dukung_id', $buktiDukungIds)
            ->where('role_id', $opdRoleId)
            ->whereNotNull('link_file');

        if ($this->selected_opd) {
            $query->where('opd_id', $this->selected_opd);
        }

        $opdPenilaianList = $query->get();

        // Map status verifikasi untuk setiap penilaian OPD
        $result = $opdPenilaianList->map(function ($p) use ($verifikatorRoleId) {
            $verifPenilaian = Penilaian::where('kriteria_komponen_id', $p->kriteria_komponen_id)
                ->where('opd_id', $p->opd_id)
                ->where('bukti_dukung_id', $p->bukti_dukung_id)
                ->where('role_id', $verifikatorRoleId)
                ->whereNotNull('is_verified')
                ->first();

            $p->verifikasi_status = $verifPenilaian
                ? ($verifPenilaian->is_verified ? 'disetujui' : 'ditolak')
                : 'belum_diverifikasi';
            $p->verifikasi_keterangan = $verifPenilaian?->keterangan;
            $p->verifikasi_tanggal = $verifPenilaian?->updated_at;

            return $p;
        });

        // Apply filter status
        if ($this->filter_status === 'sudah') {
            $result = $result->filter(fn ($p) => in_array($p->verifikasi_status, ['disetujui', 'ditolak']));
        } elseif ($this->filter_status === 'belum') {
            $result = $result->filter(fn ($p) => $p->verifikasi_status === 'belum_diverifikasi');
        }

        return $result->values();
    }

    #[Computed]
    public function statsCount()
    {
        $all = $this->rekapVerifikasi;

        return [
            'total' => count($all),
            'sudah' => $all->filter(fn ($p) => $p->verifikasi_status !== 'belum_diverifikasi')->count(),
            'belum' => $all->filter(fn ($p) => $p->verifikasi_status === 'belum_diverifikasi')->count(),
        ];
    }

    public function render()
    {
        return view('livewire.dashboard.rekap-verifikasi');
    }
}
