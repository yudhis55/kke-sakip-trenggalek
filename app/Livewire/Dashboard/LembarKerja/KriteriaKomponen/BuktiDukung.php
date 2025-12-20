<?php

namespace App\Livewire\Dashboard\LembarKerja\KriteriaKomponen;

use Livewire\Attributes\Computed;
use Livewire\Component;
use App\Models\BuktiDukung as BuktiDukungModel;
use App\Models\FileBuktiDukung;
use App\Models\KriteriaKomponen;
use App\Models\Opd;
use App\Models\Penilaian;
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
    // public $current_tab = 'bukti_dukung';

    public function mount()
    {
        // Set initial opd_id berdasarkan user yang login
        if (Auth::user()->opd_id) {
            $this->opd_id = Auth::user()->opd_id;
        }
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

        if ($this->penilaianDiKriteria) {
            // Mode Kriteria: SEMUA bukti dukung harus sudah punya file
            $allBuktiDukung = \App\Models\BuktiDukung::where('kriteria_komponen_id', $this->kriteria_komponen_id)->get();

            if ($allBuktiDukung->isEmpty()) {
                return [
                    'allowed' => false,
                    'message' => 'Belum ada bukti dukung yang terdaftar untuk kriteria ini.'
                ];
            }

            foreach ($allBuktiDukung as $buktiDukung) {
                $hasFile = FileBuktiDukung::where('bukti_dukung_id', $buktiDukung->id)
                    ->where('opd_id', $this->opd_id)
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

            $hasFile = FileBuktiDukung::where('bukti_dukung_id', $this->bukti_dukung_id)
                ->where('opd_id', $this->opd_id)
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

        return \App\Models\BuktiDukung::where('kriteria_komponen_id', $this->kriteria_komponen_id)
            ->with(['file_bukti_dukung' => function ($query) {
                $query->where('opd_id', $this->opd_id)
                    ->orderBy('created_at', 'desc');
            }])
            ->orderBy('nama')
            ->get();
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

    #[Computed]
    public function buktiDukungList()
    {
        if (Auth::user()->opd_id) {
            $this->opd_id = Auth::user()->opd_id;
        }
        return BuktiDukungModel::with([
            'file_bukti_dukung' => function ($query) {
                $query->where('opd_id', $this->opd_id);
            }
        ])
            ->where('kriteria_komponen_id', $this->kriteria_komponen_id)
            ->get();
    }

    #[Computed]
    public function selectedBuktiDukung()
    {
        if (!$this->bukti_dukung_id) {
            return null;
        }

        return BuktiDukungModel::find($this->bukti_dukung_id);
    }

    #[Computed]
    public function selectedFileBuktiDukungId()
    {
        if (!$this->bukti_dukung_id || !$this->opd_id) {
            return null;
        }

        $fileBuktiDukung = FileBuktiDukung::where('bukti_dukung_id', $this->bukti_dukung_id)
            ->where('opd_id', $this->opd_id)
            ->first();

        return $fileBuktiDukung?->id;
    }

    #[Computed]
    public function selectedFileBuktiDukung()
    {
        if (!$this->bukti_dukung_id || !$this->opd_id) {
            return null;
        }

        $fileBuktiDukung = FileBuktiDukung::where('bukti_dukung_id', $this->bukti_dukung_id)
            ->where('opd_id', $this->opd_id)
            ->first();

        if (!$fileBuktiDukung) {
            return null;
        }

        // Decode JSON link_file
        return json_decode($fileBuktiDukung->link_file, true);
    }

    #[Computed]
    public function riwayatVerifikasi()
    {
        if (!$this->selectedFileBuktiDukungId) {
            return collect([]);
        }

        // Query unified penilaian table untuk riwayat verifikasi
        // Filter: role verifikator/penjamin (yang punya is_verified)
        return Penilaian::with(['role'])
            ->where('file_bukti_dukung_id', $this->selectedFileBuktiDukungId)
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

        $this->validate([
            'file_bukti_dukung' => 'required|array',
            'file_bukti_dukung.*' => 'file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240', // Max 10MB per file
            'bukti_dukung_id' => 'required|exists:bukti_dukung,id',
            'opd_id' => 'required|exists:opd,id',
            'keterangan_upload' => 'nullable|string|max:1000',
        ]);

        $uploadedFiles = [];

        foreach ($this->file_bukti_dukung as $file) {
            // Simpan file ke storage/app/public/bukti_dukung dengan nama random
            $path = $file->store('bukti_dukung', 'public');

            $uploadedFiles[] = [
                'path' => $path, // Sudah random filename dari Laravel
                'original_name' => $file->getClientOriginalName(), // Nama asli untuk display
            ];
        }

        // Cek apakah sudah ada file sebelumnya
        $existingFile = FileBuktiDukung::where('bukti_dukung_id', $this->bukti_dukung_id)
            ->where('opd_id', $this->opd_id)
            ->first();

        if ($existingFile) {
            if ($this->ganti_semua_dokumen) {
                // Mode REPLACE: Hapus file lama dari storage
                $oldFiles = json_decode($existingFile->link_file, true);
                if ($oldFiles) {
                    foreach ($oldFiles as $oldFile) {
                        if (isset($oldFile['path'])) {
                            Storage::disk('public')->delete($oldFile['path']);
                        }
                    }
                }

                // Update dengan file baru saja (replace)
                $existingFile->update([
                    'link_file' => json_encode($uploadedFiles),
                    'keterangan' => $this->keterangan_upload,
                    'is_perubahan' => $this->is_perubahan,
                ]);
            } else {
                // Mode APPEND: Gabungkan file lama + file baru
                $oldFiles = json_decode($existingFile->link_file, true) ?? [];
                $mergedFiles = array_merge($oldFiles, $uploadedFiles);

                // Update dengan gabungan file lama + baru
                $existingFile->update([
                    'link_file' => json_encode($mergedFiles),
                    'keterangan' => $this->keterangan_upload,
                    'is_perubahan' => $this->is_perubahan,
                ]);
            }
        } else {
            // Simpan ke database sebagai JSON (record baru)
            FileBuktiDukung::create([
                'bukti_dukung_id' => $this->bukti_dukung_id,
                'opd_id' => $this->opd_id,
                'link_file' => json_encode($uploadedFiles),
                'keterangan' => $this->keterangan_upload,
                'is_perubahan' => $this->is_perubahan,
            ]);
        }

        // Reset the file input
        $this->file_bukti_dukung = [];
        $this->keterangan_upload = '';
        $this->is_perubahan = false;
        $this->ganti_semua_dokumen = false;
        $this->js('window.location.reload()');
        $message = $this->ganti_semua_dokumen
            ? 'Berhasil mengganti semua dokumen.'
            : 'Berhasil menambahkan dokumen.';
        flash()->use('theme.ruby')->option('position', 'bottom-right')->success($message);
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

        if (!$this->selectedFileBuktiDukungId) {
            flash()->use('theme.ruby')->option('position', 'bottom-right')->error('File bukti dukung tidak ditemukan.');
            return;
        }

        $fileBuktiDukung = FileBuktiDukung::find($this->selectedFileBuktiDukungId);

        if ($fileBuktiDukung) {
            // Hapus file dari storage
            $files = json_decode($fileBuktiDukung->link_file, true);
            if ($files) {
                foreach ($files as $file) {
                    if (isset($file['path'])) {
                        Storage::disk('public')->delete($file['path']);
                    }
                }
            }

            // Hapus record dari database
            $fileBuktiDukung->delete();

            flash()->use('theme.ruby')->option('position', 'bottom-right')->success('File berhasil dihapus.');
            $this->js('window.location.reload()');
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

        if (!$this->selectedFileBuktiDukungId) {
            flash()->use('theme.ruby')->option('position', 'bottom-right')->error('File bukti dukung tidak ditemukan.');
            return;
        }

        // Simpan ke unified penilaian table
        Penilaian::create([
            'file_bukti_dukung_id' => $this->selectedFileBuktiDukungId,
            'bukti_dukung_id' => $this->bukti_dukung_id,
            'kriteria_komponen_id' => $this->kriteria_komponen_id,
            'opd_id' => $this->opd_id,
            'role_id' => Auth::user()->role_id,
            'is_verified' => $this->is_verified,
            'keterangan' => $this->keterangan_verifikasi,
            'tingkatan_nilai_id' => null, // Verifikasi tidak pakai tingkatan_nilai_id
        ]);

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
}
