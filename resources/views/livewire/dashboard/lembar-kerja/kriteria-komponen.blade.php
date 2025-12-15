<div class="page-content">
    <div class="container-fluid">

        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Sub Komponen: {{ $this->subKomponen->kode }} - {{ $this->subKomponen->nama }}
                    </h4>
                    </h4>

                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboards</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('lembar-kerja') }}">Lembar Kerja</a></li>
                            <li class="breadcrumb-item active">Kriteria Komponen</li>
                        </ol>
                    </div>

                </div>
            </div>
        </div>
        <!-- end page title -->

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header align-items-center d-flex">
                        <h4 class="card-title mb-0 flex-grow-1">Daftar Kriteria Komponen</h4>
                    </div>
                    <div class="card-body">
                        <div class="live-preview">
                            <div class="table-responsive">
                                <table class="table align-middle table-nowrap mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            {{-- <th></th> --}}
                                            <th scope="col">No</th>
                                            <th scope="col">Kode</th>
                                            <th scope="col">Kriteria Komponen</th>
                                            <th scope="col">Bobot</th>
                                            <th scope="col">Bukti Dukung</th>
                                            <th scope="col">Persentase</th>
                                            <th scope="col">Verifikasi Bappeda</th>
                                            <th scope="col">Skor</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($this->kriteriaKomponenList as $index => $kriteria_komponen)
                                            <tr>
                                                <th scope="row"><a class="fw-medium">{{ $index + 1 }}</a></th>
                                                <td>{{ $kriteria_komponen->kode }}</td>
                                                <td>
                                                    <a href="{{ route('lembar-kerja.kriteria-komponen.bukti-dukung', ['sub_komponen_id' => $sub_komponen_id, 'kriteria_komponen_id' => $kriteria_komponen->id]) }}">
                                                        {{ $kriteria_komponen->nama }}
                                                    </a>
                                                </td>
                                                <td>{{ number_format($kriteria_komponen->bobot_persen, 2) }}%</td>
                                                <td>
                                                    <span
                                                        class="badge badge-soft-{{ $kriteria_komponen->uploaded_file_bukti_dukung == $kriteria_komponen->total_file_bukti_dukung ? 'success' : 'warning' }}">
                                                        {{ $kriteria_komponen->uploaded_file_bukti_dukung }} /
                                                        {{ $kriteria_komponen->total_file_bukti_dukung }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="flex-grow-1 me-2">
                                                            <div class="progress" style="height: 6px;">
                                                                <div class="progress-bar bg-{{ $kriteria_komponen->persentase_kelengkapan == 100 ? 'success' : ($kriteria_komponen->persentase_kelengkapan >= 50 ? 'warning' : 'danger') }}"
                                                                    role="progressbar"
                                                                    style="width: {{ $kriteria_komponen->persentase_kelengkapan }}%"
                                                                    aria-valuenow="{{ $kriteria_komponen->persentase_kelengkapan }}"
                                                                    aria-valuemin="0" aria-valuemax="100"></div>
                                                            </div>
                                                        </div>
                                                        <div class="flex-shrink-0">
                                                            <span
                                                                class="text-muted fs-12">{{ number_format($kriteria_komponen->persentase_kelengkapan, 1) }}%</span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge badge-soft-success">Acc</span>
                                                </td>
                                                <td>-</td>
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


    </div>
    <!-- container-fluid -->
</div>
<!-- End Page-content -->
