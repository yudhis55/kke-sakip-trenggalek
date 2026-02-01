<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0 font-size-18">Rekap Perbaikan</h4>

                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="javascript: void(0);">Dashboard</a></li>
                            <li class="breadcrumb-item active">Rekap Perbaikan</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header align-items-center d-flex">
                        <p class="mb-0 text-dark fw-semibold flex-grow-1">Dokumen yang Sudah Diperbaiki OPD</p>
                        @if ($this->badgeCount > 0)
                            <span class="badge bg-danger rounded-pill">{{ $this->badgeCount }} Perlu Review</span>
                        @endif
                    </div>
                    <div class="card-body">
                        <div class="table-responsive table-card">
                            <table class="table mb-0 align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col" style="width: 4%;">No</th>
                                        <th scope="col" style="width: 12%;">OPD</th>
                                        <th scope="col" style="width: 12%;">Komponen</th>
                                        <th scope="col" style="width: 12%;">Sub Komponen</th>
                                        <th scope="col" style="width: 16%;">Kriteria Komponen</th>
                                        <th scope="col" style="width: 14%;">Bukti Dukung</th>
                                        <th scope="col" style="width: 12%;">Status Perbaikan</th>
                                        <th scope="col" style="width: 10%;">Tgl Perbaikan</th>
                                        <th scope="col" style="width: 8%;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($this->rekapPerbaikan as $index => $perbaikan)
                                        <tr class="table-light">
                                            <td>{{ $index + 1 }}</td>
                                            <td>
                                                @if ($perbaikan->opd)
                                                    <span class="fw-semibold">{{ $perbaikan->opd->nama }}</span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if (
                                                    $perbaikan->kriteria_komponen &&
                                                        $perbaikan->kriteria_komponen->sub_komponen &&
                                                        $perbaikan->kriteria_komponen->sub_komponen->komponen)
                                                    {{ $perbaikan->kriteria_komponen->sub_komponen->komponen->nama }}
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($perbaikan->kriteria_komponen && $perbaikan->kriteria_komponen->sub_komponen)
                                                    {{ $perbaikan->kriteria_komponen->sub_komponen->nama }}
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($perbaikan->kriteria_komponen)
                                                    <span
                                                        class="text-primary">{{ $perbaikan->kriteria_komponen->kode }}</span>
                                                    -
                                                    {{ $perbaikan->kriteria_komponen->nama }}
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($perbaikan->bukti_dukung)
                                                    {{ $perbaikan->bukti_dukung->nama }}
                                                @else
                                                    <span class="text-muted fst-italic">Penilaian di kriteria</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($perbaikan->tanggal_perbaikan)
                                                    <span class="badge bg-success">
                                                        <i class="ri-check-line me-1"></i>Sudah Perbaikan
                                                    </span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($perbaikan->tanggal_perbaikan)
                                                    <small class="text-muted">
                                                        {{ $perbaikan->tanggal_perbaikan->format('d/m/Y') }}<br>
                                                        {{ $perbaikan->tanggal_perbaikan->format('H:i') }}
                                                    </small>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if (!empty(trim($perbaikan->keterangan)))
                                                    <button type="button" class="btn btn-sm btn-info me-1"
                                                        data-bs-toggle="modal" data-bs-target="#keteranganModal"
                                                        wire:click="showKeterangan({{ $perbaikan->id }})"
                                                        title="Lihat Alasan Penolakan">
                                                        <i class="ri-information-line"></i>
                                                    </button>
                                                @endif
                                                <button wire:click="redirectToBuktiDukung({{ $perbaikan->id }})"
                                                    type="button" class="btn btn-sm btn-primary"
                                                    title="Review di Lembar Kerja">
                                                    <i class="ri-external-link-line"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-center py-4">
                                                <div class="d-flex flex-column align-items-center">
                                                    <i class="ri-file-check-line display-4 text-muted mb-2"></i>
                                                    <p class="text-muted mb-0">Tidak ada dokumen yang perlu direview</p>
                                                    <small class="text-muted">Semua perbaikan sudah diverifikasi</small>
                                                </div>
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

        <!-- Modal Keterangan -->
        <div wire:ignore.self id="keteranganModal" class="modal fade" tabindex="-1"
            aria-labelledby="keteranganModalLabel" aria-hidden="true" style="display: none;">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="myModalLabel">Detail Penolakan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @if ($selectedPenolakan)
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <strong>OPD:</strong>
                                </div>
                                <div class="col-md-8">
                                    {{ $selectedPenolakan->opd->nama ?? '-' }}
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <strong>Kriteria:</strong>
                                </div>
                                <div class="col-md-8">
                                    {{ $selectedPenolakan->kriteria_komponen->kode ?? '' }} -
                                    {{ $selectedPenolakan->kriteria_komponen->nama ?? '-' }}
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <strong>Status Perbaikan:</strong>
                                </div>
                                <div class="col-md-8">
                                    @if ($selectedPenolakan->tanggal_perbaikan)
                                        <span class="badge bg-success">Sudah Upload Ulang</span>
                                        <br>
                                        <small class="text-muted">
                                            {{ $selectedPenolakan->tanggal_perbaikan->format('d/m/Y H:i') }}
                                        </small>
                                    @else
                                        <span class="badge bg-warning">Belum Upload</span>
                                    @endif
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-12">
                                    <strong>Alasan Penolakan:</strong>
                                    <p class="mt-2 p-3 bg-light rounded" style="word-wrap: break-word;">
                                        {{ $selectedKeterangan ?? 'Tidak ada keterangan' }}
                                    </p>
                                </div>
                            </div>
                        @else
                            <p class="text-muted">Tidak ada data</p>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
