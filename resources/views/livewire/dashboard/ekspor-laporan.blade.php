<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Ekspor Laporan</h4>

                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="javascript: void(0);">Dashboards</a></li>
                            <li class="breadcrumb-item active">Ekspor Laporan</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        {{-- Flash Messages --}}
        @if (session()->has('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="ri-error-warning-line me-2"></i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if (session()->has('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="ri-check-line me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        {{-- Form Pilih OPD & Ekspor --}}
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Ekspor Laporan Evaluasi</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-lg-6">
                                <label for="opd_selected_id" class="form-label">Pilih OPD <span
                                        class="text-danger">*</span></label>
                                <select wire:model.live="opd_selected_id" id="opd_selected_id" class="form-select">
                                    <option value="">-- Pilih OPD --</option>
                                    @foreach ($this->opdList as $opd)
                                        <option value="{{ $opd->id }}">{{ $opd->nama }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-6">
                                <label for="tanggal_ekspor" class="form-label">Tanggal Laporan</label>
                                <input type="text" wire:model="tanggal_ekspor" id="tanggal_ekspor"
                                    class="form-control" placeholder="Trenggalek, 3 Januari 2026">
                                {{-- <small class="text-muted">Format: Trenggalek, tanggal bulan tahun</small> --}}
                            </div>
                        </div>
                        <div class="row g-3 mt-2">
                            <div class="col-12">
                                <button wire:click="export" class="btn btn-primary w-100"
                                    @if (!$opd_selected_id) disabled @endif wire:loading.attr="disabled"
                                    wire:target="export">
                                    <span wire:loading.remove wire:target="export">
                                        <i class="ri-download-2-line me-1"></i>Ekspor Laporan
                                    </span>
                                    <span wire:loading wire:target="export">
                                        <span class="spinner-border spinner-border-sm me-1" role="status"
                                            aria-hidden="true"></span>
                                        Memproses...
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Preview Data (Testing) --}}
        @if ($this->previewData)
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-light">
                            <h5 class="card-title mb-0">
                                Preview Data - {{ $this->previewData['opd']->nama }}
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info mb-3">
                                <i class="ri-information-line me-2"></i>
                                <strong>Total Nilai OPD:</strong>
                                <span
                                    class="fs-5 fw-bold">{{ number_format($this->previewData['persentase_total'], 2, ',', '.') }}%</span>
                                <span class="badge bg-primary ms-2">Kategori: {{ $this->previewData['kategori_nilai'] }}</span>
                            </div>

                            <div x-data="{
                                openAccordion: sessionStorage.getItem('openAccordion') ? parseInt(sessionStorage.getItem('openAccordion')) : 0
                            }" x-init="$watch('openAccordion', value => sessionStorage.setItem('openAccordion', value))" class="accordion"
                                id="accordionKomponen" wire:ignore.self>
                                @foreach ($this->previewData['komponens'] as $index => $komponen)
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="heading{{ $index }}">
                                            <button
                                                @click="openAccordion = openAccordion === {{ $index }} ? null : {{ $index }}"
                                                class="accordion-button"
                                                :class="{ 'collapsed': openAccordion !== {{ $index }} }"
                                                type="button">
                                                <strong>{{ $index + 1 }}. {{ $komponen['nama'] }}</strong>
                                                <span class="badge bg-primary ms-2">
                                                    Nilai: {{ number_format($komponen['nilai'], 2, ',', '.') }} /
                                                    {{ number_format($komponen['bobot'], 2, ',', '.') }}
                                                </span>
                                            </button>
                                        </h2>
                                        <div id="collapse{{ $index }}" class="accordion-collapse collapse"
                                            :class="{ 'show': openAccordion === {{ $index }} }">
                                            <div class="accordion-body">
                                                <div class="table-responsive">
                                                    <table class="table table-sm table-bordered">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th width="5%">No</th>
                                                                <th>Sub Komponen</th>
                                                                <th width="15%" class="text-end">Nilai</th>
                                                                <th width="15%" class="text-end">Bobot</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach ($komponen['sub_komponens'] as $subIndex => $subKomponen)
                                                                <tr>
                                                                    <td class="text-center">{{ chr(97 + $subIndex) }}.
                                                                    </td>
                                                                    <td>{{ $subKomponen['nama'] }}</td>
                                                                    <td class="text-end">
                                                                        {{ number_format($subKomponen['nilai'], 2, ',', '.') }}
                                                                    </td>
                                                                    <td class="text-end">
                                                                        {{ number_format($subKomponen['bobot'], 2, ',', '.') }}
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                        <tfoot class="table-light">
                                                            <tr>
                                                                <th colspan="2" class="text-end">Total:</th>
                                                                <th class="text-end">
                                                                    {{ number_format($komponen['nilai'], 2, ',', '.') }}
                                                                </th>
                                                                <th class="text-end">
                                                                    {{ number_format($komponen['bobot'], 2, ',', '.') }}
                                                                </th>
                                                            </tr>
                                                        </tfoot>
                                                    </table>
                                                </div>

                                                {{-- Get Komponen ID terlebih dahulu --}}
                                                @php
                                                    $komponenId =
                                                        $this->previewData['komponens'][$index]['id'] ?? $index + 1;
                                                @endphp

                                                {{-- Form Deskripsi per Sub Komponen --}}
                                                <div class="mt-4">
                                                    <h6 class="text-info mb-3"><i
                                                            class="ri-file-text-line me-2"></i>Deskripsi Sub Komponen
                                                    </h6>
                                                    @foreach ($komponen['sub_komponens'] as $subIndex => $subKomponen)
                                                        @php
                                                            $subKomponenId = $subKomponen['id'] ?? $subIndex;
                                                        @endphp
                                                        <div class="mb-3">
                                                            <label class="form-label fw-semibold">
                                                                {{ chr(97 + $subIndex) }}. {{ $subKomponen['nama'] }}
                                                            </label>
                                                            <textarea wire:model="deskripsi.{{ $komponenId }}.{{ $subKomponenId }}" class="form-control" rows="3"
                                                                placeholder="Masukkan deskripsi untuk sub komponen ini..."></textarea>
                                                        </div>
                                                    @endforeach
                                                </div>

                                                {{-- Form Catatan & Rekomendasi --}}

                                                <div class="mt-4">
                                                    <h6 class="text-primary mb-3"><i
                                                            class="ri-file-list-3-line me-2"></i>Catatan</h6>
                                                    @if (isset($catatan[$komponenId]) && count($catatan[$komponenId]) > 0)
                                                        @foreach ($catatan[$komponenId] as $catatanIndex => $catatanText)
                                                            <div class="mb-2">
                                                                <div class="input-group">
                                                                    <span
                                                                        class="input-group-text">{{ $catatanIndex + 1 }})</span>
                                                                    <textarea wire:model="catatan.{{ $komponenId }}.{{ $catatanIndex }}" class="form-control" rows="2"
                                                                        placeholder="Masukkan catatan..."></textarea>
                                                                    <button
                                                                        @click="$wire.removeCatatan({{ $komponenId }}, {{ $catatanIndex }})"
                                                                        class="btn btn-danger" type="button">
                                                                        <i class="ri-delete-bin-line"></i>
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    @endif
                                                    <button @click="$wire.addCatatan({{ $komponenId }})"
                                                        class="btn btn-sm btn-outline-primary" type="button">
                                                        <i class="ri-add-line me-1"></i>Tambah Catatan
                                                    </button>
                                                </div>

                                                <div class="mt-4">
                                                    <h6 class="text-success mb-3"><i
                                                            class="ri-lightbulb-line me-2"></i>Rekomendasi</h6>
                                                    @if (isset($rekomendasi[$komponenId]) && count($rekomendasi[$komponenId]) > 0)
                                                        @foreach ($rekomendasi[$komponenId] as $rekomendasiIndex => $rekomendasiText)
                                                            <div class="mb-2">
                                                                <div class="input-group">
                                                                    <span
                                                                        class="input-group-text">{{ $rekomendasiIndex + 1 }})</span>
                                                                    <textarea wire:model="rekomendasi.{{ $komponenId }}.{{ $rekomendasiIndex }}" class="form-control" rows="2"
                                                                        placeholder="Masukkan rekomendasi..."></textarea>
                                                                    <button
                                                                        @click="$wire.removeRekomendasi({{ $komponenId }}, {{ $rekomendasiIndex }})"
                                                                        class="btn btn-danger" type="button">
                                                                        <i class="ri-delete-bin-line"></i>
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    @endif
                                                    <button @click="$wire.addRekomendasi({{ $komponenId }})"
                                                        class="btn btn-sm btn-outline-success" type="button">
                                                        <i class="ri-add-line me-1"></i>Tambah Rekomendasi
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

    </div>
</div>
