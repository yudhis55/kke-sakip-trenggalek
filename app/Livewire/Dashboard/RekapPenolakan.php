<?php

namespace App\Livewire\Dashboard;

use App\Models\Penilaian;
use App\Models\Role;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class RekapPenolakan extends Component
{
    // Modal keterangan
    public $selectedKeterangan = null;

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

    public function render()
    {
        return view('livewire.dashboard.rekap-penolakan');
    }
}
