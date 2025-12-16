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
            <div class="col-md-6">
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
                                                        class="fw-medium">{{ $index + 1 }}</a></th>
                                                <td>{{ $tahun->tahun }}</td>
                                                <td>
                                                    <!-- Base Switchs -->
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" role="switch"
                                                            id="flexSwitchCheckDefault">
                                                    </div>
                                                </td>
                                                <td class="">
                                                    <button type="button"
                                                        class="btn btn-sm btn-outline-danger btn-icon waves-effect waves-light"><i
                                                            class="ri-delete-bin-5-line" data-bs-toggle="modal"
                                                            data-bs-target="#deleteTahunModal"></i></button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div><!-- end card-body -->
                </div><!-- end card -->
            </div>
            <!--end col-->
            <div class="col-md-6">
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
        </div>

        <div class="row mt-3">
            <div class="col-md-8">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Master User</h4>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header align-items-center d-flex">
                                <h4 class="card-title mb-0 flex-grow-1">Daftar User</h4>
                                <a href="" type="button" class="btn btn-primary add-btn"><i
                                        class="ri-add-line align-bottom me-1"></i>Tambah</a>
                            </div>

                            <div class="card-body">
                                <div class="live-preview">
                                    <div class="table-responsive">
                                        <table class="table align-middle table-nowrap mb-0">
                                            <thead>
                                                <tr>
                                                    <th scope="col">Email</th>
                                                    {{-- <th scope="col">OPD</th> --}}
                                                    <th scope="col">Role</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($this->userList() as $user)
                                                    <tr>
                                                        <td>{{ $user->email }}</td>
                                                        <td>{{ $user->opd ? $user->opd->nama : '-' }}</td>
                                                        <td>{{ $user->role ? $user->role->nama : '-' }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Master Role</h4>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header align-items-center d-flex">
                                <h4 class="card-title mb-0 flex-grow-1">Daftar Role</h4>
                                <a href="" type="button" class="btn btn-primary add-btn"><i
                                        class="ri-add-line align-bottom me-1"></i>Tambah</a>
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
                </div>
            </div>
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
                    <form action="javascript:void(0);">
                        <div class="row g-3">
                            <div class="col-lg-12">
                                <div class="mb-3">
                                    <label for="tahunInput" class="form-label">Tahun</label>
                                    <input type="text" class="form-control" id="tahunInput"
                                        placeholder="Masukkan tahun">
                                </div>
                                <div class="hstack gap-2 justify-content-end">
                                    <button type="button" class="btn btn-light"
                                        data-bs-dismiss="modal">Close</button>
                                    <button type="submit" class="btn btn-primary">Submit</button>
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
</div>
<!-- End Page-content -->
