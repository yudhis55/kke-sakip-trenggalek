<?php

namespace App\Livewire\Dashboard\LembarKerja\KriteriaKomponen;

use Livewire\Attributes\Computed;
use Livewire\Component;
use App\Models\BuktiDukung as BuktiDukungModel;
use App\Models\KriteriaKomponen;
use App\Models\Opd;
use App\Models\Penilaian;
use App\Models\Role;
use App\Models\TingkatanNilai;
use App\Models\Setting;
use Spatie\LivewireFilepond\WithFilePond;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use function Flasher\Prime\flash;



class BuktiDukung extends Component
{
    use WithFilePond;
    public $file_bukti_dukung = [];
    public $kriteria_komponen_id;
    public $opd_id;
    public $bukti_dukung_id;

    // Upload properties
    public $keterangan_upload = '';
    public $is_perubahan = false;
    public $ganti_semua_dokumen = false;

    // Verifikasi properties
    public $is_verified = null;
    public $keterangan_verifikasi = '';

    // Penilaian properties
    public $tingkatan_nilai_id = null;
    public $is_editing_penilaian = false;

    // Tracking properties
    public $selected_bukti_dukung_for_tracking = null;
    // public $current_tab = 'bukti_dukung';

    public function resetBuktiDukungId()
    {
        $this->bukti_dukung_id = null;
    }

    public function mount()
    {
        // Set initial opd_id berdasarkan user yang login atau session
        $this->opd_id = Auth::user()->opd_id ?? session('opd_session');
    }

    #[Computed]
    public function opdList()
    {
        return Opd::all();
    }

    #[Computed]
    public function kriteriaKomponen()
    {
        return KriteriaKomponen::find($this->kriteria_komponen_id);
    }

    /**
     * Computed property untuk cek apakah penilaian dilakukan di level kriteria atau bukti
     */
    #[Computed]
    public function penilaianDiKriteria()
    {
        if (!$this->kriteria_komponen_id) {
            return false;
        }

        $kriteria = $this->kriteriaKomponen;
        if (!$kriteria || !$kriteria->sub_komponen) {
            return false;
        }

        // Jika penilaian_di = 'kriteria', maka true
        // Jika penilaian_di = 'bukti', maka false
        return $kriteria->sub_komponen->penilaian_di === 'kriteria';
    }

    /**
     * Hitung bobot kriteria komponen
     * Bobot = sub_komponen.bobot / jumlah_kriteria
     */
    #[Computed]
    public function bobotKriteria()
    {
        $kriteria = $this->kriteriaKomponen;
        if (!$kriteria || !$kriteria->sub_komponen) {
            return 0;
        }

        $subKomponen = $kriteria->sub_komponen;
        $jumlahKriteria = KriteriaKomponen::where('sub_komponen_id', $subKomponen->id)->count();

        return $jumlahKriteria > 0 ? round($subKomponen->bobot / $jumlahKriteria, 2) : 0;
    }

    /**
     * Hitung bobot per bukti dukung
     * Bobot = bobot_kriteria / jumlah_bukti
     */
    #[Computed]
    public function bobotPerBukti()
    {
        $jumlahBukti = BuktiDukungModel::where('kriteria_komponen_id', $this->kriteria_komponen_id)->count();

        return $jumlahBukti > 0 ? round($this->bobotKriteria / $jumlahBukti, 2) : 0;
    }

    #[Computed]
    public function penilaianTersimpan()
    {
        if (!$this->kriteria_komponen_id) {
            return null;
        }

        $jenis = Auth::user()->role->jenis;
        $roleId = Auth::user()->role_id;

        // Query unified penilaian table dengan filter role
        $query = Penilaian::with(['tingkatan_nilai'])
            ->where('kriteria_komponen_id', $this->kriteria_komponen_id)
            ->where('role_id', $roleId);

        // Untuk OPD, filter juga berdasarkan opd_id
        if ($jenis == 'opd' && $this->opd_id) {
            $query->where('opd_id', $this->opd_id);
        }

        // Untuk penilaian di level kriteria, bukti_dukung_id harus NULL
        if ($this->penilaianDiKriteria) {
            $query->whereNull('bukti_dukung_id');
        } else {
            // Untuk penilaian di level bukti, filter berdasarkan bukti_dukung_id
            if ($this->bukti_dukung_id) {
                $query->where('bukti_dukung_id', $this->bukti_dukung_id);
            }
        }

        return $query->latest()->first();
    }

    /**
     * Computed property untuk cek apakah user dalam rentang akses (untuk UI)
     */
    #[Computed]
    public function dalamRentangAkses()
    {
        $checkResult = $this->cekAksesWaktu();
        return $checkResult['allowed'];
    }

    /**
     * Cek apakah sudah upload bukti dukung (validasi sebelum penilaian/verifikasi)
     */
    #[Computed]
    public function canDoPenilaian()
    {
        if (!$this->opd_id) {
            return [
                'allowed' => false,
                'message' => 'Silakan pilih OPD terlebih dahulu.'
            ];
        }

        $opdRoleId = \App\Models\Role::where('jenis', 'opd')->first()?->id;

        if ($this->penilaianDiKriteria) {
            // Mode Kriteria: SEMUA bukti dukung harus sudah punya file
            $allBuktiDukung = \App\Models\BuktiDukung::where('kriteria_komponen_id', $this->kriteria_komponen_id)->get();

            // Jika tidak ada bukti dukung sama sekali, langsung izinkan penilaian
            if ($allBuktiDukung->isEmpty()) {
                return ['allowed' => true];
            }

            // Jika ada bukti dukung, cek apakah semua sudah diupload
            foreach ($allBuktiDukung as $buktiDukung) {
                // Cek apakah ada penilaian OPD dengan file untuk bukti dukung ini
                $hasFile = Penilaian::where('kriteria_komponen_id', $this->kriteria_komponen_id)
                    ->where('bukti_dukung_id', $buktiDukung->id)
                    ->where('opd_id', $this->opd_id)
                    ->where('role_id', $opdRoleId)
                    ->whereNotNull('link_file')
                    ->exists();

                if (!$hasFile) {
                    return [
                        'allowed' => false,
                        'message' => 'Semua bukti dukung harus diupload terlebih dahulu sebelum dapat melakukan penilaian. Bukti dukung "' . $buktiDukung->nama . '" belum diupload.'
                    ];
                }
            }

            return ['allowed' => true];
        } else {
            // Mode Bukti: Bukti dukung yang dipilih harus sudah punya file
            if (!$this->bukti_dukung_id) {
                return [
                    'allowed' => false,
                    'message' => 'Silakan pilih bukti dukung terlebih dahulu.'
                ];
            }

            // Cek apakah ada penilaian OPD dengan file untuk bukti dukung ini
            $hasFile = Penilaian::where('kriteria_komponen_id', $this->kriteria_komponen_id)
                ->where('bukti_dukung_id', $this->bukti_dukung_id)
                ->where('opd_id', $this->opd_id)
                ->where('role_id', $opdRoleId)
                ->whereNotNull('link_file')
                ->exists();

            if (!$hasFile) {
                return [
                    'allowed' => false,
                    'message' => 'Silakan upload bukti dukung terlebih dahulu sebelum melakukan penilaian atau verifikasi.'
                ];
            }

            return ['allowed' => true];
        }
    }

    /**
     * Ambil semua bukti dukung dengan dokumen untuk mode kriteria (grouped display)
     */
    public function semuaBuktiDukungDenganDokumen()
    {
        if (!$this->penilaianDiKriteria) {
            return collect(); // Kosong jika bukan mode kriteria
        }

        // Ambil semua bukti dukung dengan penilaian OPD yang memiliki file
        $buktiDukungList = \App\Models\BuktiDukung::where('kriteria_komponen_id', $this->kriteria_komponen_id)
            ->orderBy('nama')
            ->get();

        // Untuk setiap bukti dukung, ambil penilaian OPD-nya yang punya file
        $opdRoleId = \App\Models\Role::where('jenis', 'opd')->first()?->id;

        foreach ($buktiDukungList as $bukti) {
            // Query penilaian OPD untuk bukti dukung ini
            $penilaian = Penilaian::where('kriteria_komponen_id', $this->kriteria_komponen_id)
                ->where('bukti_dukung_id', $bukti->id)
                ->where('opd_id', $this->opd_id)
                ->where('role_id', $opdRoleId)
                ->whereNotNull('link_file')
                ->first();

            // Tambahkan relasi virtual untuk compatibility dengan view
            $bukti->penilaian_opd = $penilaian;
        }

        return $buktiDukungList;
    }

    /**
     * Cek apakah user masih dalam rentang waktu akses sesuai role
     */
    private function cekAksesWaktu()
    {
        $setting = Setting::first();
        if (!$setting) {
            return [
                'allowed' => false,
                'message' => 'Pengaturan waktu akses tidak ditemukan.'
            ];
        }

        $jenis = Auth::user()->role->jenis;
        $now = Carbon::now();

        $bukaColumn = null;
        $tutupColumn = null;
        $roleLabel = '';

        switch ($jenis) {
            case 'opd':
                $bukaColumn = 'buka_penilaian_mandiri';
                $tutupColumn = 'tutup_penilaian_mandiri';
                $roleLabel = 'Penilaian Mandiri';
                break;
            case 'verifikator':
                $bukaColumn = 'buka_penilaian_verifikator';
                $tutupColumn = 'tutup_penilaian_verifikator';
                $roleLabel = 'Verifikator';
                break;
            case 'penjamin':
                $bukaColumn = 'buka_penilaian_penjamin';
                $tutupColumn = 'tutup_penilaian_penjamin';
                $roleLabel = 'Penjamin Mutu';
                break;
            case 'penilai':
                $bukaColumn = 'buka_penilaian_penilai';
                $tutupColumn = 'tutup_penilaian_penilai';
                $roleLabel = 'Penilai';
                break;
            case 'admin':
                // Admin tidak ada batasan waktu
                return [
                    'allowed' => true,
                    'message' => ''
                ];
            default:
                return [
                    'allowed' => false,
                    'message' => 'Role tidak memiliki akses.'
                ];
        }

        $buka = $setting->{$bukaColumn} ? Carbon::parse($setting->{$bukaColumn}) : null;
        $tutup = $setting->{$tutupColumn} ? Carbon::parse($setting->{$tutupColumn}) : null;

        if (!$buka || !$tutup) {
            return [
                'allowed' => false,
                'message' => "Waktu akses untuk {$roleLabel} belum dikonfigurasi."
            ];
        }

        if ($now->lt($buka)) {
            return [
                'allowed' => false,
                'message' => "Akses {$roleLabel} belum dibuka. Dimulai pada {$buka->format('d/m/Y H:i')}."
            ];
        }

        if ($now->gt($tutup)) {
            return [
                'allowed' => false,
                'message' => "Akses {$roleLabel} sudah ditutup. Berakhir pada {$tutup->format('d/m/Y H:i')}."
            ];
        }

        return [
            'allowed' => true,
            'message' => ''
        ];
    }

    /**
     * Get tracking data untuk modal tracking
     * Return array dengan 4 tahap: OPD, Verifikator, Penjamin, Penilai
     */
    public function getTrackingData()
    {
        if (!$this->selected_bukti_dukung_for_tracking || !$this->opd_id) {
            return [];
        }

        // Query penilaian untuk kriteria komponen dan bukti dukung yang dipilih
        // Jika penilaian di kriteria: bukti_dukung_id = null
        // Jika penilaian di bukti: bukti_dukung_id = selected_bukti_dukung_for_tracking
        $buktiDukungId = $this->penilaianDiKriteria ? null : $this->selected_bukti_dukung_for_tracking;

        $penilaianQuery = Penilaian::where('kriteria_komponen_id', $this->kriteria_komponen_id)
            ->where('opd_id', $this->opd_id)
            ->with(['role', 'tingkatan_nilai']);

        if ($this->penilaianDiKriteria) {
            $penilaianQuery->whereNull('bukti_dukung_id');
        } else {
            $penilaianQuery->where('bukti_dukung_id', $buktiDukungId);
        }

        $penilaianList = $penilaianQuery->orderBy('created_at', 'asc')->get();

        // Pisahkan berdasarkan role
        $opdPenilaian = $penilaianList->where('role.jenis', 'opd')->first();
        $verifikatorPenilaian = $penilaianList->where('role.jenis', 'verifikator')->first();
        $penjaminPenilaian = $penilaianList->where('role.jenis', 'penjamin')->first();
        $penilaiPenilaian = $penilaianList->where('role.jenis', 'penilai')->first();

        // Build tracking array dengan 4 tahap paten
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
     * Set bukti dukung yang dipilih untuk tracking dan buka modal
     */
    public function showTracking($buktiDukungId)
    {
        $this->selected_bukti_dukung_for_tracking = $buktiDukungId;
        // Modal akan dibuka via JavaScript dengan Alpine.js atau Bootstrap modal
        $this->dispatch('openTrackingModal');
    }

    /**
     * Get nama bukti dukung untuk tracking modal title
     */
    public function getSelectedBuktiDukungName()
    {
        if (!$this->selected_bukti_dukung_for_tracking) {
            return null;
        }

        $buktiDukung = BuktiDukungModel::find($this->selected_bukti_dukung_for_tracking);
        return $buktiDukung ? $buktiDukung->nama : null;
    }

    #[Computed]
    public function buktiDukungList()
    {
        // Set opd_id dari user login atau session
        $this->opd_id = Auth::user()->opd_id ?? session('opd_session');

        $buktiDukungList = BuktiDukungModel::where('kriteria_komponen_id', $this->kriteria_komponen_id)
            ->get();

        // Get role IDs
        $opdRoleId = Role::where('jenis', 'opd')->first()?->id;
        $verifikatorRoleId = Role::where('jenis', 'verifikator')->first()?->id;
        $penjaminRoleId = Role::where('jenis', 'penjamin')->first()?->id;
        $penilaiRoleId = Role::where('jenis', 'penilai')->first()?->id;

        foreach ($buktiDukungList as $bukti) {
            // Penilaian OPD (upload file + penilaian mandiri)
            $penilaianOpd = Penilaian::where('kriteria_komponen_id', $this->kriteria_komponen_id)
                ->where('bukti_dukung_id', $bukti->id)
                ->where('opd_id', $this->opd_id)
                ->where('role_id', $opdRoleId)
                ->with('tingkatan_nilai')
                ->first();

            // Penilaian Verifikator
            $penilaianVerifikator = Penilaian::where('kriteria_komponen_id', $this->kriteria_komponen_id)
                ->where('bukti_dukung_id', $bukti->id)
                ->where('opd_id', $this->opd_id)
                ->where('role_id', $verifikatorRoleId)
                ->first();

            // Penilaian Penjamin (Evaluator)
            $penilaianPenjamin = Penilaian::where('kriteria_komponen_id', $this->kriteria_komponen_id)
                ->where('bukti_dukung_id', $bukti->id)
                ->where('opd_id', $this->opd_id)
                ->where('role_id', $penjaminRoleId)
                ->with('tingkatan_nilai')
                ->first();

            // Penilaian Penilai
            $penilaianPenilai = Penilaian::where('kriteria_komponen_id', $this->kriteria_komponen_id)
                ->where('bukti_dukung_id', $bukti->id)
                ->where('opd_id', $this->opd_id)
                ->where('role_id', $penilaiRoleId)
                ->with('tingkatan_nilai')
                ->first();

            // Attach sebagai virtual properties
            $bukti->penilaian_opd = $penilaianOpd;
            $bukti->penilaian_verifikator = $penilaianVerifikator;
            $bukti->penilaian_penjamin = $penilaianPenjamin;
            $bukti->penilaian_penilai = $penilaianPenilai;
        }

        return $buktiDukungList;
    }

    #[Computed]
    public function selectedBuktiDukung()
    {
        if (!$this->bukti_dukung_id) {
            return null;
        }

        return BuktiDukungModel::find($this->bukti_dukung_id);
    }

    // selectedFileBuktiDukungId tidak lagi dibutuhkan karena tidak ada FK ke file_bukti_dukung
    // #[Computed]
    // public function selectedFileBuktiDukungId()
    // {
    //     ...
    // }

    #[Computed]
    public function selectedFileBuktiDukung()
    {
        if (!$this->bukti_dukung_id || !$this->opd_id) {
            return null;
        }

        // Query dari tabel penilaian untuk role OPD
        $buktiDukungId = $this->penilaianDiKriteria ? null : $this->bukti_dukung_id;

        $penilaian = Penilaian::where('kriteria_komponen_id', $this->kriteria_komponen_id)
            ->where('opd_id', $this->opd_id)
            ->where('role_id', function ($query) {
                $query->select('id')
                    ->from('role')
                    ->where('jenis', 'opd')
                    ->limit(1);
            })
            ->when($this->penilaianDiKriteria, function ($query) {
                $query->whereNull('bukti_dukung_id');
            }, function ($query) use ($buktiDukungId) {
                $query->where('bukti_dukung_id', $buktiDukungId);
            })
            ->first();

        if (!$penilaian || !$penilaian->link_file) {
            return null;
        }

        // link_file sudah auto-decoded dari array cast
        return $penilaian->link_file;
    }

    #[Computed]
    public function riwayatVerifikasi()
    {
        if (!$this->bukti_dukung_id || !$this->opd_id) {
            return collect([]);
        }

        // Query unified penilaian table untuk riwayat verifikasi
        // Filter: role verifikator/penjamin (yang punya is_verified)
        $buktiDukungId = $this->penilaianDiKriteria ? null : $this->bukti_dukung_id;

        return Penilaian::with(['role'])
            ->where('kriteria_komponen_id', $this->kriteria_komponen_id)
            ->where('opd_id', $this->opd_id)
            ->when($this->penilaianDiKriteria, function ($query) {
                $query->whereNull('bukti_dukung_id');
            }, function ($query) use ($buktiDukungId) {
                $query->where('bukti_dukung_id', $buktiDukungId);
            })
            ->whereNotNull('is_verified')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function uploadBuktiDukung()
    {
        // Cek akses waktu
        $aksesCheck = $this->cekAksesWaktu();
        if (!$aksesCheck['allowed']) {
            flash()->use('theme.ruby')->option('position', 'bottom-right')->error($aksesCheck['message']);
            return;
        }

        // Validation rules berbeda untuk mode kriteria vs mode bukti
        $rules = [
            'file_bukti_dukung.*' => 'file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240', // Max 10MB per file
            'opd_id' => 'required|exists:opd,id',
            'keterangan_upload' => 'nullable|string|max:1000',
        ];

        // bukti_dukung_id hanya required untuk mode bukti
        if (!$this->penilaianDiKriteria) {
            $rules['bukti_dukung_id'] = 'required|exists:bukti_dukung,id';
        }

        $this->validate($rules);

        $uploadedFiles = [];

        foreach ($this->file_bukti_dukung as $file) {
            // Simpan file ke storage/app/public/bukti_dukung dengan nama random
            $path = $file->store('bukti_dukung', 'public');

            $uploadedFiles[] = [
                'path' => $path, // Sudah random filename dari Laravel
                'original_name' => $file->getClientOriginalName(), // Nama asli untuk display
            ];
        }

        // Cari record penilaian OPD yang sudah ada
        // Upload dokumen SELALU per bukti_dukung_id (terlepas dari mode penilaian)
        // Mode penilaian hanya mempengaruhi record penilaian/verifikasi, bukan upload dokumen
        $existingPenilaian = Penilaian::where('kriteria_komponen_id', $this->kriteria_komponen_id)
            ->where('bukti_dukung_id', $this->bukti_dukung_id)
            ->where('opd_id', $this->opd_id)
            ->where('role_id', Auth::user()->role_id)
            ->first();

        if ($existingPenilaian) {
            if ($this->ganti_semua_dokumen) {
                // Mode REPLACE: Hapus file lama dari storage
                $oldFiles = $existingPenilaian->link_file ?? [];
                if ($oldFiles) {
                    foreach ($oldFiles as $oldFile) {
                        if (isset($oldFile['path'])) {
                            Storage::disk('public')->delete($oldFile['path']);
                        }
                    }
                }

                // Update dengan file baru saja (replace)
                $existingPenilaian->update([
                    'link_file' => $uploadedFiles,
                    'keterangan' => $this->keterangan_upload,
                    'is_perubahan' => $this->is_perubahan,
                ]);
            } else {
                // Mode APPEND: Gabungkan file lama + file baru
                $oldFiles = $existingPenilaian->link_file ?? [];
                $mergedFiles = array_merge($oldFiles, $uploadedFiles);

                // Update dengan gabungan file lama + baru
                $existingPenilaian->update([
                    'link_file' => $mergedFiles,
                    'keterangan' => $this->keterangan_upload,
                    'is_perubahan' => $this->is_perubahan,
                ]);
            }
        } else {
            // Buat record penilaian baru untuk OPD
            Penilaian::create([
                'bukti_dukung_id' => $this->bukti_dukung_id,
                'kriteria_komponen_id' => $this->kriteria_komponen_id,
                'opd_id' => $this->opd_id,
                'role_id' => Auth::user()->role_id,
                'link_file' => $uploadedFiles,
                'keterangan' => $this->keterangan_upload,
                'is_perubahan' => $this->is_perubahan,
            ]);
        }

        // Simpan message sebelum reset
        $message = $this->ganti_semua_dokumen
            ? 'Berhasil mengganti semua dokumen.'
            : 'Berhasil menambahkan dokumen.';

        // Reset the file input
        $this->file_bukti_dukung = [];
        $this->keterangan_upload = '';
        $this->is_perubahan = false;
        $this->ganti_semua_dokumen = false;

        flash()->use('theme.ruby')->option('position', 'bottom-right')->success($message);
        $this->js('window.location.reload()');
    }

    public function setBuktiDukungId($bukti_dukung_id)
    {
        $this->bukti_dukung_id = $bukti_dukung_id;
    }

    public function deleteFileBuktiDukung()
    {
        // Cek akses waktu
        $aksesCheck = $this->cekAksesWaktu();
        if (!$aksesCheck['allowed']) {
            flash()->use('theme.ruby')->option('position', 'bottom-right')->error($aksesCheck['message']);
            return;
        }

        if (!$this->bukti_dukung_id || !$this->opd_id) {
            flash()->use('theme.ruby')->option('position', 'bottom-right')->error('Bukti dukung tidak ditemukan.');
            return;
        }

        // Cari record penilaian OPD (selalu query dengan bukti_dukung_id)
        $penilaian = Penilaian::where('kriteria_komponen_id', $this->kriteria_komponen_id)
            ->where('bukti_dukung_id', $this->bukti_dukung_id)
            ->where('opd_id', $this->opd_id)
            ->where('role_id', Auth::user()->role_id)
            ->first();

        if ($penilaian && $penilaian->link_file) {
            // Hapus file dari storage
            $files = $penilaian->link_file;
            if ($files) {
                foreach ($files as $file) {
                    if (isset($file['path'])) {
                        Storage::disk('public')->delete($file['path']);
                    }
                }
            }

            // Update record - hapus link_file dan reset data upload
            $penilaian->update([
                'link_file' => null,
                'is_perubahan' => false,
                // Keterangan tetap dipertahankan jika ada keterangan penilaian mandiri
            ]);

            flash()->use('theme.ruby')->option('position', 'bottom-right')->success('File berhasil dihapus.');
            $this->js('window.location.reload()');
        } else {
            flash()->use('theme.ruby')->option('position', 'bottom-right')->error('File tidak ditemukan.');
        }
    }

    public function simpanVerifikasi()
    {
        // Cek akses waktu
        $aksesCheck = $this->cekAksesWaktu();
        if (!$aksesCheck['allowed']) {
            flash()->use('theme.ruby')->option('position', 'bottom-right')->error($aksesCheck['message']);
            return;
        }

        // Validasi upload bukti dukung
        $uploadCheck = $this->canDoPenilaian;
        if (!$uploadCheck['allowed']) {
            flash()->use('theme.ruby')->option('position', 'bottom-right')->error($uploadCheck['message']);
            return;
        }

        $this->validate([
            'is_verified' => 'required|boolean',
            'keterangan_verifikasi' => 'nullable|string|max:1000',
        ]);

        // Cek apakah OPD sudah upload file
        $opdRoleId = Role::where('jenis', 'opd')->first()?->id;
        $opdPenilaian = Penilaian::where('kriteria_komponen_id', $this->kriteria_komponen_id)
            ->where('opd_id', $this->opd_id)
            ->where('role_id', $opdRoleId)
            ->when($this->penilaianDiKriteria, function ($query) {
                $query->whereNull('bukti_dukung_id');
            }, function ($query) {
                $query->where('bukti_dukung_id', $this->bukti_dukung_id);
            })
            ->whereNotNull('link_file')
            ->first();

        if (!$opdPenilaian) {
            flash()->use('theme.ruby')->option('position', 'bottom-right')->error('Belum ada file yang diunggah OPD.');
            return;
        }

        // Cari atau buat record penilaian untuk verifikator
        $buktiDukungId = $this->penilaianDiKriteria ? null : $this->bukti_dukung_id;

        $existingPenilaian = Penilaian::where('kriteria_komponen_id', $this->kriteria_komponen_id)
            ->where('opd_id', $this->opd_id)
            ->where('role_id', Auth::user()->role_id)
            ->when($this->penilaianDiKriteria, function ($query) {
                $query->whereNull('bukti_dukung_id');
            }, function ($query) use ($buktiDukungId) {
                $query->where('bukti_dukung_id', $buktiDukungId);
            })
            ->first();

        if ($existingPenilaian) {
            // Update existing verifikasi
            $existingPenilaian->update([
                'is_verified' => $this->is_verified,
                'keterangan' => $this->keterangan_verifikasi,
            ]);
        } else {
            // Buat record penilaian baru untuk verifikator
            Penilaian::create([
                'bukti_dukung_id' => $buktiDukungId,
                'kriteria_komponen_id' => $this->kriteria_komponen_id,
                'opd_id' => $this->opd_id,
                'role_id' => Auth::user()->role_id,
                'is_verified' => $this->is_verified,
                'keterangan' => $this->keterangan_verifikasi,
                'tingkatan_nilai_id' => null, // Verifikasi tidak pakai tingkatan_nilai_id
            ]);
        }

        // Reset form
        $this->is_verified = null;
        $this->keterangan_verifikasi = '';

        flash()->use('theme.ruby')->option('position', 'bottom-right')->success('Verifikasi berhasil disimpan.');
    }

    public function tingkatanNilaiList()
    {
        if (!$this->kriteria_komponen_id) {
            return collect();
        }

        $kriteriaKomponen = KriteriaKomponen::find($this->kriteria_komponen_id);
        if (!$kriteriaKomponen || !$kriteriaKomponen->jenis_nilai_id) {
            return collect();
        }

        return TingkatanNilai::where('jenis_nilai_id', $kriteriaKomponen->jenis_nilai_id)
            ->orderBy('bobot', 'desc')
            ->get();
    }

    public function editPenilaian()
    {
        $this->is_editing_penilaian = true;
        if ($this->penilaianTersimpan) {
            $this->tingkatan_nilai_id = $this->penilaianTersimpan->tingkatan_nilai_id;
        }
    }

    public function batalEditPenilaian()
    {
        $this->is_editing_penilaian = false;
        $this->tingkatan_nilai_id = null;
    }

    public function simpanPenilaian()
    {
        // Cek akses waktu
        $aksesCheck = $this->cekAksesWaktu();
        if (!$aksesCheck['allowed']) {
            flash()->use('theme.ruby')->option('position', 'bottom-right')->error($aksesCheck['message']);
            return;
        }

        // Validasi upload bukti dukung
        $uploadCheck = $this->canDoPenilaian;
        if (!$uploadCheck['allowed']) {
            flash()->use('theme.ruby')->option('position', 'bottom-right')->error($uploadCheck['message']);
            return;
        }

        $this->validate([
            'tingkatan_nilai_id' => 'required|exists:tingkatan_nilai,id',
        ]);

        if (!$this->kriteria_komponen_id) {
            flash()->use('theme.ruby')->option('position', 'bottom-right')->error('Kriteria komponen tidak ditemukan.');
            return;
        }

        $jenis = Auth::user()->role->jenis;
        $roleId = Auth::user()->role_id;

        try {
            // Untuk OPD, harus ada opd_id
            if ($jenis == 'opd' && !$this->opd_id) {
                flash()->use('theme.ruby')->option('position', 'bottom-right')->error('OPD tidak ditemukan.');
                return;
            }

            // Siapkan data untuk unique constraint (WHERE)
            $uniqueKeys = [
                'kriteria_komponen_id' => $this->kriteria_komponen_id,
                'role_id' => $roleId,
            ];

            // Tambahkan opd_id ke unique keys
            if ($jenis == 'opd') {
                $uniqueKeys['opd_id'] = $this->opd_id;
            } else {
                // Untuk role lain, set opd_id sesuai yang sedang dievaluasi
                $uniqueKeys['opd_id'] = $this->opd_id;
            }

            // Tambahkan bukti_dukung_id jika penilaian di level bukti
            if (!$this->penilaianDiKriteria && $this->bukti_dukung_id) {
                $uniqueKeys['bukti_dukung_id'] = $this->bukti_dukung_id;
            } else {
                // Untuk penilaian di level kriteria, bukti_dukung_id = null
                $uniqueKeys['bukti_dukung_id'] = null;
            }

            // Data yang akan di-update/create
            $updateData = [
                'tingkatan_nilai_id' => $this->tingkatan_nilai_id,
                'is_verified' => null, // Penilaian tidak pakai is_verified
                'keterangan' => null,
                'file_bukti_dukung_id' => null,
            ];

            // Simpan ke unified penilaian table
            Penilaian::updateOrCreate($uniqueKeys, $updateData);

            flash()->use('theme.ruby')->option('position', 'bottom-right')->success('Penilaian berhasil disimpan.');

            // Reset mode edit
            $this->is_editing_penilaian = false;
            $this->tingkatan_nilai_id = null;
        } catch (\Exception $e) {
            flash()->use('theme.ruby')->option('position', 'bottom-right')->error('Gagal menyimpan penilaian: ' . $e->getMessage());
        }
    }

    /**
     * Navigate back to kriteria komponen page
     */
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
