<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0 font-size-18">Sinkronisasi Dokumen E-SAKIP</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="javascript: void(0);">Dashboard</a></li>
                            <li class="breadcrumb-item active">Sinkron Data</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                {{-- Filter Section --}}
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="mdi mdi-filter-variant me-2"></i>
                            Filter Dokumen
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            {{-- Tahun (Required) --}}
                            <div class="col-md-4">
                                <label class="form-label">
                                    Tahun <span class="text-danger">*</span>
                                </label>
                                <select wire:model.live="selected_tahun" class="form-select" required>
                                    <option value="">-- Pilih Tahun --</option>
                                    @foreach ($tahunList as $tahun)
                                        <option value="{{ $tahun->id }}">{{ $tahun->tahun }}</option>
                                    @endforeach
                                </select>
                                @error('selected_tahun')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- OPD (Optional) --}}
                            <div class="col-md-4">
                                <label class="form-label">OPD (Opsional)</label>
                                <select wire:model.live="selected_opd" class="form-select">
                                    <option value="">Semua OPD</option>
                                    @foreach ($opdList as $opd)
                                        <option value="{{ $opd->id }}">{{ $opd->nama }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Kosongkan untuk sinkron semua OPD</small>
                            </div>

                            {{-- Jenis Dokumen (Optional) --}}
                            <div class="col-md-4">
                                <label class="form-label">Jenis Dokumen (Opsional)</label>
                                <select wire:model.live="selected_document_type" class="form-select">
                                    <option value="">Semua Dokumen</option>
                                    @foreach ($documentTypes as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Kosongkan untuk sinkron semua dokumen</small>
                            </div>

                            {{-- Sync Mode --}}
                            <div class="col-md-12">
                                <label class="form-label">Mode Sinkronisasi</label>
                                <div class="row g-2">
                                    <div class="col-md-4">
                                        <div class="form-check card mb-0">
                                            <div class="card-body p-3">
                                                <input type="radio" wire:model="sync_mode" value="merge"
                                                    class="form-check-input" id="modeMerge">
                                                <label class="form-check-label w-100" for="modeMerge">
                                                    <i class="mdi mdi-plus-circle text-primary me-2"></i>
                                                    <strong>Gabung</strong>
                                                    <span class="d-block text-muted small">Tambahkan file baru tanpa
                                                        menghapus file lama</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check card mb-0">
                                            <div class="card-body p-3">
                                                <input type="radio" wire:model="sync_mode" value="replace"
                                                    class="form-check-input" id="modeReplace">
                                                <label class="form-check-label w-100" for="modeReplace">
                                                    <i class="mdi mdi-refresh text-warning me-2"></i>
                                                    <strong>Ganti</strong>
                                                    <span class="d-block text-muted small">Ganti semua file dengan file
                                                        dari esakip</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check card mb-0">
                                            <div class="card-body p-3">
                                                <input type="radio" wire:model="sync_mode" value="skip"
                                                    class="form-check-input" id="modeSkip">
                                                <label class="form-check-label w-100" for="modeSkip">
                                                    <i class="mdi mdi-skip-forward text-info me-2"></i>
                                                    <strong>Lewati</strong>
                                                    <span class="d-block text-muted small">Hanya sinkron yang belum ada
                                                        dokumen</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3 d-flex gap-2">
                            <button wire:click="previewSync" class="btn btn-primary" wire:loading.attr="disabled"
                                @if (!$selected_tahun || $syncing) disabled @endif>
                                <span wire:loading.remove wire:target="previewSync">
                                    <i class="mdi mdi-magnify me-2"></i>
                                    Preview Sinkronisasi
                                </span>
                                <span wire:loading wire:target="previewSync">
                                    <span class="spinner-border spinner-border-sm me-2" role="status"
                                        aria-hidden="true"></span>
                                    Memuat Preview...
                                </span>
                            </button>
                            <button wire:click="resetForm" class="btn btn-light" wire:loading.attr="disabled"
                                @if ($syncing) disabled @endif>
                                <i class="mdi mdi-refresh me-2"></i>
                                Reset
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Preview Results --}}
                @if ($previewData && !$syncing)
                    <div class="card">
                        <div class="card-header bg-info">
                            <h5 class="card-title text-white mb-0">
                                <i class="mdi mdi-eye me-2"></i>
                                Preview Hasil Sinkronisasi
                            </h5>
                        </div>
                        <div class="card-body">
                            {{-- Error messages --}}
                            @if (isset($previewData['errors']) && !empty($previewData['errors']))
                                <div class="alert alert-warning">
                                    <strong>Peringatan:</strong>
                                    <ul class="mb-0 mt-2">
                                        @foreach ($previewData['errors'] as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <div class="row g-3 mb-3">
                                <div class="col-md-3">
                                    <div class="text-center p-3 bg-light rounded">
                                        <div class="h2 mb-0">{{ $previewData['document_count'] }}</div>
                                        <div class="text-muted small">Dokumen Ditemukan</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center p-3 bg-light rounded">
                                        <div class="h2 mb-0">{{ $previewData['opd_count'] }}</div>
                                        <div class="text-muted small">OPD</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center p-3 bg-light rounded">
                                        <div class="h2 mb-0">{{ $previewData['bukti_dukung_count'] }}</div>
                                        <div class="text-muted small">Bukti Dukung Terisi</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center p-3 bg-success text-white rounded">
                                        <div class="h2 mb-0">{{ $previewData['auto_verified_count'] }}</div>
                                        <div class="small">Auto-Verified</div>
                                    </div>
                                </div>
                            </div>

                            {{-- Preview Table --}}
                            <div class="table-responsive">
                                <table class="table table-sm table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Jenis Dokumen</th>
                                            <th>Nama Dokumen</th>
                                            <th>OPD</th>
                                            <th class="text-center">Bukti Dukung</th>
                                            <th class="text-center">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($previewData['documents'] as $doc)
                                            <tr>
                                                <td>
                                                    <span class="badge bg-primary">{{ $doc['type'] }}</span>
                                                </td>
                                                <td>{{ Str::limit($doc['name'], 50) }}</td>
                                                <td>{{ Str::limit($doc['opd'], 30) }}</td>
                                                <td class="text-center">
                                                    <span class="badge bg-info">{{ $doc['bukti_dukung_count'] }}
                                                        item</span>
                                                </td>
                                                <td class="text-center">
                                                    @if ($doc['auto_verify'])
                                                        <span class="badge bg-success">
                                                            <i class="mdi mdi-check-circle me-1"></i>
                                                            Auto Verify
                                                        </span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center text-muted">Tidak ada data</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <div class="d-flex gap-2 mt-3">
                                <button wire:click="$set('previewData', null)" class="btn btn-secondary"
                                    wire:loading.attr="disabled">
                                    <i class="mdi mdi-close-circle me-2"></i>
                                    Batal
                                </button>
                                <button wire:click="processSync" class="btn btn-success" wire:loading.attr="disabled"
                                    wire:loading.class="disabled">
                                    <span wire:loading.remove wire:target="processSync">
                                        <i class="mdi mdi-cloud-download me-2"></i>
                                        Proses Sinkronisasi
                                    </span>
                                    <span wire:loading wire:target="processSync">
                                        <span class="spinner-border spinner-border-sm me-2" role="status"
                                            aria-hidden="true"></span>
                                        Memproses...
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Progress Bar --}}
                @if ($syncing)
                    <div class="card">
                        <div class="card-body">
                            <h5 class="mb-3">
                                <i class="mdi mdi-timer-sand me-2"></i>
                                Sedang Sinkronisasi...
                            </h5>
                            <div class="progress mb-2" style="height: 30px;">
                                <div class="progress-bar progress-bar-striped progress-bar-animated"
                                    role="progressbar" style="width: {{ $syncProgress }}%"
                                    aria-valuenow="{{ $syncProgress }}" aria-valuemin="0" aria-valuemax="100">
                                    {{ $syncProgress }}%
                                </div>
                            </div>
                            <div class="text-muted">{{ $syncMessage }}</div>
                        </div>
                    </div>
                @endif

                {{-- Sync Results --}}
                @if ($syncResults && !$syncing)
                    <div class="card">
                        <div class="card-header bg-success">
                            <h5 class="card-title text-white mb-0">
                                <i class="mdi mdi-check-circle me-2"></i>
                                Hasil Sinkronisasi
                            </h5>
                        </div>
                        <div class="card-body">
                            @if (isset($syncResults['error']))
                                <div class="alert alert-danger">
                                    <strong>Error:</strong> {{ $syncResults['error'] }}
                                </div>
                            @else
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <div class="p-3 bg-success text-white rounded text-center">
                                            <div class="h3 mb-0">{{ $syncResults['success_count'] }}</div>
                                            <div class="small">Berhasil</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="p-3 bg-warning text-dark rounded text-center">
                                            <div class="h3 mb-0">{{ $syncResults['no_document_count'] }}</div>
                                            <div class="small">Tidak Ditemukan</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="p-3 bg-danger text-white rounded text-center">
                                            <div class="h3 mb-0">{{ $syncResults['failed_count'] }}</div>
                                            <div class="small">Gagal</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="p-3 bg-info text-white rounded text-center">
                                            <div class="h3 mb-0">{{ $syncResults['skipped_count'] }}</div>
                                            <div class="small">Dilewati</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-3">
                                    <strong>Total Penilaian Terisi:</strong> {{ $syncResults['total_penilaian'] }}<br>
                                    <strong>Auto-Verified:</strong> {{ $syncResults['auto_verified'] }}
                                </div>
                            @endif

                            <button wire:click="resetForm" class="btn btn-primary mt-3">
                                <i class="mdi mdi-plus-circle me-2"></i>
                                Sinkronisasi Lagi
                            </button>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Sidebar: Riwayat --}}
            <div class="col-lg-4">
                {{-- Info Box --}}
                <div class="card">
                    <div class="card-body">
                        <h5 class="mb-3">
                            <i class="mdi mdi-information me-2"></i>
                            Panduan
                        </h5>
                        <ul class="mb-0 small">
                            <li><strong>Gabung:</strong> File dari esakip ditambahkan ke file yang sudah ada</li>
                            <li><strong>Ganti:</strong> File lama akan diganti dengan file dari esakip</li>
                            <li><strong>Lewati:</strong> Jika sudah ada upload manual, tidak akan di-sync</li>
                            <li><strong>Auto-Verify:</strong> Bukti dukung yang ditandai akan otomatis terverifikasi
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="mdi mdi-history me-2"></i>
                            Riwayat Sinkronisasi
                        </h5>
                        <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal"
                            data-bs-target="#clearRiwayatModal" title="Hapus Semua Riwayat">
                            <i class="mdi mdi-delete"></i>
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            @forelse($this->riwayat as $item)
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <div class="fw-bold">{{ $item->opd->nama }}</div>
                                            <div class="small text-muted">
                                                <span
                                                    class="badge bg-primary me-1">{{ strtoupper($item->document_type) }}</span>
                                                Tahun {{ $item->tahun_value }}
                                            </div>
                                            @if ($item->document_name)
                                                <div class="small">{{ Str::limit($item->document_name, 40) }}</div>
                                            @endif
                                            <div class="small text-muted">
                                                {{ $item->affected_count }} penilaian
                                                @if ($item->auto_verified_count > 0)
                                                    <span class="text-success">({{ $item->auto_verified_count }}
                                                        verified)</span>
                                                @endif
                                            </div>
                                            <div class="small text-muted">
                                                <i class="mdi mdi-clock-outline me-1"></i>
                                                {{ $item->synced_at?->diffForHumans() }}
                                            </div>
                                        </div>
                                        <div>
                                            @if ($item->status === 'success')
                                                <span class="badge bg-success">Sukses</span>
                                            @elseif($item->status === 'failed')
                                                <span class="badge bg-danger">Gagal</span>
                                            @elseif($item->status === 'partial')
                                                <span class="badge bg-warning">Partial</span>
                                            @elseif($item->status === 'no_document')
                                                <span class="badge bg-secondary">Tidak Ada</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="list-group-item text-center text-muted">
                                    Belum ada riwayat sinkronisasi
                                </div>
                            @endforelse
                        </div>
                    </div>
                    @if ($this->riwayat->hasPages())
                        <div class="card-footer">
                            {{ $this->riwayat->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Konfirmasi Hapus Riwayat --}}
    <div wire:ignore.self class="modal fade zoomIn" id="clearRiwayatModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mt-2 text-center">
                        <lord-icon src="https://cdn.lordicon.com/gsqxdxog.json" trigger="loop"
                            colors="primary:#f7b84b,secondary:#f06548" style="width:100px;height:100px"></lord-icon>
                        <div class="mt-4 pt-2 fs-15 mx-4 mx-sm-5">
                            <h4>Hapus Riwayat Sinkronisasi</h4>
                            <p class="text-muted mx-4 mb-0">Apakah Anda yakin ingin menghapus <strong>semua riwayat
                                    sinkronisasi</strong>? Tindakan ini tidak dapat dibatalkan.</p>
                        </div>
                    </div>
                    <div class="d-flex gap-2 justify-content-center mt-4 mb-2">
                        <button type="button" class="btn w-sm btn-light" data-bs-dismiss="modal">Batal</button>
                        <button wire:click="clearRiwayat" type="button" class="btn w-sm btn-danger"
                            data-bs-dismiss="modal">Hapus</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
