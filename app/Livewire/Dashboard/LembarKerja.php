<?php

namespace App\Livewire\Dashboard;

use App\Models\Opd;
use Livewire\Component;
use Livewire\WithoutUrlPagination;
use Livewire\WithPagination;
use Livewire\Attributes\Session;
use App\Models\Tahun;
use Illuminate\Support\Facades\Auth;
use App\Models\Komponen;
use App\Models\SubKomponen;
use App\Models\KriteriaKomponen;
use App\Models\BuktiDukung;
use App\Models\Penilaian;
use App\Models\Role;
use App\Models\TingkatanNilai;
use App\Models\Setting;
use App\Models\FileBuktiDukung;
use App\Models\PenilaianHistory;
use Livewire\Attributes\Computed;
use Spatie\LivewireFilepond\WithFilePond;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use function Flasher\Prime\flash;

class LembarKerja extends Component
{
    use WithPagination, WithoutUrlPagination, WithFilePond;

    protected $paginationTheme = 'bootstrap';

    #[Session(key: 'opd_session')]
    public $opd_session;
    #[Session(key: 'komponen_session')]
    public $komponen_session;
    #[Session(key: 'sub_komponen_session')]
    public $sub_komponen_session;
    #[Session(key: 'kriteria_komponen_session')]
    public $kriteria_komponen_session;

    public $tahun_id;
    public $perPage = 10;
    public $searchOpd = '';

    // Bukti dukung & file upload
    public $bukti_dukung_id;
    public $file_bukti_dukung = [];
    public $keterangan_upload = '';
    public $is_perubahan = false;
    public $ganti_semua_dokumen = false;
    public $is_final = false;

    // Penilaian properties
    public $tingkatan_nilai_id = null;
    public $catatan_penilaian = '';
    public $is_editing_penilaian = false;

    // Verifikasi properties
    public $is_verified = null;
    public $keterangan_verifikasi = '';

    // Tracking
    public $selected_bukti_dukung_for_tracking = null;

    public function mount()
    {
        $this->tahun_id = session('tahun_session') ?? Tahun::where('is_active', true)->first()->id;

        // Auto-select OPD jika role = opd
        if (Auth::user()->role->jenis == 'opd') {
            $this->opd_session = Auth::user()->opd_id;
        }
    }

    public function updatedSearchOpd()
    {
        $this->resetPage();
    }

    // Reset state saat filter berubah
    public function updatedKomponenSession()
    {
        // Reset filter cascade
        $this->sub_komponen_session = null;
        $this->kriteria_komponen_session = null;

        // Reset state form
        $this->resetStateOnFilterChange();
    }

    public function updatedSubKomponenSession()
    {
        // Reset filter cascade
        $this->kriteria_komponen_session = null;

        // Reset state form
        $this->resetStateOnFilterChange();
    }

    public function updatedKriteriaKomponenSession()
    {
        // Reset state form
        $this->resetStateOnFilterChange();
    }

    private function resetStateOnFilterChange()
    {
        // Reset bukti dukung selection
        $this->bukti_dukung_id = null;

        // Reset penilaian form
        $this->tingkatan_nilai_id = null;
        $this->catatan_penilaian = '';
        $this->is_editing_penilaian = false;
        $this->is_final = false;

        // Reset verifikasi form
        $this->is_verified = null;
        $this->keterangan_verifikasi = '';

        // Reset file upload
        $this->file_bukti_dukung = [];
        $this->keterangan_upload = '';
        $this->is_perubahan = false;
        $this->ganti_semua_dokumen = false;

        // Dispatch event ke Alpine untuk reset tab
        $this->dispatch('filter-changed');
    }

    /**
     * Reset form states after successful actions
     * Keeps bukti_dukung_id intact so user stays on the same context
     */
    private function resetFormStates()
    {
        // Reset file upload states
        $this->file_bukti_dukung = [];
        $this->keterangan_upload = '';
        $this->is_perubahan = false;
        $this->ganti_semua_dokumen = false;

        // Reset penilaian states
        $this->tingkatan_nilai_id = null;
        $this->catatan_penilaian = '';
        $this->is_editing_penilaian = false;
        $this->is_final = false;

        // Reset verifikasi states
        $this->is_verified = null;
        $this->keterangan_verifikasi = '';
    }

    public function resetBuktiDukungId()
    {
        $this->bukti_dukung_id = null;
    }

    public function setBuktiDukungId($buktiDukungId)
    {
        $this->bukti_dukung_id = $buktiDukungId;
    }

    public function showTracking($buktiDukungId)
    {
        $this->selected_bukti_dukung_for_tracking = $buktiDukungId;
        $this->dispatch('openTrackingModal');
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

    public function resetOpd()
    {
        $this->opd_session = null;
        $this->komponen_session = null;
        $this->sub_komponen_session = null;
        $this->kriteria_komponen_session = null;
    }

    public function navigateBack()
    {
        $this->kriteria_komponen_session = null;
        $this->bukti_dukung_id = null;
    }

    public function selectOpd($opdId)
    {
        $this->opd_session = $opdId;
        $this->komponen_session = null;
        $this->sub_komponen_session = null;
        $this->kriteria_komponen_session = null;
    }
    public function selectKomponen($komponenId)
    {
        $this->komponen_session = $komponenId;
        $this->sub_komponen_session = null;
        $this->kriteria_komponen_session = null;
    }

    public function selectSubKomponen($subKomponenId)
    {
        $this->sub_komponen_session = $subKomponenId;
        $this->kriteria_komponen_session = null;
    }

    public function selectKriteriaKomponen($kriteriaKomponenId)
    {
        $this->kriteria_komponen_session = $kriteriaKomponenId;
    }

    #[Computed]
    public function opdList()
    {
        if (Auth::user()->role->jenis == 'opd') {
            return Opd::where('id', Auth::user()->opd_id)->get();
        }

        $opdList = Opd::when($this->searchOpd, function ($query) {
            $query->where('nama', 'like', '%' . $this->searchOpd . '%');
        })->paginate($this->perPage);

        return $opdList;
    }

    #[Computed]
    public function komponenList()
    {
        $query = Komponen::where('tahun_id', $this->tahun_id);

        // Filter untuk verifikator: hanya tampilkan komponen sesuai role_id
        if (Auth::user()->role->jenis == 'verifikator') {
            $query->where('role_id', Auth::user()->role_id);
        }

        return $query->get();
    }

    #[Computed]
    public function subKomponenList()
    {
        if ($this->komponen_session) {
            return SubKomponen::where('komponen_id', $this->komponen_session)->get();
        }
        return collect();
    }

    #[Computed]
    public function kriteriaKomponenList()
    {
        if ($this->sub_komponen_session) {
            return KriteriaKomponen::where('sub_komponen_id', $this->sub_komponen_session)->get();
        }
        return collect();
    }


    public function lembarKerjaList()
    {
        if ($this->kriteria_komponen_session) {
            return BuktiDukung::where('kriteria_komponen_id', $this->kriteria_komponen_session)->get();
        } elseif ($this->sub_komponen_session) {
            $subKomponen = SubKomponen::withCount('kriteria_komponen')->find($this->sub_komponen_session);
            if ($subKomponen) {
                return $subKomponen->kriteria_komponen()->with(['sub_komponen' => function ($query) {
                    $query->withCount('kriteria_komponen');
                }])->get();
            }
            return collect();
        } elseif ($this->komponen_session) {
            $komponen = Komponen::find($this->komponen_session);
            return $komponen ? $komponen->sub_komponen : collect();
        } else {
            // Default: tampilkan semua komponen (khusus verifikator: filter role_id)
            $query = Komponen::where('tahun_id', $this->tahun_id);
            if (Auth::user()->role->jenis == 'verifikator') {
                $query->where('role_id', Auth::user()->role_id);
            }
            return $query->get();
        }
    }

    public function cardTitle()
    {
        if ($this->kriteria_komponen_session) {
            $kriteriaKomponen = KriteriaKomponen::find($this->kriteria_komponen_session);
            return $kriteriaKomponen ? $kriteriaKomponen->kode . " - Kriteria: " . $kriteriaKomponen->nama : "Lembar Kerja";
        } elseif ($this->sub_komponen_session) {
            $subKomponen = SubKomponen::find($this->sub_komponen_session);
            return $subKomponen ? $subKomponen->kode .  " - Sub Komponen: " . $subKomponen->nama : "Lembar Kerja";
        } elseif ($this->komponen_session) {
            $komponen = Komponen::find($this->komponen_session);
            return $komponen ? $komponen->kode . " - Komponen: " . $komponen->nama : "Lembar Kerja";
        } else {
            return;
        }
    }

    public function opdName()
    {
        if ($this->opd_session) {
            $opd = Opd::find($this->opd_session);
            return $opd ? $opd->nama : "";
        }
        return "";
    }

    /**
     * Get kriteria komponen yang sedang dipilih
     */
    #[Computed]
    public function kriteriaKomponen()
    {
        if (!$this->kriteria_komponen_session) {
            return null;
        }
        return KriteriaKomponen::find($this->kriteria_komponen_session);
    }

    /**
     * Cek apakah penilaian dilakukan di level kriteria atau bukti
     */
    #[Computed]
    public function penilaianDiKriteria()
    {
        if (!$this->kriteria_komponen_session) {
            return false;
        }

        $kriteria = $this->kriteriaKomponen;
        if (!$kriteria) {
            return false;
        }

        // Sekarang penilaian_di ada di kriteria_komponen, bukan sub_komponen
        return $kriteria->penilaian_di === 'kriteria';
    }

    /**
     * Hitung bobot kriteria komponen
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
     */
    #[Computed]
    public function bobotPerBukti()
    {
        if (!$this->kriteria_komponen_session) {
            return 0;
        }

        $jumlahBukti = BuktiDukung::where('kriteria_komponen_id', $this->kriteria_komponen_session)->count();
        return $jumlahBukti > 0 ? round($this->bobotKriteria / $jumlahBukti, 2) : 0;
    }

    /**
     * Get list bukti dukung untuk kriteria yang dipilih
     */
    #[Computed]
    public function buktiDukungList()
    {
        if (!$this->kriteria_komponen_session || !$this->opd_session) {
            return collect();
        }

        $buktiDukungList = BuktiDukung::where('kriteria_komponen_id', $this->kriteria_komponen_session)
            ->get();

        // Get role IDs
        $opdRoleId = Role::where('jenis', 'opd')->first()?->id;
        $verifikatorRoleId = Role::where('jenis', 'verifikator')->first()?->id;
        $penjaminRoleId = Role::where('jenis', 'penjamin')->first()?->id;
        $penilaiRoleId = Role::where('jenis', 'penilai')->first()?->id;

        foreach ($buktiDukungList as $bukti) {
            // Penilaian OPD
            $penilaianOpd = Penilaian::where('kriteria_komponen_id', $this->kriteria_komponen_session)
                ->where('bukti_dukung_id', $bukti->id)
                ->where('opd_id', $this->opd_session)
                ->where('role_id', $opdRoleId)
                ->with('tingkatan_nilai')
                ->first();

            // Penilaian Verifikator
            $penilaianVerifikator = Penilaian::where('kriteria_komponen_id', $this->kriteria_komponen_session)
                ->where('bukti_dukung_id', $bukti->id)
                ->where('opd_id', $this->opd_session)
                ->where('role_id', $verifikatorRoleId)
                ->first();

            // Penilaian Penjamin
            $penilaianPenjamin = Penilaian::where('kriteria_komponen_id', $this->kriteria_komponen_session)
                ->where('bukti_dukung_id', $bukti->id)
                ->where('opd_id', $this->opd_session)
                ->where('role_id', $penjaminRoleId)
                ->with('tingkatan_nilai')
                ->first();

            // Penilaian Penilai
            $penilaianPenilai = Penilaian::where('kriteria_komponen_id', $this->kriteria_komponen_session)
                ->where('bukti_dukung_id', $bukti->id)
                ->where('opd_id', $this->opd_session)
                ->where('role_id', $penilaiRoleId)
                ->with('tingkatan_nilai')
                ->first();

            // Attach penilaian ke bukti dukung
            $bukti->penilaian_opd = $penilaianOpd;
            $bukti->penilaian_verifikator = $penilaianVerifikator;
            $bukti->penilaian_penjamin = $penilaianPenjamin;
            $bukti->penilaian_penilai = $penilaianPenilai;
        }

        return $buktiDukungList;
    }

    /**
     * Get bukti dukung yang dipilih
     */
    #[Computed]
    public function selectedBuktiDukung()
    {
        if (!$this->bukti_dukung_id) {
            return null;
        }
        return BuktiDukung::find($this->bukti_dukung_id);
    }

    /**
     * Get file bukti dukung yang dipilih untuk ditampilkan
     */
    #[Computed]
    public function selectedFileBuktiDukung()
    {
        if (!$this->bukti_dukung_id || !$this->opd_session) {
            return [];
        }

        $opdRoleId = Role::where('jenis', 'opd')->first()?->id;

        $penilaian = Penilaian::where('kriteria_komponen_id', $this->kriteria_komponen_session)
            ->where('bukti_dukung_id', $this->bukti_dukung_id)
            ->where('opd_id', $this->opd_session)
            ->where('role_id', $opdRoleId)
            ->first();

        if (!$penilaian || !$penilaian->link_file) {
            return [];
        }

        // link_file sudah auto-decoded karena cast di model
        $files = $penilaian->link_file;
        if (!is_array($files)) {
            return [];
        }

        return $files;
    }

    /**
     * Get penilaian tersimpan untuk user saat ini
     */
    #[Computed]
    public function penilaianTersimpan()
    {
        if (!$this->kriteria_komponen_session || !$this->opd_session) {
            return null;
        }

        $jenis = Auth::user()->role->jenis;
        $roleId = Auth::user()->role_id;

        // Query unified penilaian table dengan filter role
        $query = Penilaian::with(['tingkatan_nilai'])
            ->where('kriteria_komponen_id', $this->kriteria_komponen_session)
            ->where('opd_id', $this->opd_session)
            ->where('role_id', $roleId);

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
     * Ambil semua bukti dukung dengan dokumen (untuk mode kriteria)
     */
    public function semuaBuktiDukungDenganDokumen()
    {
        if (!$this->penilaianDiKriteria || !$this->opd_session) {
            return collect();
        }

        $buktiDukungList = BuktiDukung::where('kriteria_komponen_id', $this->kriteria_komponen_session)
            ->orderBy('nama')
            ->get();

        $opdRoleId = Role::where('jenis', 'opd')->first()?->id;

        foreach ($buktiDukungList as $bukti) {
            $penilaian = Penilaian::where('kriteria_komponen_id', $this->kriteria_komponen_session)
                ->where('bukti_dukung_id', $bukti->id)
                ->where('opd_id', $this->opd_session)
                ->where('role_id', $opdRoleId)
                ->whereNotNull('link_file')
                ->first();

            $bukti->penilaian_opd = $penilaian;
        }

        return $buktiDukungList;
    }

    /**
     * Cek apakah dalam rentang akses
     */
    #[Computed]
    public function dalamRentangAkses()
    {
        $checkResult = $this->cekAksesWaktu();
        return $checkResult['allowed'];
    }

    /**
     * Cek apakah bisa melakukan penilaian
     */
    #[Computed]
    public function canDoPenilaian()
    {
        if (!$this->opd_session) {
            return [
                'allowed' => false,
                'message' => 'Silakan pilih OPD terlebih dahulu.'
            ];
        }

        $opdRoleId = Role::where('jenis', 'opd')->first()?->id;

        if ($this->penilaianDiKriteria) {
            // Mode Kriteria: SEMUA bukti dukung harus sudah punya file
            $allBuktiDukung = BuktiDukung::where('kriteria_komponen_id', $this->kriteria_komponen_session)->get();

            if ($allBuktiDukung->isEmpty()) {
                return ['allowed' => true];
            }

            foreach ($allBuktiDukung as $buktiDukung) {
                $hasFile = Penilaian::where('kriteria_komponen_id', $this->kriteria_komponen_session)
                    ->where('bukti_dukung_id', $buktiDukung->id)
                    ->where('opd_id', $this->opd_session)
                    ->where('role_id', $opdRoleId)
                    ->whereNotNull('link_file')
                    ->exists();

                if (!$hasFile) {
                    return [
                        'allowed' => false,
                        'message' => 'Semua bukti dukung harus diupload terlebih dahulu. Bukti "' . $buktiDukung->nama . '" belum diupload.'
                    ];
                }
            }

            return ['allowed' => true];
        } else {
            // Mode Bukti: Bukti yang dipilih harus sudah punya file
            if (!$this->bukti_dukung_id) {
                return [
                    'allowed' => false,
                    'message' => 'Silakan pilih bukti dukung terlebih dahulu.'
                ];
            }

            $hasFile = Penilaian::where('kriteria_komponen_id', $this->kriteria_komponen_session)
                ->where('bukti_dukung_id', $this->bukti_dukung_id)
                ->where('opd_id', $this->opd_session)
                ->where('role_id', $opdRoleId)
                ->whereNotNull('link_file')
                ->exists();

            if (!$hasFile) {
                return [
                    'allowed' => false,
                    'message' => 'Silakan upload bukti dukung terlebih dahulu.'
                ];
            }

            return ['allowed' => true];
        }
    }

    /**
     * Get list tingkatan nilai berdasarkan jenis nilai kriteria
     */
    public function tingkatanNilaiList()
    {
        if (!$this->kriteria_komponen_session) {
            return collect();
        }

        $kriteriaKomponen = KriteriaKomponen::find($this->kriteria_komponen_session);
        if (!$kriteriaKomponen || !$kriteriaKomponen->jenis_nilai_id) {
            return collect();
        }

        return TingkatanNilai::where('jenis_nilai_id', $kriteriaKomponen->jenis_nilai_id)
            ->orderBy('bobot', 'desc')
            ->get();
    }

    /**
     * Get riwayat verifikasi
     */
    #[Computed]
    public function riwayatVerifikasi()
    {
        if (!$this->kriteria_komponen_session || !$this->opd_session) {
            return collect([]);
        }

        // Query unified penilaian table untuk riwayat verifikasi
        // Filter: role verifikator/penjamin (yang punya is_verified)
        $buktiDukungId = $this->penilaianDiKriteria ? null : $this->bukti_dukung_id;

        return Penilaian::with(['role'])
            ->where('kriteria_komponen_id', $this->kriteria_komponen_session)
            ->where('opd_id', $this->opd_session)
            ->when($this->penilaianDiKriteria, function ($query) {
                $query->whereNull('bukti_dukung_id');
            }, function ($query) use ($buktiDukungId) {
                $query->where('bukti_dukung_id', $buktiDukungId);
            })
            ->whereNotNull('is_verified')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Cek akses waktu berdasarkan role
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
     * Upload bukti dukung
     */
    public function uploadBuktiDukung()
    {
        $this->validate([
            'file_bukti_dukung' => 'required|array',
            'file_bukti_dukung.*' => 'file|mimes:pdf',
            'keterangan_upload' => 'nullable|string',
        ]);

        if (!$this->bukti_dukung_id) {
            flash()->use('theme.ruby')->option('position', 'bottom-right')->error('Silakan pilih bukti dukung terlebih dahulu.');
            return;
        }

        $opdRoleId = Role::where('jenis', 'opd')->first()?->id;

        // Upload files terlebih dahulu
        $uploadedFiles = [];
        foreach ($this->file_bukti_dukung as $file) {
            $path = $file->store('bukti_dukung', 'public');

            $uploadedFiles[] = [
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
            ];
        }

        // Cek penilaian existing
        $penilaian = Penilaian::where('kriteria_komponen_id', $this->kriteria_komponen_session)
            ->where('bukti_dukung_id', $this->bukti_dukung_id)
            ->where('opd_id', $this->opd_session)
            ->where('role_id', $opdRoleId)
            ->first();

        $isPerubahan = $penilaian !== null;

        if (!$penilaian) {
            $penilaian = Penilaian::create([
                'kriteria_komponen_id' => $this->kriteria_komponen_session,
                'bukti_dukung_id' => $this->bukti_dukung_id,
                'opd_id' => $this->opd_session,
                'role_id' => $opdRoleId,
                'link_file' => $uploadedFiles,
                'keterangan' => $this->keterangan_upload,
                'is_perubahan' => $this->is_perubahan,
            ]);
        } else {
            // Jika ganti_semua_dokumen dicentang, hapus file lama dari storage
            if ($this->ganti_semua_dokumen) {
                $oldFiles = $penilaian->link_file ?? [];
                if (is_array($oldFiles)) {
                    foreach ($oldFiles as $oldFile) {
                        if (isset($oldFile['path'])) {
                            Storage::disk('public')->delete($oldFile['path']);
                        }
                    }
                }
                // Ganti dengan file baru saja
                $mergedFiles = $uploadedFiles;
            } else {
                // Gabungkan dengan file lama
                $oldFiles = $penilaian->link_file ?? [];

                // Pastikan $oldFiles adalah array (handle jika data lama masih string)
                if (!is_array($oldFiles)) {
                    $oldFiles = [];
                }

                $mergedFiles = array_merge($oldFiles, $uploadedFiles);
            }

            $penilaian->update([
                'link_file' => $mergedFiles,
                'keterangan' => $this->keterangan_upload,
                'is_perubahan' => $this->is_perubahan,
            ]);
        }

        // Record history - Upload dokumen
        $penilaian->recordHistory(
            userId: Auth::id(),
            roleId: $opdRoleId,
            opdId: $this->opd_session,
            kriteriaKomponenId: $this->kriteria_komponen_session,
            buktiDukungId: $this->bukti_dukung_id,
            tingkatanNilaiId: $penilaian->tingkatan_nilai_id,
            isVerified: null,
            keterangan: $this->keterangan_upload ?: 'Upload ' . count($uploadedFiles) . ' file bukti dukung',
            isPerubahan: $isPerubahan
        );

        flash()->use('theme.ruby')->option('position', 'bottom-right')->success('Bukti dukung berhasil diupload.');

        // Reset all form states after successful upload
        $this->resetFormStates();
    }

    /**
     * Simpan penilaian
     */
    public function simpanPenilaian()
    {
        $this->validate([
            'tingkatan_nilai_id' => 'required|exists:tingkatan_nilai,id',
        ]);

        $jenis = Auth::user()->role->jenis;
        $roleId = Auth::user()->role_id;

        $buktiDukungId = $this->penilaianDiKriteria ? null : $this->bukti_dukung_id;

        // Cek apakah ini update atau create
        $existingPenilaian = Penilaian::where([
            'kriteria_komponen_id' => $this->kriteria_komponen_session,
            'bukti_dukung_id' => $buktiDukungId,
            'opd_id' => $this->opd_session,
            'role_id' => $roleId,
        ])->first();

        $isPerubahan = $existingPenilaian !== null;

        // Update/create penilaian
        // Keterangan tidak di-update di sini, karena keterangan hanya dari upload dokumen
        // Ini untuk preserve keterangan yang sudah ada dari proses upload
        $penilaian = Penilaian::updateOrCreate(
            [
                'kriteria_komponen_id' => $this->kriteria_komponen_session,
                'bukti_dukung_id' => $buktiDukungId,
                'opd_id' => $this->opd_session,
                'role_id' => $roleId,
            ],
            [
                'tingkatan_nilai_id' => $this->tingkatan_nilai_id,
                'is_final' => $this->is_final,
            ]
        );

        // Record history
        $penilaian->recordHistory(
            userId: Auth::id(),
            roleId: $roleId,
            opdId: $this->opd_session,
            kriteriaKomponenId: $this->kriteria_komponen_session,
            buktiDukungId: $buktiDukungId,
            tingkatanNilaiId: $this->tingkatan_nilai_id,
            isVerified: null,
            keterangan: $isPerubahan ? 'Update penilaian' : 'Penilaian awal',
            isPerubahan: $isPerubahan
        );

        flash()->use('theme.ruby')->option('position', 'bottom-right')->success('Penilaian berhasil disimpan.');

        // Reset penilaian form states
        $this->tingkatan_nilai_id = null;
        $this->catatan_penilaian = '';
        $this->is_editing_penilaian = false;
        $this->is_final = false;
    }

    /**
     * Hapus nilai penilaian (set tingkatan_nilai_id ke null)
     * Keterangan dari upload dokumen tetap tersimpan
     */
    public function hapusNilai()
    {
        // Cek akses waktu
        $aksesCheck = $this->cekAksesWaktu();
        if (!$aksesCheck['allowed']) {
            flash()->use('theme.ruby')->option('position', 'bottom-right')->error($aksesCheck['message']);
            return;
        }

        $jenis = Auth::user()->role->jenis;
        $roleId = Auth::user()->role_id;

        $buktiDukungId = $this->penilaianDiKriteria ? null : $this->bukti_dukung_id;

        // Cari penilaian existing
        $penilaian = Penilaian::where([
            'kriteria_komponen_id' => $this->kriteria_komponen_session,
            'bukti_dukung_id' => $buktiDukungId,
            'opd_id' => $this->opd_session,
            'role_id' => $roleId,
        ])->first();

        if (!$penilaian || !$penilaian->tingkatan_nilai_id) {
            flash()->use('theme.ruby')->option('position', 'bottom-right')->error('Tidak ada nilai yang dapat dihapus.');
            return;
        }

        $oldTingkatanNilaiId = $penilaian->tingkatan_nilai_id;

        // Hapus nilai penilaian (set ke null), keterangan tetap tersimpan
        $penilaian->update([
            'tingkatan_nilai_id' => null,
            'is_final' => false,
        ]);

        // Record history
        $penilaian->recordHistory(
            userId: Auth::id(),
            roleId: $roleId,
            opdId: $this->opd_session,
            kriteriaKomponenId: $this->kriteria_komponen_session,
            buktiDukungId: $buktiDukungId,
            tingkatanNilaiId: null,
            isVerified: null,
            keterangan: 'Menghapus penilaian',
            isPerubahan: true
        );

        flash()->use('theme.ruby')->option('position', 'bottom-right')->success('Nilai penilaian berhasil dihapus.');

        // Reset form states
        $this->tingkatan_nilai_id = null;
        $this->is_editing_penilaian = false;
        $this->is_final = false;
    }

    /**
     * Simpan verifikasi
     */
    public function simpanVerifikasi()
    {
        $this->validate([
            'is_verified' => 'required|boolean',
            'keterangan_verifikasi' => 'nullable|string',
        ]);

        $jenis = Auth::user()->role->jenis;
        $roleId = Auth::user()->role_id;

        $buktiDukungId = $this->penilaianDiKriteria ? null : $this->bukti_dukung_id;

        // Cek apakah ini update atau create
        $existingPenilaian = Penilaian::where([
            'kriteria_komponen_id' => $this->kriteria_komponen_session,
            'bukti_dukung_id' => $buktiDukungId,
            'opd_id' => $this->opd_session,
            'role_id' => $roleId,
        ])->first();

        $isPerubahan = $existingPenilaian !== null;

        $penilaian = Penilaian::updateOrCreate(
            [
                'kriteria_komponen_id' => $this->kriteria_komponen_session,
                'bukti_dukung_id' => $buktiDukungId,
                'opd_id' => $this->opd_session,
                'role_id' => $roleId,
            ],
            [
                'is_verified' => $this->is_verified,
                'keterangan' => $this->keterangan_verifikasi,
            ]
        );

        // Record history
        $penilaian->recordHistory(
            userId: Auth::id(),
            roleId: $roleId,
            opdId: $this->opd_session,
            kriteriaKomponenId: $this->kriteria_komponen_session,
            buktiDukungId: $buktiDukungId,
            tingkatanNilaiId: null,
            isVerified: $this->is_verified,
            keterangan: $this->keterangan_verifikasi,
            isPerubahan: $isPerubahan
        );

        flash()->use('theme.ruby')->option('position', 'bottom-right')->success('Verifikasi berhasil disimpan.');

        // Reset verifikasi form states
        $this->is_verified = null;
        $this->keterangan_verifikasi = '';
    }

    /**
     * Delete file bukti dukung
     */
    public function deleteFileBuktiDukung()
    {
        // Cek akses waktu
        $aksesCheck = $this->cekAksesWaktu();
        if (!$aksesCheck['allowed']) {
            flash()->use('theme.ruby')->option('position', 'bottom-right')->error($aksesCheck['message']);
            return;
        }

        if (!$this->bukti_dukung_id || !$this->opd_session) {
            flash()->use('theme.ruby')->option('position', 'bottom-right')->error('Bukti dukung tidak ditemukan.');
            return;
        }

        // Cari record penilaian OPD
        $penilaian = Penilaian::where('kriteria_komponen_id', $this->kriteria_komponen_session)
            ->where('bukti_dukung_id', $this->bukti_dukung_id)
            ->where('opd_id', $this->opd_session)
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

            // Update record - hapus link_file, reset data upload, dan hapus nilai penilaian
            $penilaian->update([
                'link_file' => null,
                'is_perubahan' => false,
                'keterangan' => null,
                'tingkatan_nilai_id' => null,  // Reset nilai penilaian juga
                'is_final' => false,
            ]);

            // Record history - Hapus dokumen dan nilai
            $penilaian->recordHistory(
                userId: Auth::id(),
                roleId: Auth::user()->role_id,
                opdId: $this->opd_session,
                kriteriaKomponenId: $this->kriteria_komponen_session,
                buktiDukungId: $this->bukti_dukung_id,
                tingkatanNilaiId: null,
                isVerified: $penilaian->is_verified,
                keterangan: 'Menghapus semua file bukti dukung dan penilaian',
                isPerubahan: true
            );

            flash()->use('theme.ruby')->option('position', 'bottom-right')->success('File bukti dukung dan penilaian berhasil dihapus.');
        } else {
            flash()->use('theme.ruby')->option('position', 'bottom-right')->error('File tidak ditemukan.');
        }
    }

    /**
     * Get tracking data untuk modal tracking
     */
    public function getTrackingData()
    {
        if (!$this->selected_bukti_dukung_for_tracking || !$this->opd_session) {
            return [];
        }

        // Jika penilaian di kriteria: bukti_dukung_id = null
        // Jika penilaian di bukti: bukti_dukung_id = selected_bukti_dukung_for_tracking
        $buktiDukungId = $this->penilaianDiKriteria ? null : $this->selected_bukti_dukung_for_tracking;

        $penilaianQuery = Penilaian::where('kriteria_komponen_id', $this->kriteria_komponen_session)
            ->where('opd_id', $this->opd_session)
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

        // Hitung nilai numerik untuk setiap role yang punya nilai
        $calculateNilaiNumerik = function ($penilaian) use ($buktiDukungId) {
            if (!$penilaian || !$penilaian->tingkatan_nilai) {
                return null;
            }

            if ($this->penilaianDiKriteria) {
                // Penilaian di kriteria: gunakan getNilai dari KriteriaKomponen
                $kriteria = KriteriaKomponen::find($this->kriteria_komponen_session);
                return $kriteria ? $kriteria->getNilai($this->opd_session, $penilaian->role_id) : null;
            } else {
                // Penilaian di bukti: gunakan getNilai dari BuktiDukung
                $bukti = BuktiDukung::find($buktiDukungId);
                return $bukti ? $bukti->getNilai($this->opd_session, $penilaian->role_id) : null;
            }
        };

        // Build tracking array dengan 4 tahap paten
        $tracking = [];

        // 1. OPD - Penilaian Mandiri
        $nilaiNumerikOpd = $calculateNilaiNumerik($opdPenilaian);
        $tracking[] = [
            'role' => 'OPD',
            'title' => 'Penilaian Mandiri - OPD',
            'icon' => $opdPenilaian ? ($opdPenilaian->tingkatan_nilai_id ? 'ri-check-line' : 'ri-subtract-line') : 'ri-subtract-line',
            'status' => $opdPenilaian ? ($opdPenilaian->tingkatan_nilai_id ? 'success' : 'null') : 'null',
            'date' => $opdPenilaian ? $opdPenilaian->created_at->format('D, d M Y | H:i') : null,
            'nilai' => $opdPenilaian && $opdPenilaian->tingkatan_nilai ? $opdPenilaian->tingkatan_nilai->kode_nilai : null,
            'nilai_numerik' => $nilaiNumerikOpd,
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
            'nilai_numerik' => null,
            'keterangan' => $verifikatorPenilaian ? $verifikatorPenilaian->keterangan : null,
        ];

        // 3. Penjamin - Verifikasi + Penilaian
        $nilaiNumerikPenjamin = $calculateNilaiNumerik($penjaminPenilaian);
        $tracking[] = [
            'role' => 'Evaluator',
            'title' => $penjaminPenilaian ? ($penjaminPenilaian->is_verified ? 'DITERIMA - Evaluator' : 'DITOLAK - Evaluator') : 'Evaluator',
            'icon' => $penjaminPenilaian ? ($penjaminPenilaian->is_verified ? 'ri-check-line' : 'ri-close-line') : 'ri-subtract-line',
            'status' => $penjaminPenilaian ? ($penjaminPenilaian->is_verified ? 'success' : 'danger') : 'null',
            'date' => $penjaminPenilaian ? $penjaminPenilaian->updated_at->format('D, d M Y | H:i') : null,
            'nilai' => $penjaminPenilaian && $penjaminPenilaian->tingkatan_nilai ? $penjaminPenilaian->tingkatan_nilai->kode_nilai : null,
            'nilai_numerik' => $nilaiNumerikPenjamin,
            'keterangan' => $penjaminPenilaian ? $penjaminPenilaian->keterangan : null,
        ];

        // 4. Penilai
        $nilaiNumerikPenilai = $calculateNilaiNumerik($penilaiPenilaian);
        $tracking[] = [
            'role' => 'Penjamin Kualitas',
            'title' => $penilaiPenilaian ? 'DINILAI - Penjamin Kualitas' : 'Penjamin Kualitas',
            'icon' => $penilaiPenilaian ? 'ri-check-line' : 'ri-subtract-line',
            'status' => $penilaiPenilaian ? 'success' : 'null',
            'date' => $penilaiPenilaian ? $penilaiPenilaian->updated_at->format('D, d M Y | H:i') : null,
            'nilai' => $penilaiPenilaian && $penilaiPenilaian->tingkatan_nilai ? $penilaiPenilaian->tingkatan_nilai->kode_nilai : null,
            'nilai_numerik' => $nilaiNumerikPenilai,
            'keterangan' => null,
        ];

        return $tracking;
    }

    /**
     * Get nama bukti dukung yang dipilih untuk tracking modal title
     */
    public function getSelectedBuktiDukungName()
    {
        if (!$this->selected_bukti_dukung_for_tracking) {
            return null;
        }

        $buktiDukung = BuktiDukung::find($this->selected_bukti_dukung_for_tracking);
        return $buktiDukung ? $buktiDukung->nama : null;
    }

    /**
     * Get history penilaian untuk ditampilkan di tabel
     */
    #[Computed]
    public function getHistoryPenilaian()
    {
        if (!$this->kriteria_komponen_session || !$this->opd_session) {
            return collect();
        }

        // Tentukan bukti_dukung_id berdasarkan mode penilaian
        $buktiDukungId = $this->penilaianDiKriteria ? null : $this->bukti_dukung_id;

        // Query history
        $query = PenilaianHistory::where('kriteria_komponen_id', $this->kriteria_komponen_session)
            ->where('opd_id', $this->opd_session)
            ->with(['user', 'role', 'tingkatan_nilai']);

        // Filter berdasarkan mode penilaian
        if ($this->penilaianDiKriteria) {
            $query->whereNull('bukti_dukung_id');
        } else {
            if ($buktiDukungId) {
                $query->where('bukti_dukung_id', $buktiDukungId);
            } else {
                // Jika belum pilih bukti dukung, return empty
                return collect();
            }
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Cek apakah komponen memiliki penolakan dari verifikator/penjamin
     * (is_verified = 0 atau false) pada dirinya atau anaknya
     */
    public function hasRejection($item, $type)
    {
        $opdId = $this->opd_session;

        if (!$opdId) {
            return false;
        }

        // Cek berdasarkan tipe
        switch ($type) {
            case 'komponen':
                // Cek di semua kriteria komponen yang punya bukti dukung
                // Hanya dari role verifikator atau penjamin
                return \DB::table('penilaian')
                    ->join('bukti_dukung', 'penilaian.bukti_dukung_id', '=', 'bukti_dukung.id')
                    ->join('kriteria_komponen', 'bukti_dukung.kriteria_komponen_id', '=', 'kriteria_komponen.id')
                    ->join('sub_komponen', 'kriteria_komponen.sub_komponen_id', '=', 'sub_komponen.id')
                    ->join('role', 'penilaian.role_id', '=', 'role.id')
                    ->where('sub_komponen.komponen_id', $item->id)
                    ->where('penilaian.opd_id', $opdId)
                    ->where('penilaian.is_verified', false)
                    ->whereIn('role.jenis', ['verifikator', 'penjamin'])
                    ->exists();

            case 'sub_komponen':
                // Cek di semua kriteria komponen dari sub komponen ini
                // Hanya dari role verifikator atau penjamin
                return \DB::table('penilaian')
                    ->join('bukti_dukung', 'penilaian.bukti_dukung_id', '=', 'bukti_dukung.id')
                    ->join('kriteria_komponen', 'bukti_dukung.kriteria_komponen_id', '=', 'kriteria_komponen.id')
                    ->join('role', 'penilaian.role_id', '=', 'role.id')
                    ->where('kriteria_komponen.sub_komponen_id', $item->id)
                    ->where('penilaian.opd_id', $opdId)
                    ->where('penilaian.is_verified', false)
                    ->whereIn('role.jenis', ['verifikator', 'penjamin'])
                    ->exists();

            case 'kriteria':
                // Cek di penilaian level kriteria atau di bukti dukungnya
                // Hanya dari role verifikator atau penjamin

                // Level kriteria (penilaian_di = 'kriteria')
                $hasRejectionKriteria = \DB::table('penilaian')
                    ->join('role', 'penilaian.role_id', '=', 'role.id')
                    ->whereNull('penilaian.bukti_dukung_id')
                    ->where('penilaian.kriteria_komponen_id', $item->id)
                    ->where('penilaian.opd_id', $opdId)
                    ->where('penilaian.is_verified', false)
                    ->whereIn('role.jenis', ['verifikator', 'penjamin'])
                    ->exists();

                // Level bukti dukung (penilaian_di = 'bukti')
                $hasRejectionBukti = \DB::table('penilaian')
                    ->join('bukti_dukung', 'penilaian.bukti_dukung_id', '=', 'bukti_dukung.id')
                    ->join('role', 'penilaian.role_id', '=', 'role.id')
                    ->where('bukti_dukung.kriteria_komponen_id', $item->id)
                    ->where('penilaian.opd_id', $opdId)
                    ->where('penilaian.is_verified', false)
                    ->whereIn('role.jenis', ['verifikator', 'penjamin'])
                    ->exists();

                return $hasRejectionKriteria || $hasRejectionBukti;

            case 'bukti':
                // Cek langsung di penilaian bukti dukung ini
                // Hanya dari role verifikator atau penjamin
                return \DB::table('penilaian')
                    ->join('role', 'penilaian.role_id', '=', 'role.id')
                    ->where('penilaian.bukti_dukung_id', $item->id)
                    ->where('penilaian.opd_id', $opdId)
                    ->where('penilaian.is_verified', false)
                    ->whereIn('role.jenis', ['verifikator', 'penjamin'])
                    ->exists();

            default:
                return false;
        }
    }

    public function render()
    {
        return view('livewire.dashboard.lembar-kerja');
    }
}
