<?php

namespace App\Livewire\Dashboard;

use App\Models\Setting;
use App\Models\Tahun;
use App\Models\User;
use App\Models\Opd;
use Livewire\Attributes\Computed;
use Livewire\Component;
use App\Models\Role;
use Livewire\WithPagination;
use Livewire\WithoutUrlPagination;
use Illuminate\Support\Facades\Hash;
use function Flasher\Prime\flash;

class Pengaturan extends Component
{
    protected $paginationTheme = 'bootstrap';
    use WithPagination, WithoutUrlPagination;

    public $buka_penilaian_mandiri, $tutup_penilaian_mandiri, $buka_penilaian_verifikator, $tutup_penilaian_verifikator, $buka_penilaian_penjamin, $tutup_penilaian_penjamin, $buka_penilaian_penilai, $tutup_penilaian_penilai;
    public $tahun_id;
    public $maks_bobot_komponen = 100; // Default 100%

    // Tahun properties
    public $tahun_input;
    public $tahun_id_to_delete;

    // User properties
    public $user_id, $user_name, $user_email, $user_password, $user_role_id, $user_opd_id;
    public $user_id_to_delete;

    // Role properties
    public $role_nama, $role_jenis;

    // Jenis Nilai properties
    public $jenis_nilai_id, $jenis_nilai_nama;
    public $jenis_nilai_id_to_delete;

    // Tingkatan Nilai properties
    public $tingkatan_nilai_id, $tingkatan_nilai_jenis_nilai_id, $tingkatan_nilai_kode_nilai, $tingkatan_nilai_bobot;
    public $tingkatan_nilai_id_to_delete;

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
            // Format tanggal ke Y-m-d\TH:i untuk input type="datetime-local"
            $this->buka_penilaian_mandiri = $setting->buka_penilaian_mandiri ? date('Y-m-d\TH:i', strtotime($setting->buka_penilaian_mandiri)) : null;
            $this->tutup_penilaian_mandiri = $setting->tutup_penilaian_mandiri ? date('Y-m-d\TH:i', strtotime($setting->tutup_penilaian_mandiri)) : null;
            $this->buka_penilaian_verifikator = $setting->buka_penilaian_verifikator ? date('Y-m-d\TH:i', strtotime($setting->buka_penilaian_verifikator)) : null;
            $this->tutup_penilaian_verifikator = $setting->tutup_penilaian_verifikator ? date('Y-m-d\TH:i', strtotime($setting->tutup_penilaian_verifikator)) : null;
            $this->buka_penilaian_penjamin = $setting->buka_penilaian_penjamin ? date('Y-m-d\TH:i', strtotime($setting->buka_penilaian_penjamin)) : null;
            $this->tutup_penilaian_penjamin = $setting->tutup_penilaian_penjamin ? date('Y-m-d\TH:i', strtotime($setting->tutup_penilaian_penjamin)) : null;
            $this->buka_penilaian_penilai = $setting->buka_penilaian_penilai ? date('Y-m-d\TH:i', strtotime($setting->buka_penilaian_penilai)) : null;
            $this->tutup_penilaian_penilai = $setting->tutup_penilaian_penilai ? date('Y-m-d\TH:i', strtotime($setting->tutup_penilaian_penilai)) : null;
            $this->maks_bobot_komponen = $setting->maks_bobot_komponen ?? 100;
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
                'maks_bobot_komponen' => $this->maks_bobot_komponen ?? 100, // Fallback ke 100 jika null
            ]
        );

        flash()->use('theme.ruby')->option('position', 'bottom-right')->success('Pengaturan berhasil disimpan.');
    }

    #[Computed]
    public function tahunList()
    {
        return Tahun::paginate(5, ['*'], 'tahunPage');
    }

    #[Computed]
    public function setting()
    {
        return Setting::where('tahun_id', $this->tahun_id)->first();
    }

    #[Computed]
    public function userList()
    {
        return User::with('role', 'opd')->paginate(10, ['*'], 'userPage');
    }

    #[Computed]
    public function roleList()
    {
        return Role::all();
    }

    #[Computed]
    public function opdList()
    {
        return Opd::all();
    }

    #[Computed]
    public function jenisNilaiList()
    {
        return \App\Models\JenisNilai::all();
    }

    #[Computed]
    public function tingkatanNilaiList()
    {
        return \App\Models\TingkatanNilai::with('jenis_nilai')->get();
    }

    #[Computed]
    public function buktiDukungList()
    {
        return \App\Models\BuktiDukung::with('kriteria_komponen', 'role')->paginate(10, ['*'], 'buktiDukungPage');
    }

    // ========== MAKS BOBOT KOMPONEN ==========
    public function saveMaksBobotKomponen()
    {
        $this->validate([
            'maks_bobot_komponen' => 'required|numeric|min:0|max:100',
        ], [
            'maks_bobot_komponen.required' => 'Batas bobot harus diisi',
            'maks_bobot_komponen.numeric' => 'Batas bobot harus berupa angka',
            'maks_bobot_komponen.min' => 'Batas bobot minimal 0',
            'maks_bobot_komponen.max' => 'Batas bobot maksimal 100',
        ]);

        Setting::updateOrCreate(
            ['tahun_id' => $this->tahun_id],
            ['maks_bobot_komponen' => $this->maks_bobot_komponen]
        );

        flash()->use('theme.ruby')->option('position', 'bottom-right')->success('Batas bobot komponen berhasil disimpan.');
    }

    // ========== TAHUN CRUD ==========
    public function addTahun()
    {
        $this->validate([
            'tahun_input' => 'required|integer|digits:4|unique:tahun,tahun',
        ], [
            'tahun_input.required' => 'Tahun harus diisi',
            'tahun_input.integer' => 'Tahun harus berupa angka',
            'tahun_input.digits' => 'Tahun harus 4 digit',
            'tahun_input.unique' => 'Tahun sudah ada',
        ]);

        Tahun::create([
            'tahun' => $this->tahun_input,
            'is_active' => false,
        ]);

        $this->tahun_input = '';
        flash()->use('theme.ruby')->option('position', 'bottom-right')->success('Tahun berhasil ditambahkan.');
        $this->dispatch('close-modal', 'addTahunModal');
    }

    public function toggleStatusTahun($id)
    {
        $tahun = Tahun::find($id);

        if ($tahun->is_active) {
            // Jika ingin menonaktifkan
            $tahun->update(['is_active' => false]);
            flash()->use('theme.ruby')->option('position', 'bottom-right')->info('Tahun dinonaktifkan.');
        } else {
            // Nonaktifkan semua tahun dulu
            Tahun::query()->update(['is_active' => false]);
            // Aktifkan tahun yang dipilih
            $tahun->update(['is_active' => true]);
            // Update session
            session(['tahun_session' => $id]);
            flash()->use('theme.ruby')->option('position', 'bottom-right')->success('Tahun diaktifkan.');
        }
    }

    public function setTahunToDelete($id)
    {
        $this->tahun_id_to_delete = $id;
    }

    public function deleteTahun()
    {
        if ($this->tahun_id_to_delete) {
            $tahun = Tahun::find($this->tahun_id_to_delete);

            if ($tahun->is_active) {
                flash()->use('theme.ruby')->option('position', 'bottom-right')->error('Tidak dapat menghapus tahun yang sedang aktif.');
                return;
            }

            $tahun->delete();
            $this->tahun_id_to_delete = null;
            flash()->use('theme.ruby')->option('position', 'bottom-right')->success('Tahun berhasil dihapus.');
        }
    }

    // ========== USER CRUD ==========
    public function editUser($id)
    {
        $user = User::find($id);
        $this->user_id = $user->id;
        $this->user_name = $user->name;
        $this->user_email = $user->email;
        $this->user_role_id = $user->role_id;
        $this->user_opd_id = $user->opd_id;
        $this->user_password = ''; // Password kosong saat edit
    }

    public function resetUserForm()
    {
        $this->user_id = null;
        $this->user_name = '';
        $this->user_email = '';
        $this->user_password = '';
        $this->user_role_id = null;
        $this->user_opd_id = null;
    }

    public function saveUser()
    {
        $rules = [
            'user_name' => 'required|string|max:255',
            'user_email' => 'required|email|unique:users,email,' . $this->user_id,
            'user_role_id' => 'required|exists:role,id',
            'user_opd_id' => 'nullable|exists:opd,id',
        ];

        // Jika user baru (create), password wajib
        if (!$this->user_id) {
            $rules['user_password'] = 'required|min:6';
        } else {
            // Jika edit dan password diisi, validasi
            if ($this->user_password) {
                $rules['user_password'] = 'min:6';
            }
        }

        $this->validate($rules, [
            'user_name.required' => 'Nama harus diisi',
            'user_email.required' => 'Email harus diisi',
            'user_email.email' => 'Format email tidak valid',
            'user_email.unique' => 'Email sudah digunakan',
            'user_role_id.required' => 'Role harus dipilih',
            'user_password.required' => 'Password harus diisi',
            'user_password.min' => 'Password minimal 6 karakter',
        ]);

        $data = [
            'name' => $this->user_name,
            'email' => $this->user_email,
            'role_id' => $this->user_role_id,
            'opd_id' => $this->user_opd_id,
        ];

        // Jika password diisi, hash dan update
        if ($this->user_password) {
            $data['password'] = Hash::make($this->user_password);
        }

        if ($this->user_id) {
            // Update
            User::find($this->user_id)->update($data);
            flash()->use('theme.ruby')->option('position', 'bottom-right')->success('User berhasil diupdate.');
        } else {
            // Create
            User::create($data);
            flash()->use('theme.ruby')->option('position', 'bottom-right')->success('User berhasil ditambahkan.');
        }

        $this->resetUserForm();
        $this->dispatch('close-modal', 'addUserModal');
    }

    public function setUserToDelete($id)
    {
        $this->user_id_to_delete = $id;
    }

    public function deleteUser()
    {
        if ($this->user_id_to_delete) {
            User::find($this->user_id_to_delete)->delete();
            $this->user_id_to_delete = null;
            flash()->use('theme.ruby')->option('position', 'bottom-right')->success('User berhasil dihapus.');
        }
    }

    // ========== ROLE CRUD ==========
    public function addRole()
    {
        $this->validate([
            'role_nama' => 'required|string|max:255',
            'role_jenis' => 'required|in:admin,opd,verifikator,penjamin,penilai',
        ], [
            'role_nama.required' => 'Nama role harus diisi',
            'role_jenis.required' => 'Jenis role harus dipilih',
            'role_jenis.in' => 'Jenis role tidak valid',
        ]);

        Role::create([
            'nama' => $this->role_nama,
            'jenis' => $this->role_jenis,
        ]);

        $this->role_nama = '';
        $this->role_jenis = '';
        flash()->use('theme.ruby')->option('position', 'bottom-right')->success('Role berhasil ditambahkan.');
        $this->dispatch('close-modal', 'addRoleModal');
    }

    // ========== JENIS NILAI CRUD ==========
    public function resetJenisNilaiForm()
    {
        $this->jenis_nilai_id = null;
        $this->jenis_nilai_nama = '';
    }

    public function editJenisNilai($id)
    {
        $jenisNilai = \App\Models\JenisNilai::find($id);
        $this->jenis_nilai_id = $jenisNilai->id;
        $this->jenis_nilai_nama = $jenisNilai->nama;
    }

    public function saveJenisNilai()
    {
        $this->validate([
            'jenis_nilai_nama' => 'required|string|max:255',
        ], [
            'jenis_nilai_nama.required' => 'Nama jenis nilai harus diisi',
        ]);

        if ($this->jenis_nilai_id) {
            \App\Models\JenisNilai::find($this->jenis_nilai_id)->update([
                'nama' => $this->jenis_nilai_nama,
            ]);
            flash()->use('theme.ruby')->option('position', 'bottom-right')->success('Jenis nilai berhasil diupdate.');
        } else {
            \App\Models\JenisNilai::create([
                'nama' => $this->jenis_nilai_nama,
            ]);
            flash()->use('theme.ruby')->option('position', 'bottom-right')->success('Jenis nilai berhasil ditambahkan.');
        }

        $this->resetJenisNilaiForm();
        $this->dispatch('close-modal', 'addJenisNilaiModal');
    }

    public function setJenisNilaiToDelete($id)
    {
        $this->jenis_nilai_id_to_delete = $id;
    }

    public function deleteJenisNilai()
    {
        if ($this->jenis_nilai_id_to_delete) {
            \App\Models\JenisNilai::find($this->jenis_nilai_id_to_delete)->delete();
            $this->jenis_nilai_id_to_delete = null;
            flash()->use('theme.ruby')->option('position', 'bottom-right')->success('Jenis nilai berhasil dihapus.');
        }
    }

    // ========== TINGKATAN NILAI CRUD ==========
    public function resetTingkatanNilaiForm()
    {
        $this->tingkatan_nilai_id = null;
        $this->tingkatan_nilai_jenis_nilai_id = null;
        $this->tingkatan_nilai_kode_nilai = '';
        $this->tingkatan_nilai_bobot = '';
    }

    public function editTingkatanNilai($id)
    {
        $tingkatanNilai = \App\Models\TingkatanNilai::find($id);
        $this->tingkatan_nilai_id = $tingkatanNilai->id;
        $this->tingkatan_nilai_jenis_nilai_id = $tingkatanNilai->jenis_nilai_id;
        $this->tingkatan_nilai_kode_nilai = $tingkatanNilai->kode_nilai;
        $this->tingkatan_nilai_bobot = $tingkatanNilai->bobot;
    }

    public function saveTingkatanNilai()
    {
        $this->validate([
            'tingkatan_nilai_jenis_nilai_id' => 'required|exists:jenis_nilai,id',
            'tingkatan_nilai_kode_nilai' => 'required|string|max:255',
            'tingkatan_nilai_bobot' => 'required|numeric|min:0',
        ], [
            'tingkatan_nilai_jenis_nilai_id.required' => 'Jenis nilai harus dipilih',
            'tingkatan_nilai_kode_nilai.required' => 'Kode nilai harus diisi',
            'tingkatan_nilai_bobot.required' => 'Bobot harus diisi',
            'tingkatan_nilai_bobot.numeric' => 'Bobot harus berupa angka',
            'tingkatan_nilai_bobot.min' => 'Bobot minimal 0',
        ]);

        if ($this->tingkatan_nilai_id) {
            \App\Models\TingkatanNilai::find($this->tingkatan_nilai_id)->update([
                'jenis_nilai_id' => $this->tingkatan_nilai_jenis_nilai_id,
                'kode_nilai' => $this->tingkatan_nilai_kode_nilai,
                'bobot' => $this->tingkatan_nilai_bobot,
            ]);
            flash()->use('theme.ruby')->option('position', 'bottom-right')->success('Tingkatan nilai berhasil diupdate.');
        } else {
            \App\Models\TingkatanNilai::create([
                'jenis_nilai_id' => $this->tingkatan_nilai_jenis_nilai_id,
                'kode_nilai' => $this->tingkatan_nilai_kode_nilai,
                'bobot' => $this->tingkatan_nilai_bobot,
            ]);
            flash()->use('theme.ruby')->option('position', 'bottom-right')->success('Tingkatan nilai berhasil ditambahkan.');
        }

        $this->resetTingkatanNilaiForm();
        $this->dispatch('close-modal', 'addTingkatanNilaiModal');
    }

    public function setTingkatanNilaiToDelete($id)
    {
        $this->tingkatan_nilai_id_to_delete = $id;
    }

    public function deleteTingkatanNilai()
    {
        if ($this->tingkatan_nilai_id_to_delete) {
            \App\Models\TingkatanNilai::find($this->tingkatan_nilai_id_to_delete)->delete();
            $this->tingkatan_nilai_id_to_delete = null;
            flash()->use('theme.ruby')->option('position', 'bottom-right')->success('Tingkatan nilai berhasil dihapus.');
        }
    }

    public function render()
    {
        return view('livewire.dashboard.pengaturan');
    }
}
