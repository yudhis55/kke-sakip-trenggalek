<?php

namespace App\Livewire\Dashboard;

use Livewire\Attributes\Computed;
use Livewire\Component;
use App\Models\Komponen;
use App\Models\SubKomponen;
use App\Models\Tahun;

class LembarKerja extends Component
{
    public $tahun_id;

    public function mount()
    {
        $this->tahun_id = session('tahun_session') ?? Tahun::where('is_active', true)->first()->id;
    }

    #[Computed]
    public function komponenOptions()
    {
        return Komponen::where('tahun_id', $this->tahun_id)->get();
    }

    #[Computed]
    public function subKomponenOptions()
    {
        // with komponen relation
        return SubKomponen::where('tahun_id', $this->tahun_id)->with('komponen')->get();
    }
}
