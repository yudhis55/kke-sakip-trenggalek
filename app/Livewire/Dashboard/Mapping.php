<?php

namespace App\Livewire\Dashboard;

use App\Models\Komponen;
use App\Models\Tahun;
use Livewire\Component;
use Livewire\Attributes\Computed;
use App\Models\SubKomponen;
use App\Models\JenisNilai;
use App\Models\KriteriaKomponen;
use App\Models\BuktiDukung;

class Mapping extends Component
{
    public $max_nilai = 100;
    public $kd_komponen, $nama_komponen, $bobot_komponen;
    public $kd_sub_komponen, $nama_sub_komponen, $bobot_sub_komponen, $komponen_id;
    public $kd_kriteria, $nama_kriteria, $sub_komponen_id, $jenis_nilai_id;
    public $kd_bukti, $nama_bukti, $bobot_bukti, $kriteria_komponen_id;
    public $tahun_id = 1;


    #[Computed]
    public function tahunoptions()
    {
        return Tahun::all();
    }

    #[Computed]
    public function jenisnilaioptions()
    {
        return JenisNilai::all();
    }

    #[Computed]
    public function fullMapping()
    {
        return Komponen::with('sub_komponen', 'kriteria_komponen', 'bukti_dukung')->where('tahun_id', '1')->get();
    }

    public function addKomponen()
    {
        $this->validate([
            'kd_komponen' => 'required|unique:komponen,kode',
            'nama_komponen' => 'required',
            'bobot_komponen' => 'required|numeric|min:0',
        ]);

        // Calculate total existing bobot for the same tahun_id
        $totalBobotExisting = Komponen::where('tahun_id', $this->tahun_id)->sum('bobot');

        // Check if adding new bobot would exceed 100
        if (($totalBobotExisting + $this->bobot_komponen) > 100) {
            $sisaBobot = 100 - $totalBobotExisting;
            $this->addError('bobot_komponen', "Total bobot komponen tidak boleh melebihi 100. Sisa bobot yang tersedia: {$sisaBobot}");
            return;
        }

        Komponen::create([
            'kode' => $this->kd_komponen,
            'nama' => $this->nama_komponen,
            'bobot' => $this->bobot_komponen,
            'tahun_id' => $this->tahun_id,
        ]);

        // Reset form fields
        $this->kd_komponen = '';
        $this->nama_komponen = '';
        $this->bobot_komponen = '';

        unset($this->fullMapping);
    }

    public function addSubKomponen()
    {
        $this->validate([
            'kd_sub_komponen' => 'required|unique:sub_komponen,kode',
            'nama_sub_komponen' => 'required',
            'bobot_sub_komponen' => 'required|numeric|min:0',
            'komponen_id' => 'required|exists:komponen,id',
        ]);

        // Get komponen induk
        $komponen = Komponen::find($this->komponen_id);

        // Calculate total existing bobot for the same komponen_id
        $totalBobotExisting = SubKomponen::where('komponen_id', $this->komponen_id)->sum('bobot');

        // Check if adding new bobot would exceed komponen bobot
        if (($totalBobotExisting + $this->bobot_sub_komponen) > $komponen->bobot) {
            $sisaBobot = $komponen->bobot - $totalBobotExisting;
            $this->addError('bobot_sub_komponen', "Total bobot sub komponen tidak boleh melebihi bobot komponen induk ({$komponen->bobot}). Sisa bobot yang tersedia: {$sisaBobot}");
            return;
        }

        SubKomponen::create([
            'kode' => $this->kd_sub_komponen,
            'nama' => $this->nama_sub_komponen,
            'bobot' => $this->bobot_sub_komponen,
            'komponen_id' => $this->komponen_id,
            'tahun_id' => $this->tahun_id,
        ]);

        // Reset form fields
        $this->kd_sub_komponen = '';
        $this->nama_sub_komponen = '';
        $this->bobot_sub_komponen = '';
        $this->komponen_id = '';

        unset($this->fullMapping);
    }

    public function addKriteriaKomponen()
    {
        $this->validate([
            'kd_kriteria' => 'required|unique:kriteria_komponen,kode',
            'nama_kriteria' => 'required',
            'sub_komponen_id' => 'required|exists:sub_komponen,id',
            'jenis_nilai_id' => 'required|exists:jenis_nilai,id',
        ]);

        // Create Kriteria Komponen
        KriteriaKomponen::create([
            'kode' => $this->kd_kriteria,
            'nama' => $this->nama_kriteria,
            'sub_komponen_id' => $this->sub_komponen_id,
            'komponen_id' => SubKomponen::find($this->sub_komponen_id)->komponen_id,
            'jenis_nilai_id' => $this->jenis_nilai_id,
            'tahun_id' => $this->tahun_id,
        ]);

        // Reset form fields
        $this->kd_kriteria = '';
        $this->nama_kriteria = '';
        $this->sub_komponen_id = '';
        $this->jenis_nilai_id = '';

        unset($this->fullMapping);
    }

    public function addBuktiDukung()
    {
        $this->validate([
            'nama_bukti' => 'required',
        ]);

        // Create Bukti Dukung
        BuktiDukung::create([
            'nama' => $this->nama_bukti,
            'kriteria_komponen_id' => $this->kriteria_komponen_id,
            'sub_komponen_id' => KriteriaKomponen::find($this->kriteria_komponen_id)->sub_komponen_id,
            'komponen_id' => KriteriaKomponen::find($this->kriteria_komponen_id)->komponen_id,
            'tahun_id' => $this->tahun_id,
        ]);

        // Reset form fields
        $this->kd_bukti = '';
        $this->nama_bukti = '';
        $this->bobot_bukti = '';
        $this->kriteria_komponen_id = '';

        unset($this->fullMapping);
    }

    public function deleteBuktiDukung($id)
    {
        $bukti = BuktiDukung::find($id);
        if ($bukti) {
            $bukti->delete();
            unset($this->fullMapping);
        }
    }

    public function deleteKriteriaKomponen($id)
    {
        $kriteria = KriteriaKomponen::find($id);
        if ($kriteria) {
            $kriteria->delete();
            unset($this->fullMapping);
        }
    }

    public function deleteSubKomponen($id)
    {
        $subKomponen = SubKomponen::find($id);
        if ($subKomponen) {
            $subKomponen->delete();
            unset($this->fullMapping);
        }
    }

    public function deleteKomponen($id)
    {
        $komponen = Komponen::find($id);
        if ($komponen) {
            $komponen->delete();
            unset($this->fullMapping);
        }
    }
}
