<div class="page-content">
    <div class="container-fluid">
        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Akses Input</h4>
                    </h4>

                    {{-- <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="javascript: void(0);">Dashboards</a></li>
                            <li class="breadcrumb-item active">Pengaturan</li>
                        </ol>
                    </div> --}}

                </div>
            </div>
        </div>
        <!-- end page title -->

        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header align-items-center d-flex">
                        <h4 class="card-title mb-0 flex-grow-1">Akses Input</h4>
                        <button wire:click="saveSetting" type="button" class="btn btn-primary add-btn"><i
                                class="ri-save-3-line align-bottom me-1"></i>Simpan</button>
                    </div><!-- end card header -->

                    <div class="card-body">
                        <div class="live-preview">
                            <div class="row mt-3 mb-3">
                                <div class="col-lg-3">
                                    <label for="nameInput" class="form-label">OPD:</label>
                                </div>
                                <div class="col-lg-4">
                                    <input wire:model="buka_penilaian_mandiri" type="date" class="form-control"
                                        id="opdBukaInput">
                                </div>
                                <div class="col-lg-4">
                                    <input wire:model="tutup_penilaian_mandiri" type="date" class="form-control"
                                        id="opdTutupInput">
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-lg-3">
                                    <label for="nameInput" class="form-label">Verifikator:</label>
                                </div>
                                <div class="col-lg-4">
                                    <input wire:model="buka_penilaian_verifikator" type="date" class="form-control"
                                        id="verifikatorBukaInput">
                                </div>
                                <div class="col-lg-4">
                                    <input wire:model="tutup_penilaian_verifikator" type="date" class="form-control"
                                        id="verifikatorTutupInput">
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-lg-3">
                                    <label for="nameInput" class="form-label">Penjamin:</label>
                                </div>
                                <div class="col-lg-4">
                                    <input wire:model="buka_penilaian_penjamin" type="date" class="form-control"
                                        id="penjaminBukaInput">
                                </div>
                                <div class="col-lg-4">
                                    <input wire:model="tutup_penilaian_penjamin" type="date" class="form-control"
                                        id="penjaminTutupInput">
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-lg-3">
                                    <label for="nameInput" class="form-label">Penilai:</label>
                                </div>
                                <div class="col-lg-4">
                                    <input wire:model="buka_penilaian_penilai" type="date" class="form-control"
                                        id="penilaiBukaInput">
                                </div>
                                <div class="col-lg-4">
                                    <input wire:model="tutup_penilaian_penilai" type="date" class="form-control"
                                        id="penilaiTutupInput">
                                </div>
                            </div>
                        </div>
                    </div><!-- end card-body -->
                </div><!-- end card -->
            </div>
            <!--end col-->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header align-items-center d-flex">
                        <h4 class="card-title mb-0 flex-grow-1">Tahun</h4>
                        <button type="button" class="btn btn-primary add-btn" data-bs-toggle="modal"
                            data-bs-target="#addTahunModal"><i class="ri-add-line align-bottom me-1"></i>Tambah</button>
                    </div><!-- end card header -->

                    <div class="card-body">
                        <div class="live-preview">
                            <div class="table-responsive">
                                <table class="table align-middle table-nowrap mb-0">
                                    <thead>
                                        <tr>
                                            <th scope="col">No</th>
                                            <th scope="col">Tahun</th>
                                            <th scope="col">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($this->tahunList() as $index => $tahun)
                                            <tr>
                                                <th scope="row"><a href="#"
                                                        class="fw-medium">{{ ($this->tahunList()->currentPage() - 1) * $this->tahunList()->perPage() + $index + 1 }}</a>
                                                </th>
                                                <td>{{ $tahun->tahun }}</td>
                                                <td>
                                                    <!-- Base Switchs -->
                                                    <div class="form-check form-switch">
                                                        <input wire:click="toggleStatusTahun({{ $tahun->id }})"
                                                            {{ $tahun->is_active ? 'checked' : '' }}
                                                            class="form-check-input" type="checkbox" role="switch"
                                                            id="switch{{ $tahun->id }}">
                                                        <label class="form-check-label" for="switch{{ $tahun->id }}">
                                                            {{ $tahun->is_active ? 'Aktif' : 'Nonaktif' }}
                                                        </label>
                                                    </div>
                                                </td>
                                                <td class="">
                                                    <button wire:click="setTahunToDelete({{ $tahun->id }})"
                                                        type="button"
                                                        class="btn btn-sm btn-outline-danger btn-icon waves-effect waves-light"
                                                        data-bs-toggle="modal" data-bs-target="#deleteTahunModal">
                                                        <i class="ri-delete-bin-5-line"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <div class="mt-3">
                                {{ $this->tahunList()->links(data: ['scrollTo' => false]) }}
                            </div>
                        </div>
                    </div><!-- end card-body -->
                </div><!-- end card -->
            </div>
            <!--end col-->
        </div>

        <div class="row mt-3">
            <div class="col-md-8">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Kelola User</h4>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header align-items-center d-flex">
                                <h4 class="card-title mb-0 flex-grow-1">Daftar User</h4>
                                <button wire:click="resetUserForm" type="button" class="btn btn-primary add-btn"
                                    data-bs-toggle="modal" data-bs-target="#addUserModal">
                                    <i class="ri-add-line align-bottom me-1"></i>Tambah
                                </button>
                            </div>

                            <div class="card-body">
                                <div class="live-preview">
                                    <div class="table-responsive">
                                        <table class="table align-middle table-nowrap mb-0">
                                            <thead>
                                                <tr>
                                                    <th scope="col">Email</th>
                                                    <th scope="col">OPD</th>
                                                    <th scope="col">Role</th>
                                                    <th scope="col">Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($this->userList() as $user)
                                                    <tr>
                                                        <td>{{ $user->email }}</td>
                                                        <td>{{ $user->opd ? Str::limit($user->opd->nama, 30) : '-' }}
                                                        </td>
                                                        <td>{{ $user->role ? $user->role->nama : '-' }}</td>
                                                        <td>
                                                            <button wire:click="editUser({{ $user->id }})"
                                                                type="button"
                                                                class="btn btn-sm btn-outline-primary btn-icon waves-effect waves-light"
                                                                data-bs-toggle="modal" data-bs-target="#addUserModal">
                                                                <i class="ri-edit-line"></i>
                                                            </button>
                                                            <button wire:click="setUserToDelete({{ $user->id }})"
                                                                type="button"
                                                                class="btn btn-sm btn-outline-danger btn-icon waves-effect waves-light"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#deleteUserModal">
                                                                <i class="ri-delete-bin-5-line"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Pagination -->
                                    <div class="mt-3">
                                        {{ $this->userList()->links(data: ['scrollTo' => false]) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">

                {{-- <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Kelola Role</h4>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header align-items-center d-flex">
                                <h4 class="card-title mb-0 flex-grow-1">Daftar Role</h4>
                                <button type="button" class="btn btn-primary add-btn" data-bs-toggle="modal"
                                    data-bs-target="#addRoleModal">
                                    <i class="ri-add-line align-bottom me-1"></i>Tambah
                                </button>
                            </div>

                            <div class="card-body">
                                <div class="live-preview">
                                    <div class="table-responsive">
                                        <table class="table align-middle table-nowrap mb-0">
                                            <thead>
                                                <tr>
                                                    <th scope="col">No</th>
                                                    <th scope="col">Role</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($this->roleList() as $index => $role)
                                                    <tr>
                                                        <td>{{ $index + 1 }}</td>
                                                        <td>{{ $role->nama }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div> --}}

                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Kelola Maks Bobot</h4>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header align-items-center d-flex">
                                <h4 class="card-title mb-0 flex-grow-1">Batas Bobot Komponen</h4>
                                <button wire:click="saveMaksBobotKomponen" type="button"
                                    class="btn btn-primary add-btn"><i
                                        class="ri-save-3-line align-bottom me-1"></i>Simpan</button>
                            </div>

                            <div class="card-body">
                                <form>
                                    <!-- Input with Icon Right -->
                                    <div class="form-icon right">
                                        <input wire:model="maks_bobot_komponen" type="number"
                                            class="form-control form-control-icon" id="iconrightInput"
                                            placeholder="Masukkan rentang 0-100" min="0" max="100"
                                            step="0.01">
                                        <i class="ri-percent-line"></i>
                                    </div>
                                    @error('maks_bobot_komponen')
                                        <span class="text-danger small">{{ $message }}</span>
                                    @enderror
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Kelola Tipe Penilaian</h4>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header align-items-center d-flex">
                                <h4 class="card-title mb-0 flex-grow-1">Jenis Nilai</h4>
                                <button wire:click="resetJenisNilaiForm" type="button"
                                    class="btn btn-primary add-btn" data-bs-toggle="modal"
                                    data-bs-target="#addJenisNilaiModal">
                                    <i class="ri-add-line align-bottom me-1"></i>Tambah
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table align-middle table-nowrap mb-0">
                                        <thead>
                                            <tr>
                                                <th scope="col">No</th>
                                                <th scope="col">Nama</th>
                                                <th scope="col">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($this->jenisNilaiList() as $index => $jenisNilai)
                                                <tr>
                                                    <td>{{ $index + 1 }}</td>
                                                    <td>{{ $jenisNilai->nama }}</td>
                                                    <td>
                                                        <button wire:click="editJenisNilai({{ $jenisNilai->id }})"
                                                            type="button"
                                                            class="btn btn-sm btn-outline-primary btn-icon waves-effect waves-light"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#addJenisNilaiModal">
                                                            <i class="ri-edit-line"></i>
                                                        </button>
                                                        <button
                                                            wire:click="setJenisNilaiToDelete({{ $jenisNilai->id }})"
                                                            type="button"
                                                            class="btn btn-sm btn-outline-danger btn-icon waves-effect waves-light"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#deleteJenisNilaiModal">
                                                            <i class="ri-delete-bin-5-line"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="3" class="text-center">Tidak ada data jenis nilai
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header align-items-center d-flex">
                                <h4 class="card-title mb-0 flex-grow-1">Tingkatan Nilai</h4>
                                <button wire:click="resetTingkatanNilaiForm" type="button"
                                    class="btn btn-primary add-btn" data-bs-toggle="modal"
                                    data-bs-target="#addTingkatanNilaiModal">
                                    <i class="ri-add-line align-bottom me-1"></i>Tambah
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table align-middle table-nowrap mb-0">
                                        <thead>
                                            <tr>
                                                <th scope="col">No</th>
                                                <th scope="col">Jenis Nilai</th>
                                                <th scope="col">Kode Nilai</th>
                                                <th scope="col">Bobot</th>
                                                <th scope="col">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($this->tingkatanNilaiList() as $index => $tingkatanNilai)
                                                <tr>
                                                    <td>{{ $index + 1 }}</td>
                                                    <td>{{ $tingkatanNilai->jenis_nilai->nama ?? '-' }}</td>
                                                    <td>{{ $tingkatanNilai->kode_nilai }}</td>
                                                    <td>{{ number_format($tingkatanNilai->bobot, 2) }}</td>
                                                    <td>
                                                        <button
                                                            wire:click="editTingkatanNilai({{ $tingkatanNilai->id }})"
                                                            type="button"
                                                            class="btn btn-sm btn-outline-primary btn-icon waves-effect waves-light"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#addTingkatanNilaiModal">
                                                            <i class="ri-edit-line"></i>
                                                        </button>
                                                        <button
                                                            wire:click="setTingkatanNilaiToDelete({{ $tingkatanNilai->id }})"
                                                            type="button"
                                                            class="btn btn-sm btn-outline-danger btn-icon waves-effect waves-light"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#deleteTingkatanNilaiModal">
                                                            <i class="ri-delete-bin-5-line"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5" class="text-center">Tidak ada data tingkatan
                                                        nilai</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!--end col-->
        </div>
    </div>
    <!-- container-fluid -->

    <!-- Grids in modals -->
    <div class="modal fade" id="addTahunModal" tabindex="-1" aria-labelledby="addTahunModalLabel"
        aria-modal="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addTahunModalLabel">Tambah Tahun</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form wire:submit="addTahun">
                        <div class="row g-3">
                            <div class="col-lg-12">
                                <div class="mb-3">
                                    <label for="tahunInput" class="form-label">Tahun</label>
                                    <input wire:model="tahun_input" type="text" class="form-control"
                                        id="tahunInput" placeholder="Masukkan tahun (contoh: 2025)">
                                    @error('tahun_input')
                                        <span class="text-danger small">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="hstack gap-2 justify-content-end">
                                    <button type="button" class="btn btn-light"
                                        data-bs-dismiss="modal">Batal</button>
                                    <button type="submit" class="btn btn-primary">Simpan</button>
                                </div>
                            </div><!--end col-->
                        </div><!--end row-->
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div wire:ignore.self class="modal fade zoomIn" id="deleteTahunModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                        id="btn-close"></button>
                </div>
                <div class="modal-body">
                    <div class="mt-2 text-center">
                        <lord-icon src="https://cdn.lordicon.com/gsqxdxog.json" trigger="loop"
                            colors="primary:#f7b84b,secondary:#f06548" style="width:100px;height:100px"></lord-icon>
                        <div class="mt-4 pt-2 fs-15 mx-4 mx-sm-5">
                            <h4>Hapus Tahun</h4>
                            <p class="text-muted mx-4 mb-0">Apakah anda yakin ingin menghapus tahun ini?</p>
                        </div>
                    </div>
                    <div class="d-flex gap-2 justify-content-center mt-4 mb-2">
                        <button type="button" class="btn w-sm btn-light" data-bs-dismiss="modal">Batal</button>
                        <button wire:click="deleteTahun" type="button" class="btn w-sm btn-danger"
                            data-bs-dismiss="modal" id="delete-record">Hapus</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Add/Edit User -->
    <div wire:ignore.self class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addUserModalLabel">{{ $user_id ? 'Edit' : 'Tambah' }} User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form wire:submit="saveUser">
                        <div class="mb-3">
                            <label for="userName" class="form-label">Nama</label>
                            <input wire:model="user_name" type="text" class="form-control" id="userName"
                                placeholder="Masukkan nama">
                            @error('user_name')
                                <span class="text-danger small">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="userEmail" class="form-label">Email</label>
                            <input wire:model="user_email" type="email" class="form-control" id="userEmail"
                                placeholder="Masukkan email">
                            @error('user_email')
                                <span class="text-danger small">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="userPassword" class="form-label">Password
                                {{ $user_id ? '(kosongkan jika tidak diubah)' : '' }}</label>
                            <input wire:model="user_password" type="password" class="form-control" id="userPassword"
                                placeholder="Masukkan password">
                            @error('user_password')
                                <span class="text-danger small">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="userRole" class="form-label">Role</label>
                            <select wire:model="user_role_id" class="form-select" id="userRole">
                                <option value="">-- Pilih Role --</option>
                                @foreach ($this->roleList() as $role)
                                    <option value="{{ $role->id }}">{{ $role->nama }}</option>
                                @endforeach
                            </select>
                            @error('user_role_id')
                                <span class="text-danger small">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="userOpd" class="form-label">OPD (Opsional)</label>
                            <select wire:model="user_opd_id" class="form-select" id="userOpd">
                                <option value="">-- Pilih OPD --</option>
                                @foreach ($this->opdList() as $opd)
                                    <option value="{{ $opd->id }}">{{ $opd->nama }}</option>
                                @endforeach
                            </select>
                            @error('user_opd_id')
                                <span class="text-danger small">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="hstack gap-2 justify-content-end">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Delete User -->
    <div wire:ignore.self class="modal fade zoomIn" id="deleteUserModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mt-2 text-center">
                        <lord-icon src="https://cdn.lordicon.com/gsqxdxog.json" trigger="loop"
                            colors="primary:#f7b84b,secondary:#f06548" style="width:100px;height:100px"></lord-icon>
                        <div class="mt-4 pt-2 fs-15 mx-4 mx-sm-5">
                            <h4>Hapus User</h4>
                            <p class="text-muted mx-4 mb-0">Apakah anda yakin ingin menghapus user ini?</p>
                        </div>
                    </div>
                    <div class="d-flex gap-2 justify-content-center mt-4 mb-2">
                        <button type="button" class="btn w-sm btn-light" data-bs-dismiss="modal">Batal</button>
                        <button wire:click="deleteUser" type="button" class="btn w-sm btn-danger"
                            data-bs-dismiss="modal">Hapus</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Add Role -->
    <div wire:ignore.self class="modal fade" id="addRoleModal" tabindex="-1" aria-labelledby="addRoleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addRoleModalLabel">Tambah Role</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form wire:submit="addRole">
                        <div class="mb-3">
                            <label for="roleName" class="form-label">Nama Role</label>
                            <input wire:model="role_nama" type="text" class="form-control" id="roleName"
                                placeholder="Masukkan nama role">
                            @error('role_nama')
                                <span class="text-danger small">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="roleJenis" class="form-label">Jenis Role</label>
                            <select wire:model="role_jenis" class="form-select" id="roleJenis">
                                <option value="">-- Pilih Jenis --</option>
                                <option value="admin">Admin</option>
                                <option value="opd">OPD</option>
                                <option value="verifikator">Verifikator</option>
                                <option value="penjamin">Penjamin</option>
                                <option value="penilai">Penilai</option>
                            </select>
                            @error('role_jenis')
                                <span class="text-danger small">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="hstack gap-2 justify-content-end">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Add/Edit Jenis Nilai -->
    <div wire:ignore.self class="modal fade" id="addJenisNilaiModal" tabindex="-1"
        aria-labelledby="addJenisNilaiModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addJenisNilaiModalLabel">{{ $jenis_nilai_id ? 'Edit' : 'Tambah' }}
                        Jenis Nilai</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form wire:submit="saveJenisNilai">
                        <div class="mb-3">
                            <label for="jenisNilaiNama" class="form-label">Nama Jenis Nilai</label>
                            <input wire:model="jenis_nilai_nama" type="text" class="form-control"
                                id="jenisNilaiNama" placeholder="Contoh: Kualitatif, Kuantitatif">
                            @error('jenis_nilai_nama')
                                <span class="text-danger small">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="hstack gap-2 justify-content-end">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Delete Jenis Nilai -->
    <div wire:ignore.self class="modal fade zoomIn" id="deleteJenisNilaiModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mt-2 text-center">
                        <lord-icon src="https://cdn.lordicon.com/gsqxdxog.json" trigger="loop"
                            colors="primary:#f7b84b,secondary:#f06548" style="width:100px;height:100px"></lord-icon>
                        <div class="mt-4 pt-2 fs-15 mx-4 mx-sm-5">
                            <h4>Hapus Jenis Nilai</h4>
                            <p class="text-muted mx-4 mb-0">Apakah anda yakin ingin menghapus jenis nilai ini? Semua
                                tingkatan nilai terkait akan ikut terhapus.</p>
                        </div>
                    </div>
                    <div class="d-flex gap-2 justify-content-center mt-4 mb-2">
                        <button type="button" class="btn w-sm btn-light" data-bs-dismiss="modal">Batal</button>
                        <button wire:click="deleteJenisNilai" type="button" class="btn w-sm btn-danger"
                            data-bs-dismiss="modal">Hapus</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Add/Edit Tingkatan Nilai -->
    <div wire:ignore.self class="modal fade" id="addTingkatanNilaiModal" tabindex="-1"
        aria-labelledby="addTingkatanNilaiModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addTingkatanNilaiModalLabel">
                        {{ $tingkatan_nilai_id ? 'Edit' : 'Tambah' }} Tingkatan Nilai</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form wire:submit="saveTingkatanNilai">
                        <div class="mb-3">
                            <label for="tingkatanNilaiJenisNilai" class="form-label">Jenis Nilai</label>
                            <select wire:model="tingkatan_nilai_jenis_nilai_id" class="form-select"
                                id="tingkatanNilaiJenisNilai">
                                <option value="">-- Pilih Jenis Nilai --</option>
                                @foreach ($this->jenisNilaiList() as $jenisNilai)
                                    <option value="{{ $jenisNilai->id }}">{{ $jenisNilai->nama }}</option>
                                @endforeach
                            </select>
                            @error('tingkatan_nilai_jenis_nilai_id')
                                <span class="text-danger small">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="tingkatanNilaiKodeNilai" class="form-label">Kode Nilai</label>
                            <input wire:model="tingkatan_nilai_kode_nilai" type="text" class="form-control"
                                id="tingkatanNilaiKodeNilai"
                                placeholder="Contoh: A, B, C atau Rendah, Sedang, Tinggi">
                            @error('tingkatan_nilai_kode_nilai')
                                <span class="text-danger small">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="tingkatanNilaiBobot" class="form-label">Bobot</label>
                            <input wire:model="tingkatan_nilai_bobot" type="number" class="form-control"
                                id="tingkatanNilaiBobot" placeholder="Contoh: 4.00, 3.00, 2.00" min="0"
                                step="0.01">
                            @error('tingkatan_nilai_bobot')
                                <span class="text-danger small">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="hstack gap-2 justify-content-end">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Delete Tingkatan Nilai -->
    <div wire:ignore.self class="modal fade zoomIn" id="deleteTingkatanNilaiModal" tabindex="-1"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mt-2 text-center">
                        <lord-icon src="https://cdn.lordicon.com/gsqxdxog.json" trigger="loop"
                            colors="primary:#f7b84b,secondary:#f06548" style="width:100px;height:100px"></lord-icon>
                        <div class="mt-4 pt-2 fs-15 mx-4 mx-sm-5">
                            <h4>Hapus Tingkatan Nilai</h4>
                            <p class="text-muted mx-4 mb-0">Apakah anda yakin ingin menghapus tingkatan nilai ini?</p>
                        </div>
                    </div>
                    <div class="d-flex gap-2 justify-content-center mt-4 mb-2">
                        <button type="button" class="btn w-sm btn-light" data-bs-dismiss="modal">Batal</button>
                        <button wire:click="deleteTingkatanNilai" type="button" class="btn w-sm btn-danger"
                            data-bs-dismiss="modal">Hapus</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Close modal after successful save
        window.addEventListener('close-modal', event => {
            const modalId = event.detail[0];
            const modalElement = document.getElementById(modalId);
            if (modalElement) {
                const modal = bootstrap.Modal.getInstance(modalElement);
                if (modal) {
                    modal.hide();
                }
            }
        });
    </script>
</div>
<!-- End Page-content -->
