<?php

namespace App\Livewire\Dashboard;

use App\Models\PenilaianHistory;
use App\Models\Role;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Session;
use Livewire\Component;

class RekapPerbaikan extends Component
{
    // Modal keterangan
    public $selectedKeterangan = null;
    public $selectedPenolakan = null;

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

    public function showKeterangan($penilaianHistoryId)
    {
        $penilaianHistory = PenilaianHistory::with(['opd'])->find($penilaianHistoryId);

        if ($penilaianHistory) {
            $this->selectedKeterangan = $penilaianHistory->keterangan ?? 'Tidak ada keterangan';
            $this->selectedPenolakan = $penilaianHistory;
        }
    }

    #[Computed]
    public function rekapPerbaikan()
    {
        // Hanya untuk Verifikator, Penjamin, Penilai
        $allowedRoles = ['verifikator', 'penjamin', 'penilai'];

        if (!in_array(Auth::user()->role->jenis, $allowedRoles)) {
            return collect();
        }

        // Setiap user hanya melihat perbaikan dari dokumen yang mereka sendiri tolak
        return PenilaianHistory::where('role_id', Auth::user()->role_id)
            ->where('is_verified', 0)
            ->whereNotNull('keterangan')
            ->where('status_perbaikan', 'sudah_diperbaiki')
            ->whereHas('kriteria_komponen', function ($query) {
                $query->where('tahun_id', $this->tahun_session);
            })
            ->with([
                'kriteria_komponen.sub_komponen.komponen',
                'bukti_dukung',
                'opd',
                'role'
            ])
            ->orderBy('tanggal_perbaikan', 'desc')
            ->get();
    }

    #[Computed]
    public function badgeCount()
    {
        // Hanya untuk Verifikator, Penjamin, Penilai
        $allowedRoles = ['verifikator', 'penjamin', 'penilai'];

        if (!in_array(Auth::user()->role->jenis, $allowedRoles)) {
            return 0;
        }

        // Setiap user hanya hitung perbaikan dari dokumen yang mereka sendiri tolak
        return PenilaianHistory::where('role_id', Auth::user()->role_id)
            ->where('is_verified', 0)
            ->whereNotNull('keterangan')
            ->where('status_perbaikan', 'sudah_diperbaiki')
            ->whereHas('kriteria_komponen', function ($query) {
                $query->where('tahun_id', $this->tahun_session);
            })
            ->count();
    }

    public function redirectToBuktiDukung($penilaianHistoryId)
    {
        $penilaianHistory = PenilaianHistory::with(['bukti_dukung', 'kriteria_komponen.sub_komponen.komponen', 'opd'])
            ->find($penilaianHistoryId);

        if (!$penilaianHistory || !$penilaianHistory->kriteria_komponen) {
            flash()->error('Data penilaian tidak ditemukan');
            return;
        }

        // Set semua session yang diperlukan untuk lembar kerja
        $this->tahun_session = $penilaianHistory->kriteria_komponen->tahun_id;
        $this->opd_session = $penilaianHistory->opd_id;

        $this->komponen_session = $penilaianHistory->kriteria_komponen->sub_komponen->komponen_id;
        $this->sub_komponen_session = $penilaianHistory->kriteria_komponen->sub_komponen_id;
        $this->kriteria_komponen_session = $penilaianHistory->kriteria_komponen_id;
        $this->redirectRoute('lembar-kerja');
    }

    public function render()
    {
        return view('livewire.dashboard.rekap-perbaikan');
    }
}
