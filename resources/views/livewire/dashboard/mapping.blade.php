<div class="page-content" x-data="{
    komponenId: null,
    subKomponenId: null,
    kriteriaKomponenId: null,
    expandedKomponen: {},
    expandedSub: {},
    expandedKriteria: {},
    toggleKomponen(id) { this.expandedKomponen[id] = !this.expandedKomponen[id] },
    toggleSub(id) { this.expandedSub[id] = !this.expandedSub[id] },
    toggleKriteria(id) { this.expandedKriteria[id] = !this.expandedKriteria[id] }
}" x-cloak>
    <div class="container-fluid">

        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Mapping</h4>

                    {{-- @dump(session()->all()
                    @dump($tahun_id) --}}

                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="javascript: void(0);">Dashboards</a></li>
                            <li class="breadcrumb-item active">Mapping</li>
                        </ol>
                    </div>

                </div>
            </div>
        </div>
        <!-- end page title -->

        <div class="row">
            <div class="col-xs-12">
                <div class="d-flex flex-column h-100">
                    <div class="col-xl-12">
                        <div class="card">
                            {{-- <div class="card-header align-items-center d-flex">
                                <h4 class="card-title mb-0 flex-grow-1">Mapping</h4>
                                <div class="flex-shrink-0">
                                    <div class="form-check form-switch form-switch-right form-switch-md">
                                        <label for="responsive-table-showcode" class="form-label text-muted">Show
                                            Code</label>
                                        <input class="form-check-input code-switcher" type="checkbox"
                                            id="responsive-table-showcode">
                                    </div>
                                </div>
                            </div><!-- end card header --> --}}

                            <div class="card-body">
                                @if ($errors->any())
                                    <div class="alert alert-danger">
                                        <ul class="mb-0">
                                            @foreach ($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        {{-- <p class="text-muted mb-0">Kelola struktur mapping komponen penilaian</p> --}}
                                    </div>
                                    <div>
                                        <button type="button" class="btn btn-primary btn-md" data-bs-toggle="modal"
                                            data-bs-target="#addKomponenModal">
                                            <i class="ri-add-line align-middle me-1"></i> Tambah Komponen
                                        </button>
                                    </div>
                                </div>

                                <div class="live-preview">
                                    <div class="table-responsive">
                                        <table class="table align-middle table-nowrap mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th scope="col">Kode</th>
                                                    <th scope="col">Komponen/Sub Komponen/Kriteria Komponen/Bukti
                                                        Dukung</th>
                                                    <th scope="col">Bobot</th>
                                                    <th scope="col">Kriteria Penilaian</th>
                                                    <th scope="col">Verifikator/Penilai</th>
                                                    <th scope="col" style="width: 120px;">Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($this->fullMapping as $komponen)
                                                    {{-- Komponen (top-level) --}}
                                                    <tr class="row-komponen" data-id="komponen-{{ $komponen->id }}">
                                                        <td>
                                                            <span @click="toggleKomponen({{ $komponen->id }})"
                                                                style="cursor:pointer; user-select:none; color:#405189; font-weight:bold;"
                                                                x-text="expandedKomponen[{{ $komponen->id }}] ? '−' : '+'">+</span>
                                                            {{ $komponen->kode }}
                                                        </td>
                                                        <td><strong>{{ $komponen->nama }}</strong></td>
                                                        <td>{{ $komponen->bobot }}</td>
                                                        <td></td>
                                                        <td>{{ $komponen->role->nama ?? '' }}</td>
                                                        <td>
                                                            <div class="dropdown">
                                                                <button
                                                                    class="btn btn-soft-secondary btn-sm dropdown-toggle"
                                                                    type="button" data-bs-toggle="dropdown"
                                                                    aria-expanded="false">
                                                                    <i class="ri-more-fill"></i>
                                                                </button>
                                                                <ul class="dropdown-menu dropdown-menu-end">
                                                                    <li><a class="dropdown-item" href="#"
                                                                            x-on:click="komponenId = {{ $komponen->id }}"
                                                                            data-bs-toggle="modal"
                                                                            data-bs-target="#addSubKomponenModal"><i
                                                                                class="ri-add-line align-middle me-2 text-success"></i>Tambah
                                                                            Sub</a></li>
                                                                    <li><a class="dropdown-item" href="#"
                                                                            wire:click="editKomponen({{ $komponen->id }})"
                                                                            data-bs-toggle="modal"
                                                                            data-bs-target="#editKomponenModal"><i
                                                                                class="ri-edit-line align-middle me-2 text-primary"></i>Edit</a>
                                                                    </li>
                                                                    <li>
                                                                        <hr class="dropdown-divider">
                                                                    </li>
                                                                    <li><a class="dropdown-item text-danger"
                                                                            href="#"
                                                                            wire:click="deleteKomponen({{ $komponen->id }})"
                                                                            onclick="return confirm('Yakin ingin menghapus komponen ini? Semua sub komponen, kriteria, dan bukti dukung terkait juga akan terhapus.')"><i
                                                                                class="ri-delete-bin-line align-middle me-2"></i>Hapus</a>
                                                                    </li>
                                                                </ul>
                                                            </div>
                                                        </td>
                                                    </tr>

                                                    @foreach ($komponen['sub_komponen'] as $sub_komponen)
                                                        {{-- Sub Komponen (child of komponen) --}}
                                                        <tr class="row-sub"
                                                            x-show="expandedKomponen[{{ $komponen->id }}]" x-transition
                                                            data-parent="komponen-{{ $komponen->id }}"
                                                            data-id="sub-{{ $sub_komponen->id }}">
                                                            <td>
                                                                <span style="padding-left:20px;"></span>
                                                                <span @click="toggleSub({{ $sub_komponen->id }})"
                                                                    style="cursor:pointer; user-select:none; color:#0ab39c; font-weight:bold;"
                                                                    x-text="expandedSub[{{ $sub_komponen->id }}] ? '−' : '+'">+</span>
                                                                {{ $sub_komponen->kode }}
                                                            </td>
                                                            <td style="padding-left:30px;">{{ $sub_komponen->nama }}
                                                            </td>
                                                            <td>{{ $sub_komponen->bobot }}</td>
                                                            <td></td>
                                                            <td></td>
                                                            <td>
                                                                <div class="dropdown">
                                                                    <button
                                                                        class="btn btn-soft-secondary btn-sm dropdown-toggle"
                                                                        type="button" data-bs-toggle="dropdown"
                                                                        aria-expanded="false">
                                                                        <i class="ri-more-fill"></i>
                                                                    </button>
                                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                                        <li><a class="dropdown-item" href="#"
                                                                                x-on:click="subKomponenId = {{ $sub_komponen->id }}"
                                                                                data-bs-toggle="modal"
                                                                                data-bs-target="#addKriteriaModal"><i
                                                                                    class="ri-add-line align-middle me-2 text-success"></i>Tambah
                                                                                Kriteria</a></li>
                                                                        <li><a class="dropdown-item" href="#"
                                                                                wire:click="editSubKomponen({{ $sub_komponen->id }})"
                                                                                data-bs-toggle="modal"
                                                                                data-bs-target="#editSubKomponenModal"><i
                                                                                    class="ri-edit-line align-middle me-2 text-primary"></i>Edit</a>
                                                                        </li>
                                                                        <li>
                                                                            <hr class="dropdown-divider">
                                                                        </li>
                                                                        <li><a class="dropdown-item text-danger"
                                                                                href="#"
                                                                                wire:click="deleteSubKomponen({{ $sub_komponen->id }})"
                                                                                onclick="return confirm('Yakin ingin menghapus sub komponen ini? Semua kriteria dan bukti dukung terkait juga akan terhapus.')"><i
                                                                                    class="ri-delete-bin-line align-middle me-2"></i>Hapus</a>
                                                                        </li>
                                                                    </ul>
                                                                </div>
                                                            </td>
                                                        </tr>

                                                        @foreach ($sub_komponen['kriteria_komponen'] as $kriteria_komponen)
                                                            {{-- Kriteria Komponen (child of sub) --}}
                                                            <tr class="row-kriteria"
                                                                x-show="expandedKomponen[{{ $komponen->id }}] && expandedSub[{{ $sub_komponen->id }}]"
                                                                x-transition data-parent="sub-{{ $sub_komponen->id }}"
                                                                data-id="kriteria-{{ $kriteria_komponen->id }}">
                                                                <td>
                                                                    <span style="padding-left:40px;"></span>
                                                                    <span
                                                                        @click="toggleKriteria({{ $kriteria_komponen->id }})"
                                                                        style="cursor:pointer; user-select:none; color:#f06548; font-weight:bold;"
                                                                        x-text="expandedKriteria[{{ $kriteria_komponen->id }}] ? '−' : '+'">+</span>
                                                                    {{ $kriteria_komponen->kode }}
                                                                </td>
                                                                <td style="padding-left:60px;">
                                                                    {{ $kriteria_komponen->nama }}</td>
                                                                <td>{{ $kriteria_komponen->bobot }}</td>
                                                                <td>{{ $kriteria_komponen->jenis_nilai->nama }}</td>
                                                                <td></td>
                                                                <td>
                                                                    <div class="dropdown">
                                                                        <button
                                                                            class="btn btn-soft-secondary btn-sm dropdown-toggle"
                                                                            type="button" data-bs-toggle="dropdown"
                                                                            aria-expanded="false">
                                                                            <i class="ri-more-fill"></i>
                                                                        </button>
                                                                        <ul class="dropdown-menu dropdown-menu-end">
                                                                            <li><a class="dropdown-item"
                                                                                    href="#"
                                                                                    x-on:click="kriteriaKomponenId = {{ $kriteria_komponen->id }}"
                                                                                    data-bs-toggle="modal"
                                                                                    data-bs-target="#addBuktiModal"><i
                                                                                        class="ri-add-line align-middle me-2 text-success"></i>Tambah
                                                                                    Bukti</a></li>
                                                                            <li><a class="dropdown-item"
                                                                                    href="#"
                                                                                    wire:click="editKriteriaKomponen({{ $kriteria_komponen->id }})"
                                                                                    data-bs-toggle="modal"
                                                                                    data-bs-target="#editKriteriaModal"><i
                                                                                        class="ri-edit-line align-middle me-2 text-primary"></i>Edit</a>
                                                                            </li>
                                                                            <li>
                                                                                <hr class="dropdown-divider">
                                                                            </li>
                                                                            <li><a class="dropdown-item text-danger"
                                                                                    href="#"
                                                                                    wire:click="deleteKriteriaKomponen({{ $kriteria_komponen->id }})"
                                                                                    onclick="return confirm('Yakin ingin menghapus kriteria komponen ini? Semua bukti dukung terkait juga akan terhapus.')"><i
                                                                                        class="ri-delete-bin-line align-middle me-2"></i>Hapus</a>
                                                                            </li>
                                                                        </ul>
                                                                    </div>
                                                                </td>
                                                            </tr>

                                                            @foreach ($kriteria_komponen['bukti_dukung'] as $bukti_dukung)
                                                                {{-- Bukti Dukung (child of kriteria) --}}
                                                                <tr class="row-bukti"
                                                                    x-show="expandedKomponen[{{ $komponen->id }}] && expandedSub[{{ $sub_komponen->id }}] && expandedKriteria[{{ $kriteria_komponen->id }}]"
                                                                    x-transition
                                                                    data-parent="kriteria-{{ $kriteria_komponen->id }}">
                                                                    <td style="padding-left:80px;">
                                                                        {{ $bukti_dukung->kode }}</td>
                                                                    <td style="padding-left:90px;">
                                                                        {{ $bukti_dukung->nama }}</td>
                                                                    <td>{{ $bukti_dukung->bobot }}</td>
                                                                    <td>{{ $bukti_dukung->kriteria_penilaian }}</td>
                                                                    <td></td>
                                                                    <td>
                                                                        <div class="hstack gap-2">
                                                                            <button class="btn btn-sm btn-soft-primary"
                                                                                wire:click="editBuktiDukung({{ $bukti_dukung->id }})"
                                                                                data-bs-toggle="modal"
                                                                                data-bs-target="#editBuktiModal"
                                                                                title="Edit"><i
                                                                                    class="ri-edit-line"></i></button>
                                                                            <button class="btn btn-sm btn-soft-danger"
                                                                                wire:click="deleteBuktiDukung({{ $bukti_dukung->id }})"
                                                                                onclick="return confirm('Yakin ingin menghapus bukti dukung ini?')"
                                                                                title="Hapus"><i
                                                                                    class="ri-delete-bin-line"></i></button>
                                                                        </div>
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        @endforeach
                                                    @endforeach
                                                @endforeach


                                            </tbody>
                                        </table>
                                        <!-- end table -->
                                    </div>
                                    <!-- end table responsive -->
                                </div>
                            </div><!-- end card-body -->
                        </div><!-- end card -->
                    </div><!-- end col -->

                </div>
            </div> <!-- end col-->
        </div> <!-- end row-->

        {{-- @dump($kd_komponen, $nama_komponen, $bobot_komponen, $tahun_id) --}}

    </div>
    <!-- container-fluid -->

    <!-- Modal Tambah Komponen -->
    <div wire:ignore.self class="modal fade" id="addKomponenModal" tabindex="-1"
        aria-labelledby="addKomponenModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary">
                    <h5 class="modal-title text-white" id="addKomponenModalLabel">Tambah Komponen</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form>
                        <div class="mb-3">
                            <label for="komponenKode" class="form-label">Kode Komponen <span
                                    class="text-danger">*</span></label>
                            <input wire:model="kd_komponen" type="text" class="form-control" id="komponenKode"
                                placeholder="Contoh: K1">
                        </div>
                        <div class="mb-3">
                            <label for="komponenNama" class="form-label">Nama Komponen <span
                                    class="text-danger">*</span></label>
                            <textarea wire:model="nama_komponen" class="form-control" id="komponenNama" rows="2"
                                placeholder="Masukkan nama komponen"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="komponenBobot" class="form-label">Bobot <span
                                    class="text-danger">*</span></label>
                            <input wire:model="bobot_komponen" type="number" step="0.01" class="form-control"
                                id="komponenBobot" placeholder="Contoh: 25.00">
                        </div>
                        <div class="mb-3">
                            <label for="komponenEvaluator" class="form-label">Evaluator/Verifikator</label>
                            <select wire:model="role_id" class="form-select" id="komponenEvaluator">
                                <option value="">Pilih Role</option>
                                @foreach ($this->roleoptions as $role)
                                    <option value="{{ $role->id }}">{{ $role->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="komponenTahun" class="form-label">Tahun <span
                                    class="text-danger">*</span></label>
                            <select wire:model="tahun_id" class="form-select" id="komponenTahun">
                                <option selected disabled>Pilih Tahun</option>
                                @foreach ($this->tahunoptions as $tahun)
                                    <option value="{{ $tahun->id }}">{{ $tahun->tahun }}</option>
                                @endforeach
                            </select>
                        </div>
                        @if ($errors->all())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>

                        @endif
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button wire:click="addKomponen" type="button" class="btn btn-primary"
                        data-bs-dismiss="modal">Simpan</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Tambah Sub Komponen -->
    <div wire:ignore.self class="modal fade" id="addSubKomponenModal" tabindex="-1"
        aria-labelledby="addSubKomponenModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-success">
                    <h5 class="modal-title text-white" id="addSubKomponenModalLabel">Tambah Sub Komponen</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form>
                        <div class="mb-3">
                            <label for="subKode" class="form-label">Kode Sub Komponen <span
                                    class="text-danger">*</span></label>
                            <input wire:model="kd_sub_komponen" type="text" class="form-control" id="subKode"
                                placeholder="Contoh: K1.1">
                        </div>
                        <div class="mb-3">
                            <label for="subNama" class="form-label">Nama Sub Komponen <span
                                    class="text-danger">*</span></label>
                            <textarea wire:model="nama_sub_komponen" class="form-control" id="subNama" rows="2"
                                placeholder="Masukkan nama sub komponen"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="subBobot" class="form-label">Bobot <span class="text-danger">*</span></label>
                            <input wire:model="bobot_sub_komponen" type="number" step="0.01"
                                class="form-control" id="subBobot" placeholder="Contoh: 10.00">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button x-on:click="$wire.set('komponen_id', komponenId).then(() => { $wire.addSubKomponen() })"
                        type="button" class="btn btn-success" data-bs-dismiss="modal">Simpan</button>
                </div>
                @if ($errors->all())
                    <div class="alert alert-danger m-3">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Modal Tambah Kriteria Komponen -->
    <div wire:ignore.self class="modal fade" id="addKriteriaModal" tabindex="-1"
        aria-labelledby="addKriteriaModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-info">
                    <h5 class="modal-title text-white" id="addKriteriaModalLabel">Tambah Kriteria Komponen</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form>
                        <div class="mb-3">
                            <label for="kriteriaKode" class="form-label">Kode Kriteria <span
                                    class="text-danger">*</span></label>
                            <input wire:model="kd_kriteria" type="text" class="form-control" id="kriteriaKode"
                                placeholder="Contoh: K1.1.1">
                        </div>
                        <div class="mb-3">
                            <label for="kriteriaNama" class="form-label">Nama Kriteria <span
                                    class="text-danger">*</span></label>
                            <textarea wire:model="nama_kriteria" class="form-control" id="kriteriaNama" rows="3"
                                placeholder="Masukkan nama kriteria"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="kriteriaJenisNilai" class="form-label">Jenis Nilai <span
                                    class="text-danger">*</span></label>
                            <select wire:model="jenis_nilai_id" class="form-select" id="kriteriaJenisNilai">
                                <option selected disabled>Pilih Jenis Nilai</option>
                                @foreach ($this->jenisnilaioptions() as $jenisnilai)
                                    <option value="{{ $jenisnilai->id }}">{{ $jenisnilai->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button
                        x-on:click="$wire.set('sub_komponen_id', subKomponenId).then(() => { $wire.addKriteriaKomponen() })"
                        type="button" class="btn btn-info" data-bs-dismiss="modal">Simpan</button>
                </div>
                @if ($errors->all())
                    <div class="alert alert-danger m-3">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Modal Tambah Bukti Dukung -->
    <div wire:ignore.self class="modal fade" id="addBuktiModal" tabindex="-1" aria-labelledby="addBuktiModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title text-white" id="addBuktiModalLabel">Tambah Bukti Dukung</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form>
                        <div class="mb-3">
                            <label for="buktiKode" class="form-label">Kode Bukti Dukung <span
                                    class="text-danger">*</span></label>
                            <input wire:model="kd_bukti" type="text" class="form-control" id="buktiKode"
                                placeholder="Contoh: BD1">
                        </div>
                        <div class="mb-3">
                            <label for="buktiNama" class="form-label">Nama Bukti Dukung <span
                                    class="text-danger">*</span></label>
                            <textarea wire:model="nama_bukti" class="form-control" id="buktiNama" rows="3"
                                placeholder="Masukkan nama bukti dukung"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="buktiKriteria" class="form-label">Kriteria Penilaian</label>
                            <textarea wire:model="kriteria_penilaian" class="form-control" id="buktiKriteria" rows="2"
                                placeholder="Masukkan kriteria penilaian"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button
                        x-on:click="$wire.set('kriteria_komponen_id', kriteriaKomponenId).then(() => { $wire.addBuktiDukung() })"
                        type="button" class="btn btn-warning" data-bs-dismiss="modal">Simpan</button>
                </div>
                @if ($errors->all())
                    <div class="alert alert-danger m-3">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Modal Edit Komponen -->
    <div wire:ignore.self class="modal fade" id="editKomponenModal" tabindex="-1"
        aria-labelledby="editKomponenModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary">
                    <h5 class="modal-title text-white" id="editKomponenModalLabel">Edit Komponen</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close" wire:click="resetFormKomponen"></button>
                </div>
                <div class="modal-body">
                    <form>
                        <div class="mb-3">
                            <label for="editKomponenKode" class="form-label">Kode Komponen <span
                                    class="text-danger">*</span></label>
                            <input wire:model="kd_komponen" type="text" class="form-control"
                                id="editKomponenKode" placeholder="Contoh: K1">
                        </div>
                        <div class="mb-3">
                            <label for="editKomponenNama" class="form-label">Nama Komponen <span
                                    class="text-danger">*</span></label>
                            <textarea wire:model="nama_komponen" class="form-control" id="editKomponenNama" rows="2"
                                placeholder="Masukkan nama komponen"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="editKomponenBobot" class="form-label">Bobot <span
                                    class="text-danger">*</span></label>
                            <input wire:model="bobot_komponen" type="number" step="0.01" class="form-control"
                                id="editKomponenBobot" placeholder="Contoh: 25.00">
                        </div>
                        <div class="mb-3">
                            <label for="editKomponenEvaluator" class="form-label">Evaluator/Verifikator</label>
                            <select wire:model="role_id" class="form-select" id="editKomponenEvaluator">
                                <option value="">Pilih Role</option>
                                @foreach ($this->roleoptions as $role)
                                    <option value="{{ $role->id }}">{{ $role->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                        @if ($errors->all())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal"
                        wire:click="resetFormKomponen">Batal</button>
                    <button wire:click="updateKomponen" type="button" class="btn btn-primary"
                        data-bs-dismiss="modal">Update</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Edit Sub Komponen -->
    <div wire:ignore.self class="modal fade" id="editSubKomponenModal" tabindex="-1"
        aria-labelledby="editSubKomponenModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-success">
                    <h5 class="modal-title text-white" id="editSubKomponenModalLabel">Edit Sub Komponen</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close" wire:click="resetFormSubKomponen"></button>
                </div>
                <div class="modal-body">
                    <form>
                        <div class="mb-3">
                            <label for="editSubKode" class="form-label">Kode Sub Komponen <span
                                    class="text-danger">*</span></label>
                            <input wire:model="kd_sub_komponen" type="text" class="form-control" id="editSubKode"
                                placeholder="Contoh: K1.1">
                        </div>
                        <div class="mb-3">
                            <label for="editSubNama" class="form-label">Nama Sub Komponen <span
                                    class="text-danger">*</span></label>
                            <textarea wire:model="nama_sub_komponen" class="form-control" id="editSubNama" rows="2"
                                placeholder="Masukkan nama sub komponen"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="editSubBobot" class="form-label">Bobot <span
                                    class="text-danger">*</span></label>
                            <input wire:model="bobot_sub_komponen" type="number" step="0.01"
                                class="form-control" id="editSubBobot" placeholder="Contoh: 10.00">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal"
                        wire:click="resetFormSubKomponen">Batal</button>
                    <button wire:click="updateSubKomponen" type="button" class="btn btn-success"
                        data-bs-dismiss="modal">Update</button>
                </div>
                @if ($errors->all())
                    <div class="alert alert-danger m-3">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Modal Edit Kriteria Komponen -->
    <div wire:ignore.self class="modal fade" id="editKriteriaModal" tabindex="-1"
        aria-labelledby="editKriteriaModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-info">
                    <h5 class="modal-title text-white" id="editKriteriaModalLabel">Edit Kriteria Komponen</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close" wire:click="resetFormKriteriaKomponen"></button>
                </div>
                <div class="modal-body">
                    <form>
                        <div class="mb-3">
                            <label for="editKriteriaKode" class="form-label">Kode Kriteria <span
                                    class="text-danger">*</span></label>
                            <input wire:model="kd_kriteria" type="text" class="form-control"
                                id="editKriteriaKode" placeholder="Contoh: K1.1.1">
                        </div>
                        <div class="mb-3">
                            <label for="editKriteriaNama" class="form-label">Nama Kriteria <span
                                    class="text-danger">*</span></label>
                            <textarea wire:model="nama_kriteria" class="form-control" id="editKriteriaNama" rows="3"
                                placeholder="Masukkan nama kriteria"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="editKriteriaJenisNilai" class="form-label">Jenis Nilai <span
                                    class="text-danger">*</span></label>
                            <select wire:model="jenis_nilai_id" class="form-select" id="editKriteriaJenisNilai">
                                <option selected disabled>Pilih Jenis Nilai</option>
                                @foreach ($this->jenisnilaioptions() as $jenisnilai)
                                    <option value="{{ $jenisnilai->id }}">{{ $jenisnilai->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal"
                        wire:click="resetFormKriteriaKomponen">Batal</button>
                    <button wire:click="updateKriteriaKomponen" type="button" class="btn btn-info"
                        data-bs-dismiss="modal">Update</button>
                </div>
                @if ($errors->all())
                    <div class="alert alert-danger m-3">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Modal Edit Bukti Dukung -->
    <div wire:ignore.self class="modal fade" id="editBuktiModal" tabindex="-1"
        aria-labelledby="editBuktiModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title text-white" id="editBuktiModalLabel">Edit Bukti Dukung</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close" wire:click="resetFormBuktiDukung"></button>
                </div>
                <div class="modal-body">
                    <form>
                        <div class="mb-3">
                            <label for="editBuktiNama" class="form-label">Nama Bukti Dukung <span
                                    class="text-danger">*</span></label>
                            <textarea wire:model="nama_bukti" class="form-control" id="editBuktiNama" rows="3"
                                placeholder="Masukkan nama bukti dukung"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal"
                        wire:click="resetFormBuktiDukung">Batal</button>
                    <button wire:click="updateBuktiDukung" type="button" class="btn btn-warning"
                        data-bs-dismiss="modal">Update</button>
                </div>
                @if ($errors->all())
                    <div class="alert alert-danger m-3">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
<!-- End Page-content -->



@push('scripts')
    <style>
        /* hide all child rows by default */
        tr[data-parent] {
            display: none;
        }

        .toggle-children {
            display: inline-block;
            width: 16px;
            text-align: center;
            margin-right: 5px;
            font-size: 16px;
        }

        .toggle-children:hover {
            opacity: 0.7;
        }

        /* Styling untuk Komponen - Level 1 */
        .row-komponen {
            background-color: #f8f9fa;
            border-left: 4px solid #405189;
        }

        .row-komponen td {
            font-weight: 600;
            font-size: 14px;
            padding-top: 12px !important;
            padding-bottom: 12px !important;
            color: #2c3e50;
        }

        .row-komponen:hover {
            background-color: #e9ecef;
        }

        /* Styling untuk Sub Komponen - Level 2 */
        .row-sub {
            background-color: #f0f9f4;
            border-left: 4px solid #0ab39c;
        }

        .row-sub td {
            font-weight: 500;
            font-size: 13.5px;
            padding-top: 10px !important;
            padding-bottom: 10px !important;
            color: #34495e;
        }

        .row-sub:hover {
            background-color: #d4f1e8;
        }

        /* Styling untuk Kriteria Komponen - Level 3 */
        .row-kriteria {
            background-color: #fef5e5;
            border-left: 4px solid #f1b44c;
        }

        .row-kriteria td {
            font-weight: 400;
            font-size: 13px;
            padding-top: 9px !important;
            padding-bottom: 9px !important;
            color: #495057;
        }

        .row-kriteria:hover {
            background-color: #fdeac7;
        }

        /* Styling untuk Bukti Dukung - Level 4 */
        .row-bukti {
            background-color: #fff5f5;
            border-left: 4px solid #f06548;
        }

        .row-bukti td {
            font-weight: 400;
            font-size: 12.5px;
            padding-top: 8px !important;
            padding-bottom: 8px !important;
            color: #6c757d;
            font-style: italic;
        }

        .row-bukti:hover {
            background-color: #ffe5e0;
        }

        /* Subtle animation for row transitions */
        tbody tr {
            transition: background-color 0.2s ease, transform 0.1s ease;
        }

        tbody tr:hover {
            transform: translateX(2px);
        }

        /* Better spacing for table */
        .table>tbody>tr>td {
            vertical-align: middle;
        }

        /* Kode column styling */
        .row-komponen td:first-child {
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
            letter-spacing: 0.5px;
        }

        .row-sub td:first-child,
        .row-kriteria td:first-child,
        .row-bukti td:first-child {
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
            letter-spacing: 0.3px;
            font-size: 12px;
        }
    </style>
@endpush
