<?php

namespace App\Livewire\Dashboard;

use App\Models\BuktiDukung;
use App\Models\Opd;
use App\Models\Penilaian;
use App\Models\PenilaianHistory;
use App\Models\Role;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Session;
use Livewire\Component;

class RekapPenolakan extends Component
{
    // Modal keterangan
    public $selectedKeterangan = null;

    // Filter OPD (untuk role penjamin/penilai)
    public $selected_opd = null;
    public $searchOpd = '';

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
        $penilaianHistory = PenilaianHistory::find($penilaianHistoryId);
        $this->selectedKeterangan = $penilaianHistory?->keterangan ?? 'Tidak ada keterangan';
    }

    #[Computed]
    public function opdList()
    {
        return Opd::orderBy('nama')->get();
    }

    public function updatedSelectedOpd()
    {
        // Reset pagination if needed
    }

    #[Computed]
    public function rekapPenolakan()
    {
        $jenis = Auth::user()->role->jenis;

        // Hanya OPD, penjamin, dan penilai yang dapat mengakses
        if (!in_array($jenis, ['opd', 'penjamin', 'penilai'])) {
            return collect();
        }

        // Ambil semua penilaian yang ditolak (is_verified = 0 atau false)
        // Dari role verifikator atau penjamin
        $verifikatorRoleIds = Role::where('jenis', 'verifikator')->pluck('id')->toArray();
        $penjaminRoleId = Role::where('jenis', 'penjamin')->first()?->id;
        $roleIds = array_merge($verifikatorRoleIds, [$penjaminRoleId]);

        $query = PenilaianHistory::whereIn('role_id', $roleIds)
            ->where('is_verified', 0)
            ->whereNotNull('keterangan')
            ->whereIn('status_perbaikan', ['belum_diperbaiki', 'sudah_diperbaiki']) // Exclude yang sudah diterima
            ->whereHas('kriteria_komponen', function ($q) {
                $q->where('tahun_id', $this->tahun_session);
            })
            ->with([
                'kriteria_komponen.sub_komponen.komponen',
                'bukti_dukung',
                'role',
                'opd',
            ])
            ->orderBy('created_at', 'desc');

        // OPD: forced filter ke opd_id sendiri (preserve existing behavior)
        if ($jenis === 'opd') {
            $query->where('opd_id', Auth::user()->opd_id);
        } elseif ($this->selected_opd) {
            // penjamin/penilai: optional filter berdasarkan selected_opd
            $query->where('opd_id', $this->selected_opd);
        }

        return $query->get();
    }

    #[Computed]
    public function badgeCount()
    {
        $jenis = Auth::user()->role->jenis;

        // Hanya OPD, penjamin, dan penilai yang dapat mengakses
        if (!in_array($jenis, ['opd', 'penjamin', 'penilai'])) {
            return 0;
        }

        // Hitung dokumen yang ditolak dan belum diperbaiki
        $verifikatorRoleIds = Role::where('jenis', 'verifikator')->pluck('id')->toArray();
        $penjaminRoleId = Role::where('jenis', 'penjamin')->first()?->id;
        $roleIds = array_merge($verifikatorRoleIds, [$penjaminRoleId]);

        $query = PenilaianHistory::whereIn('role_id', $roleIds)
            ->where('is_verified', 0)
            ->whereNotNull('keterangan')
            ->where('status_perbaikan', 'belum_diperbaiki')
            ->whereHas('kriteria_komponen', function ($q) {
                $q->where('tahun_id', $this->tahun_session);
            });

        // OPD: forced filter ke opd_id sendiri (preserve existing behavior)
        if ($jenis === 'opd') {
            $query->where('opd_id', Auth::user()->opd_id);
        } elseif ($this->selected_opd) {
            // penjamin/penilai: optional filter berdasarkan selected_opd
            $query->where('opd_id', $this->selected_opd);
        }

        return $query->count();
    }

    public function redirectToBuktiDukung($penilaianHistoryId)
    {
        $penilaianHistory = PenilaianHistory::with(['bukti_dukung', 'kriteria_komponen.sub_komponen.komponen'])
            ->find($penilaianHistoryId);

        if (!$penilaianHistory || !$penilaianHistory->kriteria_komponen) {
            flash()->error('Data penilaian tidak ditemukan');
            return;
        }

        // Set semua session yang diperlukan untuk lembar kerja
        $this->tahun_session = $penilaianHistory->kriteria_komponen->tahun_id;
        $this->opd_session = Auth::user()->opd_id;

        $this->komponen_session = $penilaianHistory->kriteria_komponen->sub_komponen->komponen_id;
        $this->sub_komponen_session = $penilaianHistory->kriteria_komponen->sub_komponen_id;
        $this->kriteria_komponen_session = $penilaianHistory->kriteria_komponen_id;
        $this->redirectRoute('lembar-kerja');
    }

    public function render()
    {
        return view('livewire.dashboard.rekap-penolakan');
    }
}
