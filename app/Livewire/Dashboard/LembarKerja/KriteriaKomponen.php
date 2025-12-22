<?php

namespace App\Livewire\Dashboard\LembarKerja;

use Livewire\Component;
use Livewire\Attributes\Computed;
use App\Models\KriteriaKomponen as KriteriaKomponenModel;
use App\Models\SubKomponen;

class KriteriaKomponen extends Component
{
    public $sub_komponen_id;
    public $selected_kriteria_for_tracking = null;

    #[Computed]
    public function subKomponen()
    {
        return SubKomponen::find($this->sub_komponen_id);
    }

    #[Computed]
    public function penilaianDiKriteria()
    {
        // Cek sub komponen untuk mengetahui mode penilaian
        return $this->subKomponen?->penilaian_di === 'kriteria';
    }

    /**
     * Hitung bobot sub komponen
     */
    #[Computed]
    public function bobotSubKomponen()
    {
        return $this->subKomponen?->bobot ?? 0;
    }

    /**
     * Hitung bobot per kriteria komponen
     */
    #[Computed]
    public function bobotPerKriteria()
    {
        $totalKriteria = KriteriaKomponenModel::where('sub_komponen_id', $this->sub_komponen_id)->count();
        return $totalKriteria > 0 ? round($this->bobotSubKomponen / $totalKriteria, 2) : 0;
    }

    #[Computed]
    public function kriteriaKomponenList()
    {
        $kriteriaList = KriteriaKomponenModel::where('sub_komponen_id', $this->sub_komponen_id)
            ->with(['bukti_dukung'])
            ->get();

        // Ambil bobot dari Sub Komponen (induk)
        $bobotSubKomponen = $this->subKomponen->bobot ?? 0;

        // Hitung bobot per kriteria komponen berdasarkan bobot sub komponen
        // Formula: bobot_sub_komponen / jumlah_kriteria_komponen
        $totalKriteria = $kriteriaList->count();
        $bobotPerKriteria = $totalKriteria > 0 ? $bobotSubKomponen / $totalKriteria : 0;

        // Get OPD role ID for querying
        $opdRoleId = \App\Models\Role::where('jenis', 'opd')->first()?->id;
        $verifikatorRoleId = \App\Models\Role::where('jenis', 'verifikator')->first()?->id;
        $penjaminRoleId = \App\Models\Role::where('jenis', 'penjamin')->first()?->id;
        $penilaiRoleId = \App\Models\Role::where('jenis', 'penilai')->first()?->id;
        $opdId = auth()->user()->opd_id ?? session('opd_session');

        // Tambahkan perhitungan untuk setiap kriteria
        $kriteriaList->each(function ($kriteria) use ($bobotPerKriteria, $opdRoleId, $opdId, $verifikatorRoleId, $penjaminRoleId, $penilaiRoleId) {
            // Hitung total bukti dukung dan yang sudah upload
            $totalBuktiDukung = $kriteria->bukti_dukung->count();
            $uploadedBuktiDukung = 0;

            foreach ($kriteria->bukti_dukung as $buktiDukung) {
                // Cek apakah bukti dukung ini punya file (dari penilaian OPD)
                $hasPenilaianWithFile = \App\Models\Penilaian::where('kriteria_komponen_id', $kriteria->id)
                    ->where('bukti_dukung_id', $buktiDukung->id)
                    ->where('opd_id', $opdId)
                    ->where('role_id', $opdRoleId)
                    ->whereNotNull('link_file')
                    ->exists();

                if ($hasPenilaianWithFile) {
                    $uploadedBuktiDukung++;
                }
            }

            // Ambil penilaian untuk kriteria (bukti_dukung_id = null untuk mode kriteria)
            $penilaianOpd = \App\Models\Penilaian::where('kriteria_komponen_id', $kriteria->id)
                ->whereNull('bukti_dukung_id')
                ->where('opd_id', $opdId)
                ->where('role_id', $opdRoleId)
                ->with('tingkatan_nilai')
                ->first();

            $penilaianVerifikator = \App\Models\Penilaian::where('kriteria_komponen_id', $kriteria->id)
                ->whereNull('bukti_dukung_id')
                ->where('opd_id', $opdId)
                ->where('role_id', $verifikatorRoleId)
                ->first();

            $penilaianPenjamin = \App\Models\Penilaian::where('kriteria_komponen_id', $kriteria->id)
                ->whereNull('bukti_dukung_id')
                ->where('opd_id', $opdId)
                ->where('role_id', $penjaminRoleId)
                ->with('tingkatan_nilai')
                ->first();

            $penilaianPenilai = \App\Models\Penilaian::where('kriteria_komponen_id', $kriteria->id)
                ->whereNull('bukti_dukung_id')
                ->where('opd_id', $opdId)
                ->where('role_id', $penilaiRoleId)
                ->with('tingkatan_nilai')
                ->first();

            // Set attributes untuk digunakan di view
            $kriteria->bobot_persen = $bobotPerKriteria;
            $kriteria->total_file_bukti_dukung = $totalBuktiDukung;
            $kriteria->uploaded_file_bukti_dukung = $uploadedBuktiDukung;
            $kriteria->persentase_kelengkapan = $totalBuktiDukung > 0 ? ($uploadedBuktiDukung / $totalBuktiDukung) * 100 : 0;

            // Attach penilaian kriteria
            $kriteria->penilaian_opd = $penilaianOpd;
            $kriteria->penilaian_verifikator = $penilaianVerifikator;
            $kriteria->penilaian_penjamin = $penilaianPenjamin;
            $kriteria->penilaian_penilai = $penilaianPenilai;
        });

        return $kriteriaList;
    }

    /**
     * Get tracking data untuk modal tracking (mode kriteria)
     * Return array dengan 4 tahap: OPD, Verifikator, Penjamin, Penilai
     */
    public function getTrackingData()
    {
        if (!$this->selected_kriteria_for_tracking) {
            return [];
        }

        $opdId = auth()->user()->opd_id ?? session('opd_session');
        if (!$opdId) {
            return [];
        }

        // Query penilaian untuk kriteria komponen (bukti_dukung_id = null untuk mode kriteria)
        $penilaianQuery = \App\Models\Penilaian::where('kriteria_komponen_id', $this->selected_kriteria_for_tracking)
            ->where('opd_id', $opdId)
            ->whereNull('bukti_dukung_id') // Mode kriteria
            ->with(['role', 'tingkatan_nilai']);

        $penilaianList = $penilaianQuery->orderBy('created_at', 'asc')->get();

        // Pisahkan berdasarkan role
        $opdPenilaian = $penilaianList->where('role.jenis', 'opd')->first();
        $verifikatorPenilaian = $penilaianList->where('role.jenis', 'verifikator')->first();
        $penjaminPenilaian = $penilaianList->where('role.jenis', 'penjamin')->first();
        $penilaiPenilaian = $penilaianList->where('role.jenis', 'penilai')->first();

        // Build tracking array dengan 4 tahap
        $tracking = [];

        // 1. OPD - Penilaian Mandiri
        $tracking[] = [
            'role' => 'OPD',
            'title' => 'Penilaian Mandiri - OPD',
            'icon' => $opdPenilaian ? ($opdPenilaian->tingkatan_nilai_id ? 'ri-check-line' : 'ri-subtract-line') : 'ri-subtract-line',
            'status' => $opdPenilaian ? ($opdPenilaian->tingkatan_nilai_id ? 'success' : 'null') : 'null',
            'date' => $opdPenilaian ? $opdPenilaian->created_at->format('D, d M Y | H:i') : null,
            'nilai' => $opdPenilaian && $opdPenilaian->tingkatan_nilai ? $opdPenilaian->tingkatan_nilai->kode_nilai : null,
            'keterangan' => $opdPenilaian ? $opdPenilaian->keterangan : null,
        ];

        // 2. Verifikator
        $tracking[] = [
            'role' => 'Verifikator',
            'title' => $verifikatorPenilaian ? ($verifikatorPenilaian->is_verified ? 'DITERIMA - Verifikator' : 'DITOLAK - Verifikator') : 'Verifikator',
            'icon' => $verifikatorPenilaian ? ($verifikatorPenilaian->is_verified ? 'ri-check-line' : 'ri-close-line') : 'ri-subtract-line',
            'status' => $verifikatorPenilaian ? ($verifikatorPenilaian->is_verified ? 'success' : 'danger') : 'null',
            'date' => $verifikatorPenilaian ? $verifikatorPenilaian->updated_at->format('D, d M Y | H:i') : null,
            'nilai' => null,
            'keterangan' => $verifikatorPenilaian ? $verifikatorPenilaian->keterangan : null,
        ];

        // 3. Penjamin - Verifikasi + Penilaian
        $tracking[] = [
            'role' => 'Penjamin',
            'title' => $penjaminPenilaian ? ($penjaminPenilaian->is_verified ? 'DITERIMA - Penjamin' : 'DITOLAK - Penjamin') : 'Penjamin Mutu',
            'icon' => $penjaminPenilaian ? ($penjaminPenilaian->is_verified ? 'ri-check-line' : 'ri-close-line') : 'ri-subtract-line',
            'status' => $penjaminPenilaian ? ($penjaminPenilaian->is_verified ? 'success' : 'danger') : 'null',
            'date' => $penjaminPenilaian ? $penjaminPenilaian->updated_at->format('D, d M Y | H:i') : null,
            'nilai' => $penjaminPenilaian && $penjaminPenilaian->tingkatan_nilai ? $penjaminPenilaian->tingkatan_nilai->kode_nilai : null,
            'keterangan' => $penjaminPenilaian ? $penjaminPenilaian->keterangan : null,
        ];

        // 4. Penilai
        $tracking[] = [
            'role' => 'Penilai',
            'title' => $penilaiPenilaian ? 'DINILAI - Penilai' : 'Penilai',
            'icon' => $penilaiPenilaian ? 'ri-check-line' : 'ri-subtract-line',
            'status' => $penilaiPenilaian ? 'success' : 'null',
            'date' => $penilaiPenilaian ? $penilaiPenilaian->updated_at->format('D, d M Y | H:i') : null,
            'nilai' => $penilaiPenilaian && $penilaiPenilaian->tingkatan_nilai ? $penilaiPenilaian->tingkatan_nilai->kode_nilai : null,
            'keterangan' => null,
        ];

        return $tracking;
    }

    /**
     * Set kriteria yang dipilih untuk tracking dan buka modal
     */
    public function showTracking($kriteriaKomponenId)
    {
        $this->selected_kriteria_for_tracking = $kriteriaKomponenId;
        // Modal akan dibuka otomatis via Bootstrap data-bs-toggle="modal"
    }

    /**
     * Get nama kriteria komponen untuk tracking modal title
     */
    public function getSelectedKriteriaName()
    {
        if (!$this->selected_kriteria_for_tracking) {
            return null;
        }

        $kriteria = KriteriaKomponenModel::find($this->selected_kriteria_for_tracking);
        return $kriteria ? $kriteria->kode . ' - ' . $kriteria->nama : null;
    }

    public function navigateBack()
    {
        $kriteriaKomponen = $this->kriteriaKomponen;
        if ($kriteriaKomponen && $kriteriaKomponen->sub_komponen_id) {
            return $this->redirect(
                route('lembar-kerja.kriteria-komponen', [
                    'sub_komponen_id' => $kriteriaKomponen->sub_komponen_id
                ]),
                // navigate: true
            );
        }
    }
}
