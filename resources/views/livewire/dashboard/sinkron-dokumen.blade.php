<div>
    <div class="page-header">
        <h1 class="page-title">Sinkronisasi Dokumen esakip</h1>
        <div class="page-subtitle">Sinkronkan dokumen dari esakip ke sistem penilaian mandiri</div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            {{-- Filter Section --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="bi bi-funnel me-2"></i>
                        Filter Dokumen
                    </h3>
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
                                <option value="">-- Semua OPD --</option>
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
                                <option value="">-- Semua Dokumen --</option>
                                @foreach ($documentTypes as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Kosongkan untuk sinkron semua jenis</small>
                        </div>

                        {{-- Sync Mode --}}
                        <div class="col-md-12">
                            <label class="form-label">Mode Sinkronisasi</label>
                            <div class="form-selectgroup">
                                <label class="form-selectgroup-item">
                                    <input type="radio" wire:model="sync_mode" value="merge"
                                        class="form-selectgroup-input">
                                    <span class="form-selectgroup-label">
                                        <i class="bi bi-plus-circle me-2"></i>
                                        <strong>Gabung</strong>
                                        <span class="d-block text-muted small">Tambahkan file baru tanpa menghapus file
                                            lama</span>
                                    </span>
                                </label>
                                <label class="form-selectgroup-item">
                                    <input type="radio" wire:model="sync_mode" value="replace"
                                        class="form-selectgroup-input">
                                    <span class="form-selectgroup-label">
                                        <i class="bi bi-arrow-repeat me-2"></i>
                                        <strong>Ganti</strong>
                                        <span class="d-block text-muted small">Ganti semua file dengan file dari
                                            esakip</span>
                                    </span>
                                </label>
                                <label class="form-selectgroup-item">
                                    <input type="radio" wire:model="sync_mode" value="skip"
                                        class="form-selectgroup-input">
                                    <span class="form-selectgroup-label">
                                        <i class="bi bi-skip-forward me-2"></i>
                                        <strong>Lewati</strong>
                                        <span class="d-block text-muted small">Hanya sinkron yang belum ada
                                            dokumen</span>
                                    </span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <button wire:click="previewSync" class="btn btn-primary"
                            @if (!$selected_tahun || $syncing) disabled @endif>
                            <i class="bi bi-search me-2"></i>
                            Preview Sinkronisasi
                        </button>
                        <button wire:click="resetForm" class="btn btn-outline-secondary"
                            @if ($syncing) disabled @endif>
                            <i class="bi bi-arrow-clockwise me-2"></i>
                            Reset
                        </button>
                    </div>
                </div>
            </div>

            {{-- Preview Results --}}
            @if ($previewData && !$syncing)
                <div class="card mt-3">
                    <div class="card-header bg-info text-white">
                        <h3 class="card-title">
                            <i class="bi bi-eye me-2"></i>
                            Preview Hasil Sinkronisasi
                        </h3>
                    </div>
                    <div class="card-body">
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
                            <table class="table table-sm table-hover">
                                <thead>
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
                                                        <i class="bi bi-check-circle me-1"></i>
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
                            <button wire:click="$set('previewData', null)" class="btn btn-secondary">
                                <i class="bi bi-x-circle me-2"></i>
                                Batal
                            </button>
                            <button wire:click="processSync" class="btn btn-success">
                                <i class="bi bi-cloud-download me-2"></i>
                                Proses Sinkronisasi
                            </button>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Progress Bar --}}
            @if ($syncing)
                <div class="card mt-3">
                    <div class="card-body">
                        <h4 class="mb-3">
                            <i class="bi bi-hourglass-split me-2"></i>
                            Sedang Sinkronisasi...
                        </h4>
                        <div class="progress mb-2" style="height: 30px;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar"
                                style="width: {{ $syncProgress }}%" aria-valuenow="{{ $syncProgress }}"
                                aria-valuemin="0" aria-valuemax="100">
                                {{ $syncProgress }}%
                            </div>
                        </div>
                        <div class="text-muted">{{ $syncMessage }}</div>
                    </div>
                </div>
            @endif

            {{-- Sync Results --}}
            @if ($syncResults && !$syncing)
                <div class="card mt-3">
                    <div class="card-header bg-success text-white">
                        <h3 class="card-title">
                            <i class="bi bi-check-circle me-2"></i>
                            Hasil Sinkronisasi
                        </h3>
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
                            <i class="bi bi-plus-circle me-2"></i>
                            Sinkronisasi Lagi
                        </button>
                    </div>
                </div>
            @endif
        </div>

        {{-- Sidebar: Riwayat --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="bi bi-clock-history me-2"></i>
                        Riwayat Sinkronisasi
                    </h3>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush" style="max-height: 600px; overflow-y: auto;">
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
                                            <i class="bi bi-clock me-1"></i>
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
            </div>

            {{-- Info Box --}}
            <div class="card mt-3">
                <div class="card-body">
                    <h4 class="mb-3">
                        <i class="bi bi-info-circle me-2"></i>
                        Panduan
                    </h4>
                    <ul class="mb-0 small">
                        <li><strong>Gabung:</strong> File dari esakip ditambahkan ke file yang sudah ada</li>
                        <li><strong>Ganti:</strong> File lama akan diganti dengan file dari esakip</li>
                        <li><strong>Lewati:</strong> Jika sudah ada upload manual, tidak akan di-sync</li>
                        <li><strong>Auto-Verify:</strong> Bukti dukung yang ditandai akan otomatis terverifikasi</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
