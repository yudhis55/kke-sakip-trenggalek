<?php

namespace App\Livewire\Dashboard;

use App\Models\Tahun;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Session;
use Livewire\Component;

class TahunDropdown extends Component
{
    #[Session(key: 'tahun_session')]
    public $tahun_session;

    public function mount()
    {
        if (!$this->tahun_session) {
            $activeTahun = Tahun::where('is_active', true)->first();
            $nowTahun = date('Y');
            $this->tahun_session = $activeTahun ? $activeTahun->id : Tahun::where('tahun', $nowTahun)->first()->id;
        }
    }

    #[Computed]
    public function tahunOptions()
    {
        return Tahun::all();
    }

    public function updatedTahunSession()
    {
        $this->js('window.location.reload()');
    }

    public function render()
    {
        return <<<'HTML'
        <div class="my-auto">
            <select wire:model.live="tahun_session" class="form-select" aria-label="Pilih Tahun">
                @foreach($this->tahunOptions as $tahun)
                    <option value="{{ $tahun->id }}">
                        {{ $tahun->tahun }} {{ $tahun->is_active ? '(Aktif)' : '' }}
                    </option>
                @endforeach
            </select>
        </div>
        HTML;
    }
}
