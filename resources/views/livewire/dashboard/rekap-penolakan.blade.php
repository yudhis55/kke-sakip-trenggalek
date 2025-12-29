<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0 font-size-18">Rekap Penolakan</h4>

                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="javascript: void(0);">Dashboard</a></li>
                            <li class="breadcrumb-item active">Rekap Penolakan</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header align-items-center d-flex">
                        <p class="mb-0 text-dark fw-semibold flex-grow-1">Rekap Penolakan Dokumen</p>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive table-card">
                            <table class="table table-nowrap mb-0 align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col" style="width: 5%;">No</th>
                                        <th scope="col" style="width: 15%;">Komponen</th>
                                        <th scope="col" style="width: 15%;">Sub Komponen</th>
                                        <th scope="col" style="width: 20%;">Kriteria Komponen</th>
                                        <th scope="col" style="width: 20%;">Bukti Dukung</th>
                                        <th scope="col" style="width: 12%;">Ditolak Oleh</th>
                                        <th scope="col" style="width: 13%;">Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($this->rekapPenolakan as $index => $penolakan)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>
                                                @if (
                                                    $penolakan->kriteria_komponen &&
                                                        $penolakan->kriteria_komponen->sub_komponen &&
                                                        $penolakan->kriteria_komponen->sub_komponen->komponen)
                                                    {{ $penolakan->kriteria_komponen->sub_komponen->komponen->nama }}
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($penolakan->kriteria_komponen && $penolakan->kriteria_komponen->sub_komponen)
                                                    {{ $penolakan->kriteria_komponen->sub_komponen->nama }}
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($penolakan->kriteria_komponen)
                                                    {{ $penolakan->kriteria_komponen->kode }} -
                                                    {{ $penolakan->kriteria_komponen->nama }}
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($penolakan->bukti_dukung)
                                                    {{ $penolakan->bukti_dukung->nama }}
                                                @else
                                                    <span class="text-muted fst-italic">Penilaian di kriteria</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($penolakan->role)
                                                    @switch($penolakan->role->nama)
                                                        @case('verifikator_bappeda')
                                                            <span>Verifikator Bappeda</span>
                                                        @break

                                                        @case('verifikator_bag_organisasi')
                                                            <span>Verifikator Bag Organisasi</span>
                                                        @break

                                                        @case('verifikator_inspektorat')
                                                            <span>Verifikator Inspektorat</span>
                                                        @break

                                                        @case('penjamin')
                                                            <span">Evaluator</span>
                                                            @break

                                                            @default
                                                                <span">Verifikator </span>
                                                            @endswitch
                                                        @else
                                                            <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($penolakan->keterangan)
                                                    <button type="button" class="btn btn-sm btn-primary"
                                                        data-bs-toggle="modal" data-bs-target="#keteranganModal"
                                                        wire:click="showKeterangan({{ $penolakan->id }})">
                                                        <i class="ri-information-line"></i> Lihat
                                                    </button>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                        </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="text-center py-4">
                                                    <div class="d-flex flex-column align-items-center">
                                                        <i class="ri-file-list-3-line display-4 text-muted mb-2"></i>
                                                        <p class="text-muted mb-0">Tidak ada penolakan dokumen</p>
                                                        <small class="text-muted">Semua dokumen telah diverifikasi</small>
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
        </div>

        <!-- Default Modals -->
        <div wire:ignore.self id="keteranganModal" class="modal fade" tabindex="-1" aria-labelledby="keteranganModalLabel"
            aria-hidden="true" style="display: none;">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="myModalLabel">Alasan Penolakan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-0" style="word-wrap: break-word; white-space: pre-wrap;">
                            {{ $selectedKeterangan ?? 'Tidak ada keterangan' }}</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
                    </div>

                </div><!-- /.modal-content -->
            </div><!-- /.modal-dialog -->
        </div><!-- /.modal -->

        {{-- <!-- Keterangan Modal -->
        <div wire:ignore.self id="keteranganModal" class="modal fade" tabindex="-1" aria-labelledby="keteranganModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="keteranganModalLabel">Alasan Penolakan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-0">{{ $selectedKeterangan ?? 'Tidak ada keterangan' }}</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
                    </div>
                </div>
            </div>
        </div><!-- /.modal --> --}}
    </div>

    {{-- @push('scripts')
        <script>
            // Re-initialize feather icons after Livewire navigation
            document.addEventListener('livewire:navigated', function() {
                if (typeof feather !== 'undefined') {
                    feather.replace();
                }
            });

            document.addEventListener('DOMContentLoaded', function() {
                if (typeof feather !== 'undefined') {
                    feather.replace();
                }
            });
        </script>
    @endpush --}}
