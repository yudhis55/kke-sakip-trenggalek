<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0 font-size-18">Rekap Verifikasi</h4>

                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="javascript: void(0);">Dashboard</a></li>
                            <li class="breadcrumb-item active">Rekap Verifikasi</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        {{-- Stats Cards --}}
        <div class="row">
            <div class="col-md-4">
                <div class="card mini-stats-wid">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="text-muted fw-medium mb-2">Total Dokumen</p>
                                <h4 class="mb-0">{{ $this->statsCount['total'] }}</h4>
                            </div>
                            <div class="avatar-sm rounded-circle bg-primary align-self-center mini-stat-icon">
                                <span class="avatar-title rounded-circle bg-primary">
                                    <i class="bx bx-file font-size-24"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card mini-stats-wid">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="text-muted fw-medium mb-2">Sudah Diverifikasi</p>
                                <h4 class="mb-0">{{ $this->statsCount['sudah'] }}</h4>
                            </div>
                            <div class="avatar-sm rounded-circle bg-success align-self-center mini-stat-icon">
                                <span class="avatar-title rounded-circle bg-success">
                                    <i class="bx bx-check-circle font-size-24"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card mini-stats-wid">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="text-muted fw-medium mb-2">Belum Diverifikasi</p>
                                <h4 class="mb-0">{{ $this->statsCount['belum'] }}</h4>
                            </div>
                            <div class="avatar-sm rounded-circle bg-warning align-self-center mini-stat-icon">
                                <span class="avatar-title rounded-circle bg-warning">
                                    <i class="bx bx-time-five font-size-24"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header align-items-center d-flex">
                        <p class="mb-0 text-dark fw-semibold flex-grow-1">Rekap Verifikasi Dokumen</p>
                    </div>
                    <div class="card-body">
                        {{-- Filter Row --}}
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="filter-opd" class="form-label">Filter OPD</label>
                                <select id="filter-opd" class="form-select" wire:model.live="selected_opd">
                                    <option value="">-- Semua OPD --</option>
                                    @foreach ($this->opdList as $opd)
                                        <option value="{{ $opd->id }}">{{ $opd->nama }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="filter-status" class="form-label">Filter Status</label>
                                <select id="filter-status" class="form-select" wire:model.live="filter_status">
                                    <option value="semua">Semua</option>
                                    <option value="sudah">Sudah Diverifikasi</option>
                                    <option value="belum">Belum Diverifikasi</option>
                                </select>
                            </div>
                        </div>

                        <div class="table-responsive table-card">
                            <table class="table mb-0 align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col" style="width: 4%;">No</th>
                                        <th scope="col" style="width: 12%;">OPD</th>
                                        <th scope="col" style="width: 11%;">Komponen</th>
                                        <th scope="col" style="width: 11%;">Sub Komponen</th>
                                        <th scope="col" style="width: 14%;">Kriteria</th>
                                        <th scope="col" style="width: 12%;">Bukti Dukung</th>
                                        <th scope="col" style="width: 11%;">Status</th>
                                        <th scope="col" style="width: 10%;">Tgl Verifikasi</th>
                                        <th scope="col" style="width: 15%;">Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($this->rekapVerifikasi as $index => $item)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>
                                                @if ($item->opd)
                                                    {{ $item->opd->nama }}
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if (
                                                    $item->bukti_dukung &&
                                                        $item->bukti_dukung->kriteria_komponen &&
                                                        $item->bukti_dukung->kriteria_komponen->sub_komponen &&
                                                        $item->bukti_dukung->kriteria_komponen->sub_komponen->komponen)
                                                    {{ $item->bukti_dukung->kriteria_komponen->sub_komponen->komponen->nama }}
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if (
                                                    $item->bukti_dukung &&
                                                        $item->bukti_dukung->kriteria_komponen &&
                                                        $item->bukti_dukung->kriteria_komponen->sub_komponen)
                                                    {{ $item->bukti_dukung->kriteria_komponen->sub_komponen->nama }}
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($item->bukti_dukung && $item->bukti_dukung->kriteria_komponen)
                                                    {{ $item->bukti_dukung->kriteria_komponen->kode }} -
                                                    {{ $item->bukti_dukung->kriteria_komponen->nama }}
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($item->bukti_dukung)
                                                    {{ $item->bukti_dukung->nama }}
                                                @else
                                                    <span class="text-muted fst-italic">Penilaian di kriteria</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($item->verifikasi_status === 'disetujui')
                                                    <span class="badge bg-success">Disetujui</span>
                                                @elseif ($item->verifikasi_status === 'ditolak')
                                                    <span class="badge bg-danger">Ditolak</span>
                                                @else
                                                    <span class="badge bg-warning text-dark">Belum Diverifikasi</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($item->verifikasi_tanggal)
                                                    {{ \Carbon\Carbon::parse($item->verifikasi_tanggal)->format('d-m-Y H:i') }}
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($item->verifikasi_keterangan)
                                                    <span class="text-muted">{{ $item->verifikasi_keterangan }}</span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-center text-muted py-4">
                                                Tidak ada data
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
    </div>
</div>
