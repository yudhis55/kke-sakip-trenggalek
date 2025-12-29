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
                                                    <button tabindex="0" class="btn btn-sm btn-primary" role="button"
                                                        data-bs-toggle="popover" data-bs-trigger="focus"
                                                        data-bs-placement="top" title="Alasan Penolakan"
                                                        data-bs-content="{{ $penolakan->keterangan }}">
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
    </div>

    @push('scripts')
        <script>
            // Initialize Bootstrap Popovers
            document.addEventListener('livewire:navigated', function() {
                initPopovers();
            });

            document.addEventListener('DOMContentLoaded', function() {
                initPopovers();
            });

            function initPopovers() {
                var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
                popoverTriggerList.map(function(popoverTriggerEl) {
                    return new bootstrap.Popover(popoverTriggerEl, {
                        html: true,
                        sanitize: false
                    });
                });
            }
        </script>
    @endpush
