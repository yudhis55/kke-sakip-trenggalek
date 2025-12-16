<?php

namespace App\Livewire\Dashboard;

use App\Models\Setting;
use App\Models\Tahun;
use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Component;
use App\Models\Role;

class Pengaturan extends Component
{
    public $buka_penilaian_mandiri, $tutup_penilaian_mandiri, $buka_penilaian_verifikator, $tutup_penilaian_verifikator, $buka_penilaian_penjamin, $tutup_penilaian_penjamin, $buka_penilaian_penilai, $tutup_penilaian_penilai;
    public $tahun_id;

    public function mount()
    {
        // Ambil tahun_id dari session atau default ke tahun aktif
        $this->tahun_id = session('tahun_session') ?? Tahun::where('is_active', true)->first()?->id ?? 1;

        // Load setting berdasarkan tahun_id
        $this->loadSetting();
    }

    public function loadSetting()
    {
        $setting = Setting::where('tahun_id', $this->tahun_id)->first();

        if ($setting) {
            // Format tanggal ke Y-m-d untuk input type="date"
            $this->buka_penilaian_mandiri = $setting->buka_penilaian_mandiri ? date('Y-m-d', strtotime($setting->buka_penilaian_mandiri)) : null;
            $this->tutup_penilaian_mandiri = $setting->tutup_penilaian_mandiri ? date('Y-m-d', strtotime($setting->tutup_penilaian_mandiri)) : null;
            $this->buka_penilaian_verifikator = $setting->buka_penilaian_verifikator ? date('Y-m-d', strtotime($setting->buka_penilaian_verifikator)) : null;
            $this->tutup_penilaian_verifikator = $setting->tutup_penilaian_verifikator ? date('Y-m-d', strtotime($setting->tutup_penilaian_verifikator)) : null;
            $this->buka_penilaian_penjamin = $setting->buka_penilaian_penjamin ? date('Y-m-d', strtotime($setting->buka_penilaian_penjamin)) : null;
            $this->tutup_penilaian_penjamin = $setting->tutup_penilaian_penjamin ? date('Y-m-d', strtotime($setting->tutup_penilaian_penjamin)) : null;
            $this->buka_penilaian_penilai = $setting->buka_penilaian_penilai ? date('Y-m-d', strtotime($setting->buka_penilaian_penilai)) : null;
            $this->tutup_penilaian_penilai = $setting->tutup_penilaian_penilai ? date('Y-m-d', strtotime($setting->tutup_penilaian_penilai)) : null;
        } else {
            // Reset jika tidak ada setting untuk tahun ini
            $this->buka_penilaian_mandiri = null;
            $this->tutup_penilaian_mandiri = null;
            $this->buka_penilaian_verifikator = null;
            $this->tutup_penilaian_verifikator = null;
            $this->buka_penilaian_penjamin = null;
            $this->tutup_penilaian_penjamin = null;
            $this->buka_penilaian_penilai = null;
            $this->tutup_penilaian_penilai = null;
        }
    }

    public function saveSetting()
    {
        $this->validate([
            'buka_penilaian_mandiri' => 'required|date',
            'tutup_penilaian_mandiri' => 'required|date|after_or_equal:buka_penilaian_mandiri',
            'buka_penilaian_verifikator' => 'required|date',
            'tutup_penilaian_verifikator' => 'required|date|after_or_equal:buka_penilaian_verifikator',
            'buka_penilaian_penjamin' => 'required|date',
            'tutup_penilaian_penjamin' => 'required|date|after_or_equal:buka_penilaian_penjamin',
            'buka_penilaian_penilai' => 'required|date',
            'tutup_penilaian_penilai' => 'required|date|after_or_equal:buka_penilaian_penilai',
        ], [
            'buka_penilaian_mandiri.required' => 'Tanggal buka OPD harus diisi',
            'tutup_penilaian_mandiri.required' => 'Tanggal tutup OPD harus diisi',
            'tutup_penilaian_mandiri.after_or_equal' => 'Tanggal tutup OPD harus setelah atau sama dengan tanggal buka',
            'buka_penilaian_verifikator.required' => 'Tanggal buka Verifikator harus diisi',
            'tutup_penilaian_verifikator.required' => 'Tanggal tutup Verifikator harus diisi',
            'tutup_penilaian_verifikator.after_or_equal' => 'Tanggal tutup Verifikator harus setelah atau sama dengan tanggal buka',
            'buka_penilaian_penjamin.required' => 'Tanggal buka Penjamin harus diisi',
            'tutup_penilaian_penjamin.required' => 'Tanggal tutup Penjamin harus diisi',
            'tutup_penilaian_penjamin.after_or_equal' => 'Tanggal tutup Penjamin harus setelah atau sama dengan tanggal buka',
            'buka_penilaian_penilai.required' => 'Tanggal buka Penilai harus diisi',
            'tutup_penilaian_penilai.required' => 'Tanggal tutup Penilai harus diisi',
            'tutup_penilaian_penilai.after_or_equal' => 'Tanggal tutup Penilai harus setelah atau sama dengan tanggal buka',
        ]);

        Setting::updateOrCreate(
            ['tahun_id' => $this->tahun_id],
            [
                'buka_penilaian_mandiri' => $this->buka_penilaian_mandiri,
                'tutup_penilaian_mandiri' => $this->tutup_penilaian_mandiri,
                'buka_penilaian_verifikator' => $this->buka_penilaian_verifikator,
                'tutup_penilaian_verifikator' => $this->tutup_penilaian_verifikator,
                'buka_penilaian_penjamin' => $this->buka_penilaian_penjamin,
                'tutup_penilaian_penjamin' => $this->tutup_penilaian_penjamin,
                'buka_penilaian_penilai' => $this->buka_penilaian_penilai,
                'tutup_penilaian_penilai' => $this->tutup_penilaian_penilai,
            ]
        );

        session()->flash('success', 'Setting berhasil disimpan!');
    }

    #[Computed]
    public function tahunList()
    {
        return Tahun::all();
    }

    #[Computed]
    public function setting()
    {
        return Setting::where('tahun_id', $this->tahun_id)->first();
    }

    #[Computed]
    public function userList()
    {
        return User::with('role', 'opd')->get();
    }

    #[Computed]
    public function roleList()
    {
        return Role::all();
    }
}
