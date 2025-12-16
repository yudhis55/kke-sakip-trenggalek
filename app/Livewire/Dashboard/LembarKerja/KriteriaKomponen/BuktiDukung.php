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

    // Verifikasi properties
    public $is_verified = null;
    public $keterangan_verifikasi = '';

    // Penilaian properties
    public $tingkatan_nilai_id = null;
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

        // Simpan ke database sebagai JSON
        FileBuktiDukung::create([
            'bukti_dukung_id' => $this->bukti_dukung_id,
            'opd_id' => $this->opd_id,
            'link_file' => json_encode($uploadedFiles),
            'is_perubahan' => false,
        ]);

        // Reset the file input
        $this->file_bukti_dukung = [];
        $this->js('window.location.reload()');

        flash()->use('theme.ruby')->option('position', 'bottom-right')->success('Berhasil upload bukti dukung.');

        // flasher()->addSuccess('File berhasil diunggah!');
    }

    public function setBuktiDukungId($bukti_dukung_id)
    {
        $this->bukti_dukung_id = $bukti_dukung_id;
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

                PenilaianMandiri::create([
                    'tingkatan_nilai_id' => $this->tingkatan_nilai_id,
                    'kriteria_komponen_id' => $this->kriteria_komponen_id,
                    'opd_id' => $this->opd_id,
                    'is_perubahan' => false,
                ]);
            } elseif ($jenis == 'penjamin') {
                PenilaianPenjamin::create([
                    'tingkatan_nilai_id' => $this->tingkatan_nilai_id,
                    'kriteria_komponen_id' => $this->kriteria_komponen_id,
                ]);
            } elseif ($jenis == 'penilai') {
                PenilaianPenilai::create([
                    'tingkatan_nilai_id' => $this->tingkatan_nilai_id,
                    'kriteria_komponen_id' => $this->kriteria_komponen_id,
                ]);
            } else {
                flash()->use('theme.ruby')->option('position', 'bottom-right')->error('Role tidak memiliki akses untuk melakukan penilaian.');
                return;
            }

            // Reset form
            $this->tingkatan_nilai_id = null;

            flash()->use('theme.ruby')->option('position', 'bottom-right')->success('Penilaian berhasil disimpan.');
        } catch (\Exception $e) {
            flash()->use('theme.ruby')->option('position', 'bottom-right')->error('Gagal menyimpan penilaian: ' . $e->getMessage());
        }
    }
}
