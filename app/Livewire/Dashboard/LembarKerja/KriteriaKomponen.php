<?php

namespace App\Livewire\Dashboard\LembarKerja;

use Livewire\Component;
use Livewire\Attributes\Computed;
use App\Models\KriteriaKomponen as KriteriaKomponenModel;
use App\Models\SubKomponen;

class KriteriaKomponen extends Component
{
    public $sub_komponen_id;

    #[Computed]
    public function subKomponen()
    {
        return SubKomponen::find($this->sub_komponen_id);
    }

    #[Computed]
    public function kriteriaKomponenList()
    {
        $kriteriaList = KriteriaKomponenModel::where('sub_komponen_id', $this->sub_komponen_id)
            ->with(['bukti_dukung.file_bukti_dukung'])
            ->get();

        // Ambil bobot dari Sub Komponen (induk)
        $bobotSubKomponen = $this->subKomponen->bobot ?? 0;

        // Hitung bobot per kriteria komponen berdasarkan bobot sub komponen
        // Formula: bobot_sub_komponen / jumlah_kriteria_komponen
        $totalKriteria = $kriteriaList->count();
        $bobotPerKriteria = $totalKriteria > 0 ? $bobotSubKomponen / $totalKriteria : 0;

        // Tambahkan perhitungan untuk setiap kriteria
        $kriteriaList->each(function ($kriteria) use ($bobotPerKriteria) {
            // Hitung total file bukti dukung
            $totalFiles = 0;
            $uploadedFiles = 0;

            foreach ($kriteria->bukti_dukung as $buktiDukung) {
                $fileCount = $buktiDukung->file_bukti_dukung->count();
                $totalFiles += $fileCount;

                // File yang sudah diupload adalah yang memiliki file_path tidak null
                $uploadedFiles += $buktiDukung->file_bukti_dukung->whereNotNull('file_path')->count();
            }

            // Set attributes untuk digunakan di view
            $kriteria->bobot_persen = $bobotPerKriteria;
            $kriteria->total_file_bukti_dukung = $totalFiles;
            $kriteria->uploaded_file_bukti_dukung = $uploadedFiles;
            $kriteria->persentase_kelengkapan = $totalFiles > 0 ? ($uploadedFiles / $totalFiles) * 100 : 0;
        });

        return $kriteriaList;
    }
}
