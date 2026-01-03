<?php

namespace App\Livewire\Dashboard;

use App\Models\BuktiDukung;
use App\Models\Penilaian;
use App\Models\Role;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Session;
use Livewire\Component;

class RekapPenolakan extends Component
{
    // Modal keterangan
    public $selectedKeterangan = null;

    // Session untuk redirect ke lembar kerja
    #[Session(key: 'tahun_session')]
    public $tahun_session;
    #[Session(key: 'opd_session')]
    public $opd_session;
    #[Session(key: 'komponen_session')]
    public $komponen_session;
    #[Session(key: 'sub_komponen_session')]
    public $sub_komponen_session;
    #[Session(key: 'kriteria_komponen_session')]
    public $kriteria_komponen_session;

    public function showKeterangan($penilaianId)
    {
        $penilaian = Penilaian::find($penilaianId);
        $this->selectedKeterangan = $penilaian?->keterangan ?? 'Tidak ada keterangan';
    }

    #[Computed]
    public function rekapPenolakan()
    {
        // Hanya untuk OPD
        if (Auth::user()->role->jenis !== 'opd') {
            return collect();
        }

        $opdId = Auth::user()->opd_id;

        // Ambil semua penilaian yang ditolak (is_verified = 0 atau false)
        // Dari role verifikator atau penjamin
        $verifikatorRoleId = Role::where('jenis', 'verifikator')->first()?->id;
        $penjaminRoleId = Role::where('jenis', 'penjamin')->first()?->id;

        return Penilaian::whereIn('role_id', [$verifikatorRoleId, $penjaminRoleId])
            ->where('opd_id', $opdId)
            ->where('is_verified', 0)
            ->whereNotNull('keterangan')
            ->with([
                'kriteria_komponen.sub_komponen.komponen',
                'bukti_dukung',
                'role'
            ])
            ->orderBy('updated_at', 'desc')
            ->get();
    }

    public function redirectToBuktiDukung($penilaianId)
    {
        // $penilaian = Penilaian::with(['bukti_dukung', 'kriteria_komponen'])->find($penilaianId);
        $penilaian = Penilaian::with(['bukti_dukung', 'kriteria_komponen.sub_komponen.komponen'])
            ->find($penilaianId);

        if (!$penilaian || !$penilaian->kriteria_komponen) {
            flash()->error('Data penilaian tidak ditemukan');
            return;
        }

        // Set semua session yang diperlukan untuk lembar kerja
        $this->tahun_session = $penilaian->kriteria_komponen;
        $this->opd_session = Auth::user()->opd_id;

        $this->komponen_session = $penilaian->kriteria_komponen->sub_komponen->komponen_id;
        $this->sub_komponen_session = $penilaian->kriteria_komponen->sub_komponen_id;
        $this->kriteria_komponen_session = $penilaian->kriteria_komponen_id;
        $this->redirectRoute('lembar-kerja');
    }

    public function render()
    {
        return view('livewire.dashboard.rekap-penolakan');
    }
}
