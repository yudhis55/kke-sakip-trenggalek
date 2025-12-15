<div class="page-content">
    <div class="container-fluid">

        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Bukti Dukung</h4>

                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            {{-- <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboards</a></li> --}}
                            {{-- <li class="breadcrumb-item"><a href="{{ route('lembar-kerja') }}">Lembar Kerja</a></li>
                            <li class="breadcrumb-item">Kriteria Komponen</li> --}}
                            <li class="breadcrumb-item">...</li>
                            <li class="breadcrumb-item">Kriteria Komponen</li>
                            <li class="breadcrumb-item active">Bukti Dukung</li>
                        </ol>
                    </div>

                </div>
            </div>
        </div>
        <!-- end page title -->

        {{-- <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <p class="mb-sm-0 text-primary fw-semibold">Kriteria Komponen: {{ $this->kriteriaKomponen->kode }} -
                            {{ $this->kriteriaKomponen->nama }}
                        </p>
                    </div>
                </div>
            </div>
        </div> --}}

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <label for="opdSelectedId" class="form-label">Pilih OPD</label>
                        <select wire:model.live="opd_selected_id" class="form-select" aria-label="Pilih OPD"
                            id="opdSelectedId">
                            <option value="">-- Pilih OPD yang akan diverifikasi atau dinilai --</option>
                            @foreach ($this->opdList as $opd)
                                <option value="{{ $opd->id }}">{{ $opd->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    {{-- @dump($opd_id) --}}
                    {{-- @dump($opd_selected_id) --}}
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <ul class="nav nav-tabs nav-justified nav-border-top nav-border-top-primary" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active py-3" data-bs-toggle="tab" href="#nav-border-justified-home"
                                    role="tab" aria-selected="false">
                                    <i class="ri-home-5-line align-middle me-1"></i> Bukti Dukung
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link py-3" data-bs-toggle="tab" href="#nav-border-justified-profile"
                                    role="tab" aria-selected="false">
                                    <i class="ri-user-line me-1 align-middle"></i>
                                    @if (Auth::user()->role->id != 7)
                                        Lembar Penilaian
                                    @else
                                        Penilaian Mandiri
                                    @endif
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    {{-- <div class="card-header align-items-center d-flex">
                        <h4 class="card-title mb-0 flex-grow-1">Daftar Bukti Dukung</h4>
                    </div> --}}
                    <div class="card-header align-items-center d-flex">
                        <p class="mb-sm-0 text-dark fw-semibold">Kriteria Komponen: {{ $this->kriteriaKomponen->kode }}
                            -
                            {{ $this->kriteriaKomponen->nama }}
                        </p>
                    </div>
                    <div class="card-body">
                        <div class="live-preview">
                            <div class="table-responsive">
                                <table class="table align-middle table-nowrap mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            {{-- <th></th> --}}
                                            <th scope="col">No</th>
                                            <th scope="col">Bukti Dukung</th>
                                            <th scope="col">Dokumen</th>
                                            <th scope="col">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($this->buktiDukungList as $index => $bukti_dukung)
                                            <tr>
                                                <th scope="row"><a class="fw-medium">{{ $index + 1 }}</a></th>
                                                <td> {{ $bukti_dukung->nama }} </td>
                                                <td>
                                                    @if ($bukti_dukung->file_bukti_dukung->isNotEmpty())
                                                        <button wire:click="setBuktiDukungId({{ $bukti_dukung->id }})" class="btn btn-sm btn-primary waves-effect waves-light"
                                                            data-bs-toggle="modal" data-bs-target="#viewBuktiDukung""><i
                                                                class="ri-file-line align-bottom me-1"></i>Lihat</button>
                                                    @else
                                                        <span class="fst-italic text-muted">Belum diunggah</span>
                                                    @endif

                                                    {{-- <button type="button"
                                                        class="btn btn-sm btn-primary btn-icon waves-effect waves-light" data-bs-toggle="modal" data-bs-target="#viewBuktiDukung"><i
                                                            class="ri-file-line"></i></button> --}}
                                                </td>
                                                <td><button class="btn btn-sm btn-light add-btn"><i
                                                            class="ri-upload-2-line align-bottom me-1"></i>Unggah</button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                <!-- end table -->
                            </div>
                            <!-- end table responsive -->
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header align-items-center d-flex">
                        <p class="mb-sm-0 text-dark fw-semibold">Bukti Dukung: Terdapat dokumen perencanaan kinerja
                            jangka menengah
                        </p>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            {{-- <div class="col-lg-3">

                            </div>
                            <div class="col-lg-9">

                            </div> --}}

                            <div class="col-md-2">
                                <div class="nav flex-column nav-pills text-center" id="v-pills-tab" role="tablist"
                                    aria-orientation="vertical">
                                    <a class="nav-link mb-2" id="v-pills-home-tab" data-bs-toggle="pill"
                                        href="#v-pills-home" role="tab" aria-controls="v-pills-home"
                                        aria-selected="false"><i class="ri-file-line me-1 align-middle"></i>Dokumen</a>
                                    <a class="nav-link mb-2" id="v-pills-profile-tab" data-bs-toggle="pill"
                                        href="#v-pills-profile" role="tab" aria-controls="v-pills-profile"
                                        aria-selected="false"><i class="ri-upload-line me-1 align-middle"></i>Unggah</a>
                                    <a class="nav-link mb-2 " id="button-penilaian-mandiri" data-bs-toggle="pill"
                                        href="#tab-penilaian-mandiri" role="tab"
                                        aria-controls="tab-penilaian-mandiri" aria-selected="false"><i
                                            class="ri-file-edit-line me-1 align-middle"></i>Penilaian</a>
                                    <a class="nav-link mb-2" id="button-verifikasi" data-bs-toggle="pill"
                                        href="#tab-verifikasi" role="tab" aria-controls="tab-verifikasi"
                                        aria-selected="true"><i
                                            class="ri-check-double-line me-1 align-middle"></i>Verifikasi</a>
                                </div>
                            </div><!-- end col -->
                            <div class="col-md-10">
                                <div class="tab-content mt-4 mt-md-0" id="v-pills-tabContent">
                                    <div class="tab-pane fade" id="v-pills-home" role="tabpanel"
                                        aria-labelledby="v-pills-home-tab">
                                        <div class="d-flex mb-2">
                                            <div class="flex-shrink-0">
                                                <img src="assets/images/small/img-4.jpg" alt=""
                                                    width="150" class="rounded">
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <embed src="{{ asset('assets/Form Surat Pernyataan.pdf') }}"
                                                    width="100%" height="500" alt="pdf" />

                                            </div>
                                        </div>
                                    </div>
                                    <div class="tab-pane fade" id="v-pills-profile" role="tabpanel"
                                        aria-labelledby="v-pills-profile-tab">
                                        <div class="mb-2">
                                            <div class="ms-3">
                                                <x-filepond::upload wire:model="file" multiple />
                                                <button class="btn btn-primary">Simpan</button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="tab-pane fade" id="tab-penilaian-mandiri" role="tabpanel"
                                        aria-labelledby="tombol-penilaian-mandiri">
                                        <div class="d-flex mb-2">
                                            <div class="col-xxl-4 col-lg-6">
                                                <div class="card card-body text-center">
                                                    <div class="avatar-sm mx-auto mb-3">
                                                        <div
                                                            class="avatar-title bg-soft-primary text-primary fs-17 rounded">
                                                            A
                                                        </div>
                                                    </div>
                                                    <h4 class="card-title">Nilai A</h4>
                                                    <p class="card-text text-muted">Bobot: 70%</p>
                                                    <a href="javascript:void(0);" class="btn btn-primary">Klaim</a>
                                                </div>
                                            </div><!-- end col -->
                                            <div class="col-xxl-4 col-lg-6">
                                                <div class="card card-body text-center">
                                                    <div class="avatar-sm mx-auto mb-3">
                                                        <div
                                                            class="avatar-title bg-soft-primary text-primary fs-17 rounded">
                                                            A
                                                        </div>
                                                    </div>
                                                    <h4 class="card-title">Nilai A</h4>
                                                    <p class="card-text text-muted">Bobot: 70%</p>
                                                    <a href="javascript:void(0);" class="btn btn-primary">Klaim</a>
                                                </div>
                                            </div><!-- end col -->
                                            <div class="col-xxl-4 col-lg-6">
                                                <div class="card card-body text-center">
                                                    <div class="avatar-sm mx-auto mb-3">
                                                        <div
                                                            class="avatar-title bg-soft-primary text-primary fs-17 rounded">
                                                            A
                                                        </div>
                                                    </div>
                                                    <h4 class="card-title">Nilai A</h4>
                                                    <p class="card-text text-muted">Bobot: 70%</p>
                                                    <a href="javascript:void(0);" class="btn btn-primary">Klaim</a>
                                                </div>
                                            </div><!-- end col -->
                                        </div>
                                    </div>
                                    <div class="tab-pane fade" id="tab-verifikasi" role="tabpanel"
                                        aria-labelledby="v-pills-profile-tab">
                                        <div class="mb-2">
                                            <div class="ms-3">
                                                <div class="live-preview">
                                                    <div class="table-responsive">
                                                        <table class="table align-middle table-nowrap mb-0">
                                                            <thead class="table-light">
                                                                <tr>
                                                                    {{-- <th></th> --}}
                                                                    <th scope="col">Status Verifikasi</th>
                                                                    <th scope="col">Keterangan</th>
                                                                    <th scope="col">Oleh</th>
                                                                    <th scope="col">Tanggal</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <tr>
                                                                    {{-- <th scope="row"></th> --}}
                                                                    <td>OK</td>
                                                                    <td>ABCD</td>
                                                                    <td>Bappeda</td>
                                                                    <td>12 Juni 2024</td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                        <!-- end table -->
                                                    </div>
                                                    <!-- end table responsive -->
                                                </div>
                                                <!-- Base Switchs -->
                                                <div class="form-check form-switch mt-3">
                                                    <input class="form-check-input" type="checkbox" role="switch"
                                                        id="flexSwitchCheckDefault">
                                                    <label class="form-check-label" for="flexSwitchCheckDefault">Ya,
                                                        sudah diperiksa dan sesuai</label>
                                                </div>
                                                <div class="form-check form-switch mt-3">
                                                    <input class="form-check-input" type="checkbox" role="switch"
                                                        id="flexSwitchCheckDefault">
                                                    <label class="form-check-label"
                                                        for="flexSwitchCheckDefault">Tidak, belum ada
                                                        kesesuaian</label>
                                                </div>
                                                <div class="mb-3 mt-3">
                                                    <label for="keterangan" class="form-label">Keterangan</label>
                                                    <textarea class="form-control" id="keterangan" rows="3"></textarea>
                                                </div>
                                                <button class="btn btn-primary">Simpan</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div><!--  end col -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div wire:ignore id="viewBuktiDukung" class="modal fade" tabindex="-1" role="dialog"
        aria-labelledby="viewBuktiDukungLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="myModalLabel">Modal Heading</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <div class="modal-body">
                    <embed src="{{ asset('assets/Form Surat Pernyataan.pdf') }}" width="100%" height="600"
                        alt="pdf" />
                </div>
                {{-- <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary ">Save Changes</button>
                </div> --}}
            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </div><!-- /.modal -->
</div>
<!-- container-fluid -->
