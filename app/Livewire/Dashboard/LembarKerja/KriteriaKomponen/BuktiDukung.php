<?php

namespace App\Livewire\Dashboard\LembarKerja\KriteriaKomponen;

use Livewire\Attributes\Computed;
use Livewire\Component;
use App\Models\BuktiDukung as BuktiDukungModel;
use App\Models\KriteriaKomponen;
use App\Models\Opd;
use Spatie\LivewireFilepond\WithFilePond;
use Illuminate\Support\Facades\Auth;


class BuktiDukung extends Component
{
    use WithFilePond;
    public $file;
    public $sub_komponen_id;
    public $kriteria_komponen_id;
    public $opd_selected_id;
    public $bukti_dukung_id;

    public function mount()
    {
        Auth::user()->opd_id ? $this->opd_selected_id = Auth::user()->opd_id : $this->opd_selected_id = null;
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
        return BuktiDukungModel::with('file_bukti_dukung')->where('kriteria_komponen_id', $this->kriteria_komponen_id)->get();
    }

    public function setBuktiDukungId($bukti_dukung_id)
    {
        $this->bukti_dukung_id = $bukti_dukung_id;
    }

    // public function 
}
