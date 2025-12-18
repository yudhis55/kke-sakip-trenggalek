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
use App\Models\Role;

class Mapping extends Component
{
    public $kd_komponen, $nama_komponen, $bobot_komponen, $role_id;
    public $kd_sub_komponen, $nama_sub_komponen, $bobot_sub_komponen, $komponen_id;
    public $kd_kriteria, $nama_kriteria, $sub_komponen_id, $jenis_nilai_id;
    public $kd_bukti, $nama_bukti, $bobot_bukti, $kriteria_komponen_id, $kriteria_penilaian;
    public $tahun_id;

    // Edit mode properties
    public $editKomponenId, $editSubKomponenId, $editKriteriaKomponenId, $editBuktiDukungId;
    public $isEditMode = false;

    public function mount()
    {
        $this->tahun_id = session('tahun_session') ?? Tahun::where('is_active', true)->first()->id;
    }


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
    public function roleoptions()
    {
        return Role::all();
    }

    #[Computed]
    public function fullMapping()
    {
        $komponens = Komponen::with([
            'sub_komponen.kriteria_komponen.bukti_dukung',
            'role'
        ])->where('tahun_id', $this->tahun_id)->get();

        // Calculate bobot for kriteria_komponen and bukti_dukung
        foreach ($komponens as $komponen) {
            foreach ($komponen->sub_komponen as $subKomponen) {
                // Hitung jumlah kriteria komponen untuk sub komponen ini
                $jumlahKriteria = $subKomponen->kriteria_komponen->count();

                foreach ($subKomponen->kriteria_komponen as $kriteriaKomponen) {
                    // Bobot kriteria komponen = bobot sub komponen / jumlah kriteria komponen
                    $kriteriaKomponen->bobot = $jumlahKriteria > 0
                        ? round($subKomponen->bobot / $jumlahKriteria, 2)
                        : 0;

                    // Hitung jumlah bukti dukung untuk kriteria komponen ini
                    $jumlahBukti = $kriteriaKomponen->bukti_dukung->count();

                    foreach ($kriteriaKomponen->bukti_dukung as $buktiDukung) {
                        // Bobot bukti dukung = bobot kriteria komponen / jumlah bukti dukung
                        $buktiDukung->bobot = $jumlahBukti > 0
                            ? round($kriteriaKomponen->bobot / $jumlahBukti, 2)
                            : 0;
                    }
                }
            }
        }

        return $komponens;
    }

    public function addKomponen()
    {
        $this->validate([
            'kd_komponen' => 'required|unique:komponen,kode',
            'nama_komponen' => 'required',
            'bobot_komponen' => 'required|numeric|min:0',
            'role_id' => 'nullable|exists:role,id',
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
            'role_id' => $this->role_id,
        ]);

        // Reset form fields
        $this->kd_komponen = '';
        $this->nama_komponen = '';
        $this->bobot_komponen = '';
        $this->role_id = null;

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
            'kriteria_penilaian' => 'nullable|string',
        ]);

        // Create Bukti Dukung
        BuktiDukung::create([
            'nama' => $this->nama_bukti,
            'kriteria_penilaian' => $this->kriteria_penilaian,
            'kriteria_komponen_id' => $this->kriteria_komponen_id,
            'sub_komponen_id' => KriteriaKomponen::find($this->kriteria_komponen_id)->sub_komponen_id,
            'komponen_id' => KriteriaKomponen::find($this->kriteria_komponen_id)->komponen_id,
            'tahun_id' => $this->tahun_id,
        ]);

        // Reset form fields
        $this->kd_bukti = '';
        $this->nama_bukti = '';
        $this->bobot_bukti = '';
        $this->kriteria_penilaian = '';
        $this->kriteria_komponen_id = '';

        unset($this->fullMapping);
    }

    // Edit functions
    public function editKomponen($id)
    {
        $komponen = Komponen::find($id);
        if ($komponen) {
            $this->editKomponenId = $id;
            $this->kd_komponen = $komponen->kode;
            $this->nama_komponen = $komponen->nama;
            $this->bobot_komponen = $komponen->bobot;
            $this->role_id = $komponen->role_id;
            $this->isEditMode = true;
        }
    }

    public function updateKomponen()
    {
        $this->validate([
            'kd_komponen' => 'required',
            'nama_komponen' => 'required',
            'bobot_komponen' => 'required|numeric|min:0',
            'role_id' => 'nullable|exists:role,id',
        ]);

        $komponen = Komponen::find($this->editKomponenId);
        if ($komponen) {
            // Calculate total bobot excluding current komponen
            $totalBobotExisting = Komponen::where('tahun_id', $this->tahun_id)
                ->where('id', '!=', $this->editKomponenId)
                ->sum('bobot');

            if (($totalBobotExisting + $this->bobot_komponen) > 100) {
                $sisaBobot = 100 - $totalBobotExisting;
                $this->addError('bobot_komponen', "Total bobot komponen tidak boleh melebihi 100. Sisa bobot yang tersedia: {$sisaBobot}");
                return;
            }

            $komponen->update([
                'kode' => $this->kd_komponen,
                'nama' => $this->nama_komponen,
                'bobot' => $this->bobot_komponen,
                'role_id' => $this->role_id,
            ]);

            $this->resetFormKomponen();
        }
    }

    public function editSubKomponen($id)
    {
        $subKomponen = SubKomponen::find($id);
        if ($subKomponen) {
            $this->editSubKomponenId = $id;
            $this->kd_sub_komponen = $subKomponen->kode;
            $this->nama_sub_komponen = $subKomponen->nama;
            $this->bobot_sub_komponen = $subKomponen->bobot;
            $this->komponen_id = $subKomponen->komponen_id;
            $this->isEditMode = true;
        }
    }

    public function updateSubKomponen()
    {
        $this->validate([
            'kd_sub_komponen' => 'required',
            'nama_sub_komponen' => 'required',
            'bobot_sub_komponen' => 'required|numeric|min:0',
        ]);

        $subKomponen = SubKomponen::find($this->editSubKomponenId);
        if ($subKomponen) {
            $komponen = Komponen::find($subKomponen->komponen_id);

            // Calculate total bobot excluding current sub komponen
            $totalBobotExisting = SubKomponen::where('komponen_id', $subKomponen->komponen_id)
                ->where('id', '!=', $this->editSubKomponenId)
                ->sum('bobot');

            if (($totalBobotExisting + $this->bobot_sub_komponen) > $komponen->bobot) {
                $sisaBobot = $komponen->bobot - $totalBobotExisting;
                $this->addError('bobot_sub_komponen', "Total bobot sub komponen tidak boleh melebihi bobot komponen induk ({$komponen->bobot}). Sisa bobot yang tersedia: {$sisaBobot}");
                return;
            }

            $subKomponen->update([
                'kode' => $this->kd_sub_komponen,
                'nama' => $this->nama_sub_komponen,
                'bobot' => $this->bobot_sub_komponen,
            ]);

            $this->resetFormSubKomponen();
        }
    }

    public function editKriteriaKomponen($id)
    {
        $kriteria = KriteriaKomponen::find($id);
        if ($kriteria) {
            $this->editKriteriaKomponenId = $id;
            $this->kd_kriteria = $kriteria->kode;
            $this->nama_kriteria = $kriteria->nama;
            $this->jenis_nilai_id = $kriteria->jenis_nilai_id;
            $this->sub_komponen_id = $kriteria->sub_komponen_id;
            $this->isEditMode = true;
        }
    }

    public function updateKriteriaKomponen()
    {
        $this->validate([
            'kd_kriteria' => 'required',
            'nama_kriteria' => 'required',
            'jenis_nilai_id' => 'required|exists:jenis_nilai,id',
        ]);

        $kriteria = KriteriaKomponen::find($this->editKriteriaKomponenId);
        if ($kriteria) {
            $kriteria->update([
                'kode' => $this->kd_kriteria,
                'nama' => $this->nama_kriteria,
                'jenis_nilai_id' => $this->jenis_nilai_id,
            ]);

            $this->resetFormKriteriaKomponen();
        }
    }

    public function editBuktiDukung($id)
    {
        $bukti = BuktiDukung::find($id);
        if ($bukti) {
            $this->editBuktiDukungId = $id;
            $this->kd_bukti = $bukti->kode;
            $this->nama_bukti = $bukti->nama;
            $this->kriteria_penilaian = $bukti->kriteria_penilaian;
            $this->kriteria_komponen_id = $bukti->kriteria_komponen_id;
            $this->isEditMode = true;
        }
    }

    public function updateBuktiDukung()
    {
        $this->validate([
            'nama_bukti' => 'required',
            'kriteria_penilaian' => 'nullable|string',
        ]);

        $bukti = BuktiDukung::find($this->editBuktiDukungId);
        if ($bukti) {
            $bukti->update([
                'nama' => $this->nama_bukti,
                'kriteria_penilaian' => $this->kriteria_penilaian,
            ]);

            $this->resetFormBuktiDukung();
        }
    }

    // Reset functions
    public function resetFormKomponen()
    {
        $this->kd_komponen = '';
        $this->nama_komponen = '';
        $this->bobot_komponen = '';
        $this->role_id = null;
        $this->editKomponenId = null;
        $this->isEditMode = false;
        unset($this->fullMapping);
    }

    public function resetFormSubKomponen()
    {
        $this->kd_sub_komponen = '';
        $this->nama_sub_komponen = '';
        $this->bobot_sub_komponen = '';
        $this->komponen_id = '';
        $this->editSubKomponenId = null;
        $this->isEditMode = false;
        unset($this->fullMapping);
    }

    public function resetFormKriteriaKomponen()
    {
        $this->kd_kriteria = '';
        $this->nama_kriteria = '';
        $this->jenis_nilai_id = '';
        $this->sub_komponen_id = '';
        $this->editKriteriaKomponenId = null;
        $this->isEditMode = false;
        unset($this->fullMapping);
    }

    public function resetFormBuktiDukung()
    {
        $this->kd_bukti = '';
        $this->nama_bukti = '';
        $this->kriteria_penilaian = '';
        $this->kriteria_komponen_id = '';
        $this->editBuktiDukungId = null;
        $this->isEditMode = false;
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
