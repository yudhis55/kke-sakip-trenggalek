<div class="page-content">
    <div class="container-fluid">

        <!-- start page title -->
        {{-- <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Kriteria Komponen
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
        </div> --}}
        <!-- end page title -->

        {{-- Breadcrumb Navigation --}}
        <div class="row">
            <div class="col-12">
                <div class="alert alert-primary alert-border-left" role="alert">
                    <i class="ri-building-line me-2 align-middle fs-16"></i>
                    @if (Auth::user()->opd)
                        <strong>OPD:</strong> {{ Auth::user()->opd->nama }}
                    @elseif(session('opd_session'))
                        <strong>OPD:</strong> {{ \App\Models\Opd::find(session('opd_session'))->nama }}
                    @endif
                    <i class="ri-arrow-right-s-line mx-2"></i>
                    <a href="{{ route('monitoring') }}" class="text-decoration-underline text-primary fw-semibold">
                        <strong>Komponen:</strong> {{ $this->subKomponen->komponen->nama }}
                    </a>
                    {{-- <i class="ri-arrow-right-s-line mx-2"></i>
                    <strong>Sub Komponen:</strong> {{ $this->subKomponen->nama }} --}}
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header align-items-center d-flex">
                        {{-- <h4 class="card-title mb-0 flex-grow-1">Sub Komponen: {{ $this->subKomponen->kode }} - {{ $this->subKomponen->nama }}
                            <span class="badge bg-primary ms-2">Bobot:
                                {{ number_format($this->bobotSubKomponen, 2) }}%</span>
                        </h4> --}}
                        <p class="mb-0 text-dark fw-semibold flex-grow-1">Sub Komponen: {{ $this->subKomponen->kode }} -
                            {{ $this->subKomponen->nama }}
                            <span class="badge text-bg-primary ms-2">Bobot:
                                {{ number_format($this->bobotSubKomponen, 2) }}%</span>
                        </p>
                        <a href="{{ route('monitoring') }}" type="button"
                            class="btn btn-sm btn-soft-primary btn-label waves-effect waves-light"><i
                                class=" ri-arrow-go-back-line label-icon align-middle fs-16 me-2"></i> Kembali</a>
                    </div>
                    <div class="card-body">
                        <div class="live-preview">
                            <div class="table-responsive">
                                <table class="table align-middle mb-0" style="table-layout: fixed;">
                                    <thead class="table-light">
                                        <tr>
                                            {{-- <th></th> --}}
                                            <th scope="col" style="width: 3%;">No</th>
                                            <th scope="col" style="width: 7%;">Kode</th>
                                            <th scope="col" style="width: 25%;">Kriteria Komponen</th>
                                            <th scope="col" style="width: 6%;">Bobot</th>
                                            <th scope="col" style="width: 8%;">Bukti Dukung</th>
                                            <th scope="col" style="width: 10%;">Penilaian <br>Mandiri</th>
                                            <th scope="col" style="width: 6%;">Verval</th>
                                            <th scope="col" style="width: 9%;">Evaluator</th>
                                            <th scope="col" style="width: 10%;">Penjaminan <br>Kualitas</th>
                                            <th scope="col" style="width: 6%;">Jumlah</th>
                                            <th scope="col" style="width: 6%;">Skor</th>
                                            @if ($this->penilaianDiKriteria && (Auth::user()->role->jenis == 'admin' || Auth::user()->role->jenis == 'opd'))
                                                <th scope="col" style="width: 5%;">Lacak</th>
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $totalJumlah = 0;
                                        @endphp
                                        @foreach ($this->kriteriaKomponenList as $index => $kriteria_komponen)
                                            <tr>
                                                <th scope="row"><a class="fw-medium">{{ $index + 1 }}</a></th>
                                                <td>{{ $kriteria_komponen->kode }}</td>
                                                <td>
                                                    <a
                                                        href="{{ route('monitoring.kriteria-komponen.bukti-dukung', ['sub_komponen_id' => $sub_komponen_id, 'kriteria_komponen_id' => $kriteria_komponen->id]) }}">
                                                        {{ $kriteria_komponen->nama }}
                                                    </a>
                                                </td>
                                                <td>{{ number_format($kriteria_komponen->bobot_persen, 2) }}%</td>
                                                <td>
                                                    <span
                                                        class="{{ $kriteria_komponen->uploaded_file_bukti_dukung == $kriteria_komponen->total_file_bukti_dukung ? 'success' : 'warning' }}">
                                                        {{ $kriteria_komponen->uploaded_file_bukti_dukung }} /
                                                        {{ $kriteria_komponen->total_file_bukti_dukung }}
                                                    </span>
                                                </td>
                                                <td>
                                                    {{-- Penilaian Mandiri (OPD) --}}
                                                    @if ($this->penilaianDiKriteria)
                                                        @if ($kriteria_komponen->penilaian_opd && $kriteria_komponen->penilaian_opd->tingkatan_nilai)
                                                            @php
                                                                $skorPenilaianMandiri =
                                                                    $kriteria_komponen->penilaian_opd->tingkatan_nilai
                                                                        ->bobot * $kriteria_komponen->bobot_persen;
                                                            @endphp
                                                            {{-- <button class="btn btn-sm btn-soft-primary"
                                                                title="Bobot: {{ $kriteria_komponen->penilaian_opd->tingkatan_nilai->bobot }}">
                                                                <span
                                                                    class="fw-bold">{{ $kriteria_komponen->penilaian_opd->tingkatan_nilai->kode_nilai }}</span>
                                                            </button> --}}
                                                            <div class="mt-1">
                                                                <small class="text-muted"><span
                                                                        class="fw-semibold text-dark">{{ number_format($skorPenilaianMandiri, 2) }}%</span></small>
                                                            </div>
                                                        @else
                                                            <span class="text-muted">-</span>
                                                        @endif
                                                    @else
                                                        @php
                                                            $opdId = Auth::user()->opd_id ?? session('opd_session');
                                                            $nilaiPerRole = $kriteria_komponen->getNilaiPerRole($opdId);
                                                            $nilaiMap = [];
                                                            foreach ($nilaiPerRole as $item) {
                                                                $nilaiMap[$item['role_jenis']] = $item['nilai'];
                                                            }
                                                        @endphp
                                                        @if (isset($nilaiMap['opd']))
                                                            <span>{{ number_format($nilaiMap['opd'], 2) }}%</span>
                                                        @else
                                                            <span class="text-muted">-</span>
                                                        @endif
                                                    @endif
                                                </td>
                                                <td>
                                                    {{-- Verifikator --}}
                                                    @if ($this->penilaianDiKriteria)
                                                        @if ($kriteria_komponen->penilaian_verifikator)
                                                            @if ($kriteria_komponen->penilaian_verifikator->is_verified === true)
                                                                <button class="btn btn-sm btn-soft-success btn-icon"
                                                                    title="Terverifikasi">
                                                                    <i class="ri-check-fill fw-bold"></i>
                                                                </button>
                                                            @elseif ($kriteria_komponen->penilaian_verifikator->is_verified === false)
                                                                <button class="btn btn-sm btn-soft-danger btn-icon"
                                                                    title="Ditolak">
                                                                    <i class="ri-close-fill fw-bold"></i>
                                                                </button>
                                                            @endif
                                                        @else
                                                            <span class="text-muted">-</span>
                                                        @endif
                                                    @else
                                                        @php
                                                            $opdId = Auth::user()->opd_id ?? session('opd_session');
                                                            $nilaiPerRole = $kriteria_komponen->getNilaiPerRole($opdId);
                                                            $nilaiMap = [];
                                                            foreach ($nilaiPerRole as $item) {
                                                                $nilaiMap[$item['role_jenis']] = $item['nilai'];
                                                            }
                                                        @endphp
                                                        @if (isset($nilaiMap['verifikator']))
                                                            <span
                                                                class="badge bg-info-subtle text-info">{{ number_format($nilaiMap['verifikator'], 2) }}%</span>
                                                        @else
                                                            <span class="text-muted">-</span>
                                                        @endif
                                                    @endif
                                                </td>
                                                <td>
                                                    {{-- Evaluator (Penjamin) --}}
                                                    @if ($this->penilaianDiKriteria)
                                                        @if ($kriteria_komponen->penilaian_penjamin)
                                                            @if ($kriteria_komponen->penilaian_penjamin->tingkatan_nilai)
                                                                <button class="btn btn-sm btn-soft-primary"
                                                                    title="Nilai: {{ $kriteria_komponen->penilaian_penjamin->tingkatan_nilai->bobot }}">
                                                                    <span
                                                                        class="fw-bold">{{ $kriteria_komponen->penilaian_penjamin->tingkatan_nilai->kode_nilai }}</span>
                                                                </button>
                                                            @endif
                                                            @if ($kriteria_komponen->penilaian_penjamin->is_verified === true)
                                                                <button
                                                                    class="btn btn-sm btn-soft-success btn-icon ms-1"
                                                                    title="Terverifikasi">
                                                                    <i class="ri-check-fill fw-bold"></i>
                                                                </button>
                                                            @elseif ($kriteria_komponen->penilaian_penjamin->is_verified === false)
                                                                <button
                                                                    class="btn btn-sm btn-soft-danger btn-icon ms-1"
                                                                    title="Ditolak">
                                                                    <i class="ri-close-fill fw-bold"></i>
                                                                </button>
                                                            @endif
                                                            @if (
                                                                !$kriteria_komponen->penilaian_penjamin->tingkatan_nilai &&
                                                                    !isset($kriteria_komponen->penilaian_penjamin->is_verified))
                                                                <span class="text-muted">-</span>
                                                            @endif
                                                        @else
                                                            <span class="text-muted">-</span>
                                                        @endif
                                                    @else
                                                        @php
                                                            $opdId = Auth::user()->opd_id ?? session('opd_session');
                                                            $nilaiPerRole = $kriteria_komponen->getNilaiPerRole($opdId);
                                                            $nilaiMap = [];
                                                            foreach ($nilaiPerRole as $item) {
                                                                $nilaiMap[$item['role_jenis']] = $item['nilai'];
                                                            }
                                                        @endphp
                                                        @if (isset($nilaiMap['penjamin']))
                                                            <span>{{ number_format($nilaiMap['penjamin'], 2) }}%</span>
                                                        @else
                                                            <span class="text-muted">-</span>
                                                        @endif
                                                    @endif
                                                </td>
                                                <td>
                                                    {{-- Penilai --}}
                                                    @if ($this->penilaianDiKriteria)
                                                        @if ($kriteria_komponen->penilaian_penilai && $kriteria_komponen->penilaian_penilai->tingkatan_nilai)
                                                            <button class="btn btn-sm btn-soft-primary"
                                                                title="Nilai: {{ $kriteria_komponen->penilaian_penilai->tingkatan_nilai->bobot }}">
                                                                <span
                                                                    class="fw-bold">{{ $kriteria_komponen->penilaian_penilai->tingkatan_nilai->kode_nilai }}</span>
                                                            </button>
                                                        @else
                                                            <span class="text-muted">-</span>
                                                        @endif
                                                    @else
                                                        @php
                                                            $opdId = Auth::user()->opd_id ?? session('opd_session');
                                                            $nilaiPerRole = $kriteria_komponen->getNilaiPerRole($opdId);
                                                            $nilaiMap = [];
                                                            foreach ($nilaiPerRole as $item) {
                                                                $nilaiMap[$item['role_jenis']] = $item['nilai'];
                                                            }
                                                        @endphp
                                                        @if (isset($nilaiMap['penilai']))
                                                            <span>{{ number_format($nilaiMap['penilai'], 2) }}%</span>
                                                        @else
                                                            <span class="text-muted">-</span>
                                                        @endif
                                                    @endif
                                                </td>
                                                <td>
                                                    @php
                                                        $opdId = Auth::user()->opd_id ?? session('opd_session');
                                                        $nilaiRataRata = $kriteria_komponen->getNilaiRataRata($opdId);

                                                        // Hitung jumlah (sum) dari 3 role
                                                        $nilaiPerRoleKriteria = $kriteria_komponen->getNilaiPerRole(
                                                            $opdId,
                                                        );
                                                        $nilaiMapKriteria = [];
                                                        foreach ($nilaiPerRoleKriteria as $item) {
                                                            $nilaiMapKriteria[$item['role_jenis']] = $item['nilai'];
                                                        }
                                                        $jumlah =
                                                            ($nilaiMapKriteria['opd'] ?? 0) +
                                                            ($nilaiMapKriteria['penilai'] ?? 0) +
                                                            ($nilaiMapKriteria['penjamin'] ?? 0);
                                                        $totalJumlah += $jumlah;
                                                    @endphp
                                                    @if ($jumlah > 0)
                                                        <span class="fw-semibold">{{ number_format($jumlah, 2) }}%</span>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($nilaiRataRata > 0)
                                                        <span class="fw-semibold">{{ number_format($nilaiRataRata, 2) }}%</span>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                @if ($this->penilaianDiKriteria && (Auth::user()->role->jenis == 'admin' || Auth::user()->role->jenis == 'opd'))
                                                    <td>
                                                        <button type="button"
                                                            wire:click="showTracking({{ $kriteria_komponen->id }})"
                                                            class="btn btn-sm btn-primary btn-icon waves-effect waves-light"
                                                            data-bs-toggle="modal" data-bs-target="#trackingModal"
                                                            title="Lihat Tracking">
                                                            <i class="ri-eye-line"></i>
                                                        </button>
                                                    </td>
                                                @endif
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot class="table-light">
                                        <tr>
                                            <td colspan="5" class="text-center">
                                                <strong>Total Nilai Sub
                                                    Komponen:</strong>
                                            </td>
                                            @php
                                                $opdId = Auth::user()->opd_id ?? session('opd_session');
                                                $nilaiPerRole = $this->subKomponen->getNilaiPerRole($opdId);
                                                $nilaiRataRata = $this->subKomponen->getNilaiRataRata($opdId);

                                                // Mapping role jenis ke nilai
                                                $nilaiMap = [];
                                                foreach ($nilaiPerRole as $item) {
                                                    $nilaiMap[$item['role_jenis']] = $item['nilai'];
                                                }
                                            @endphp

                                            {{-- Kolom Penilaian Mandiri (OPD) --}}
                                            <td>
                                                @if (isset($nilaiMap['opd']))
                                                    <span>{{ number_format($nilaiMap['opd'], 2) }}%</span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>

                                            {{-- Kolom Verval (Verifikator) --}}
                                            <td>
                                                @if (isset($nilaiMap['verifikator']))
                                                    <span>{{ number_format($nilaiMap['verifikator'], 2) }}%</span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>

                                            {{-- Kolom Evaluator (Penilai) --}}
                                            <td>
                                                @if (isset($nilaiMap['penilai']))
                                                    <span>{{ number_format($nilaiMap['penilai'], 2) }}%</span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>

                                            {{-- Kolom Penjaminan Kualitas (Penjamin) --}}
                                            <td>
                                                @if (isset($nilaiMap['penjamin']))
                                                    <span>{{ number_format($nilaiMap['penjamin'], 2) }}%</span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>

                                            {{-- Kolom Jumlah (total sum) --}}
                                            <td>
                                                <span class="fw-semibold">{{ number_format($totalJumlah, 2) }}%</span>
                                            </td>

                                            {{-- Kolom Skor (dipindah ke sini) --}}
                                            <td>
                                                @php
                                                    $opdId = Auth::user()->opd_id ?? session('opd_session');
                                                    $nilaiRataRata = $this->subKomponen->getNilaiRataRata($opdId);
                                                @endphp
                                                <span class="fw-semibold">{{ number_format($nilaiRataRata, 2) }}%</span>
                                            </td>

                                            @if ($this->penilaianDiKriteria && (Auth::user()->role->jenis == 'admin' || Auth::user()->role->jenis == 'opd'))
                                                <td></td>
                                            @endif
                                        </tr>
                                    </tfoot>
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

    <!-- Tracking Modal -->
    <div wire:ignore.self id="trackingModal" class="modal fade" tabindex="-1" aria-labelledby="myModalLabel"
        aria-hidden="true" style="display: none;">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="myModalLabel">
                        Tracking Status Evaluasi
                        @if ($this->getSelectedKriteriaName())
                            <br>
                            <small class="text-muted">{{ $this->getSelectedKriteriaName() }}</small>
                        @endif
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <div class="modal-body">
                    <div class="px-4 py-3">
                        <style>
                            .tracking-timeline {
                                position: relative;
                                padding-left: 0;
                                list-style: none;
                            }

                            .tracking-item {
                                position: relative;
                                padding-bottom: 2.5rem;
                                padding-left: 4rem;
                            }

                            .tracking-item:last-child {
                                padding-bottom: 0;
                            }

                            .tracking-item::before {
                                content: '';
                                position: absolute;
                                left: 1.125rem;
                                top: 2.5rem;
                                bottom: -0.5rem;
                                width: 2px;
                                background: #e9ecef;
                            }

                            .tracking-item:last-child::before {
                                display: none;
                            }

                            .tracking-icon {
                                position: absolute;
                                left: 0;
                                top: 0;
                                width: 2.5rem;
                                height: 2.5rem;
                                border-radius: 50%;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                font-size: 1.2rem;
                                font-weight: 600;
                                z-index: 1;
                            }

                            .tracking-icon.success {
                                background: #198754;
                                color: white;
                            }

                            .tracking-icon.danger {
                                background: #dc3545;
                                color: white;
                            }

                            .tracking-icon.warning {
                                background: #ffc107;
                                color: white;
                            }

                            .tracking-icon.null {
                                background: #6c757d;
                                color: white;
                            }

                            .tracking-date {
                                color: #ff9800;
                                font-size: 0.875rem;
                                font-weight: 500;
                                margin-bottom: 0.5rem;
                            }

                            .tracking-status {
                                font-size: 1.125rem;
                                font-weight: 600;
                                margin-bottom: 0.5rem;
                            }

                            .tracking-desc {
                                color: #6c757d;
                                margin-bottom: 0.25rem;
                            }
                        </style>

                        <ul class="tracking-timeline">
                            @php
                                $trackingData = $this->getTrackingData();
                            @endphp

                            @if (empty($trackingData))
                                <div class="alert alert-info text-center">
                                    <i class="ri-information-line fs-3"></i>
                                    <p class="mb-0 mt-2">Pilih OPD/kriteria komponen untuk melihat tracking evaluasi.
                                    </p>
                                </div>
                            @else
                                @foreach ($trackingData as $index => $item)
                                    <li class="tracking-item">
                                        <div class="tracking-icon {{ $item['status'] }}">
                                            <i class="{{ $item['icon'] }}"></i>
                                        </div>
                                        @if ($item['date'])
                                            <div class="tracking-date">
                                                <i class="ri-calendar-line me-1"></i>{{ $item['date'] }}
                                            </div>
                                        @endif
                                        <div class="tracking-status">{{ $item['title'] }}</div>

                                        @if ($item['nilai'])
                                            <div class="tracking-desc">
                                                <strong>Nilai:</strong> {{ $item['nilai'] }}
                                            </div>
                                        @endif

                                        @if ($item['keterangan'])
                                            <div class="tracking-desc">
                                                <strong>Keterangan:</strong> {{ $item['keterangan'] }}
                                            </div>
                                        @endif

                                        @if (!$item['date'] && $item['status'] == 'null')
                                            <div class="tracking-desc text-muted fst-italic">
                                                Belum ada data
                                            </div>
                                        @endif
                                    </li>
                                @endforeach
                            @endif
                        </ul>
                    </div>
                </div>
                {{-- <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary ">Save Changes</button>
            </div> --}}

            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </div><!-- /.modal -->

</div>
<!-- End Page-content -->
