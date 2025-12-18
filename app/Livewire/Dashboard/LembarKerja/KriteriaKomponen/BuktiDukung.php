<?php

namespace App\Livewire\Dashboard\LembarKerja\KriteriaKomponen;

use Livewire\Attributes\Computed;
use Livewire\Component;
use App\Models\BuktiDukung as BuktiDukungModel;
use App\Models\FileBuktiDukung;
use App\Models\KriteriaKomponen;
use App\Models\Opd;
use App\Models\PenilaianVerifikator;
use App\Models\PenilaianMandiri;
use App\Models\PenilaianPenjamin;
use App\Models\PenilaianPenilai;
use App\Models\TingkatanNilai;
use Spatie\LivewireFilepond\WithFilePond;
use Illuminate\Support\Facades\Auth;
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

    #[Computed]
    public function penilaianTersimpan()
    {
        if (!$this->kriteria_komponen_id) {
            return null;
        }

        $jenis = Auth::user()->role->jenis;

        if ($jenis == 'opd' && $this->opd_id) {
            return PenilaianMandiri::where('kriteria_komponen_id', $this->kriteria_komponen_id)
                ->where('opd_id', $this->opd_id)
                ->latest()
                ->first();
        } elseif ($jenis == 'penjamin') {
            return PenilaianPenjamin::where('kriteria_komponen_id', $this->kriteria_komponen_id)
                ->latest()
                ->first();
        } elseif ($jenis == 'penilai') {
            return PenilaianPenilai::where('kriteria_komponen_id', $this->kriteria_komponen_id)
                ->latest()
                ->first();
        }

        return null;
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

        return PenilaianVerifikator::with(['role'])
            ->where('file_bukti_dukung_id', $this->selectedFileBuktiDukungId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function uploadBuktiDukung()
    {
        $this->validate([
            'file_bukti_dukung' => 'required|array',
            'file_bukti_dukung.*' => 'file|max:10240', // Max 10MB per file
            'bukti_dukung_id' => 'required|exists:bukti_dukung,id',
            'opd_id' => 'required|exists:opd,id',
            'keterangan_upload' => 'nullable|string|max:1000',
        ]);

        $uploadedFiles = [];

        foreach ($this->file_bukti_dukung as $file) {
            // Simpan file ke storage/app/public/bukti_dukung
            $path = $file->store('bukti_dukung', 'public');

            // Generate full URL
            $url = asset('storage/' . $path);

            $uploadedFiles[] = [
                'path' => $path,
                'url' => $url,
                'original_name' => $file->getClientOriginalName(),
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
                            \Storage::disk('public')->delete($oldFile['path']);
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
                        \Storage::disk('public')->delete($file['path']);
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
        $this->validate([
            'is_verified' => 'required|boolean',
            'keterangan_verifikasi' => 'nullable|string|max:1000',
        ]);

        if (!$this->selectedFileBuktiDukungId) {
            flash()->use('theme.ruby')->option('position', 'bottom-right')->error('File bukti dukung tidak ditemukan.');
            return;
        }

        PenilaianVerifikator::create([
            'file_bukti_dukung_id' => $this->selectedFileBuktiDukungId,
            'role_id' => Auth::user()->role_id,
            'is_verified' => $this->is_verified,
            'keterangan' => $this->keterangan_verifikasi,
            'is_perubahan' => false,
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
        $this->validate([
            'tingkatan_nilai_id' => 'required|exists:tingkatan_nilai,id',
        ]);

        if (!$this->kriteria_komponen_id) {
            flash()->use('theme.ruby')->option('position', 'bottom-right')->error('Kriteria komponen tidak ditemukan.');
            return;
        }

        $jenis = Auth::user()->role->jenis;

        try {
            if ($jenis == 'opd') {
                // Untuk OPD, harus ada opd_id
                if (!$this->opd_id) {
                    flash()->use('theme.ruby')->option('position', 'bottom-right')->error('OPD tidak ditemukan.');
                    return;
                }

                PenilaianMandiri::updateOrCreate(
                    [
                        'kriteria_komponen_id' => $this->kriteria_komponen_id,
                        'opd_id' => $this->opd_id,
                    ],
                    [
                        'tingkatan_nilai_id' => $this->tingkatan_nilai_id,
                        'is_perubahan' => false,
                    ]
                );
            } elseif ($jenis == 'penjamin') {
                PenilaianPenjamin::updateOrCreate(
                    [
                        'kriteria_komponen_id' => $this->kriteria_komponen_id,
                    ],
                    [
                        'tingkatan_nilai_id' => $this->tingkatan_nilai_id,
                    ]
                );
            } elseif ($jenis == 'penilai') {
                PenilaianPenilai::updateOrCreate(
                    [
                        'kriteria_komponen_id' => $this->kriteria_komponen_id,
                    ],
                    [
                        'tingkatan_nilai_id' => $this->tingkatan_nilai_id,
                    ]
                );
            } else {
                flash()->use('theme.ruby')->option('position', 'bottom-right')->error('Role tidak memiliki akses untuk melakukan penilaian.');
                return;
            }

            flash()->use('theme.ruby')->option('position', 'bottom-right')->success('Penilaian berhasil disimpan.');

            // Reset mode edit
            $this->is_editing_penilaian = false;
            $this->tingkatan_nilai_id = null;
        } catch (\Exception $e) {
            flash()->use('theme.ruby')->option('position', 'bottom-right')->error('Gagal menyimpan penilaian: ' . $e->getMessage());
        }
    }
}
