<div class="page-content" x-data="{
    selectedKomponenId: {{ $this->komponenOptions->first()->id ?? 'null' }},
    isKomponenSelected(komponenId) {
        return this.selectedKomponenId === komponenId;
    },
    selectKomponen(komponenId) {
        this.selectedKomponenId = komponenId;
    }
}">
    <div class="container-fluid">

        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Lembar Kerja</h4>

                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="javascript: void(0);">Dashboards</a></li>
                            <li class="breadcrumb-item active">Lembar Kerja</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        <!-- end page title -->

        {{-- ========================================
             TAMPILAN UNTUK ROLE OPD (CARD BASED)
             ======================================== --}}
        @if (Auth::user()->role->jenis == 'opd')
            {{-- Nav Pills untuk Komponen --}}
            <div class="row">
                <div class="col-xxl-12">
                    <div class="card">
                        <div class="card-body">
                            <!-- Nav tabs -->
                            <ul class="nav nav-pills nav-justified gap-2" role="tablist">
                                @foreach ($this->komponenOptions as $komponen)
                                    <li wire:key="{{ $komponen->id }}" class="nav-item">
                                        <a class="nav-link waves-effect waves-light py-3"
                                            :class="{ 'active': isKomponenSelected({{ $komponen->id }}) }"
                                            @click.prevent="selectKomponen({{ $komponen->id }})" href="#"
                                            role="tab">
                                            {{ $komponen->nama }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div><!-- end card-body -->
                    </div>
                </div>
                <!--end col-->
            </div>

            {{-- Card Grid untuk Sub Komponen --}}
            <div class="row">
                @foreach ($this->subKomponenOptions as $sub_komponen)
                    <div wire:key="{{ $sub_komponen->id }}" class="col-xxl-4 col-lg-4"
                        x-show="isKomponenSelected({{ $sub_komponen->komponen_id }})" x-cloak>
                        <div class="card card-body text-center">
                            <div class="avatar-sm mx-auto mb-3">
                                <div class="avatar-title bg-soft-primary text-primary fs-17 rounded">
                                    <i class="ri-folder-2-line"></i>
                                </div>
                            </div>
                            <h4 class="card-title">{{ $sub_komponen->nama }}</h4>
                            <p class="card-text text-muted">{{ $sub_komponen->kode }}</p>
                            <p class="text-muted">
                                <span class="badge badge-soft-primary me-2">Bobot:
                                    {{ number_format($sub_komponen->bobot_persen, 2) }}%</span>
                                <span class="badge badge-outline-primary">Nilai:
                                    {{ number_format($sub_komponen->nilai_rata_rata ?? 0, 2) }}</span>
                            </p>
                            <p class="text-muted">Jumlah Kriteria: {{ $sub_komponen->kriteria_komponen->count() }}</p>
                            <a href="{{ route('lembar-kerja.kriteria-komponen', ['sub_komponen_id' => $sub_komponen->id]) }}"
                                class="btn btn-primary">Pilih</a>
                        </div>
                    </div><!-- end col -->
                @endforeach
            </div>
        @endif

        {{-- ========================================
             TAMPILAN UNTUK ADMIN & VERIFIKATOR (CARD BASED setelah pilih OPD)
             ======================================== --}}
        @if (in_array(Auth::user()->role->jenis, ['admin', 'verifikator']))

            {{-- TIER 1: TABEL OPD --}}
            @if (!$opd_session)
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header align-items-center d-flex">
                                {{-- <h4 class="card-title mb-0 flex-grow-1">Pilih OPD untuk Verifikasi</h4> --}}
                                <p class="mb-0 text-dark fw-semibold flex-grow-1">Pilih OPD untuk Verifikasi</p>
                                <div class="flex-shrink-0">
                                    <div class="search-box">
                                        <input type="text" wire:model.live="searchOpd" class="form-control search"
                                            placeholder="Cari OPD...">
                                        <i class="ri-search-line search-icon"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table align-middle table-nowrap mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th scope="col" style="width: 5%;">No</th>
                                                <th scope="col">OPD</th>
                                                @if (Auth::user()->role->jenis == 'admin')
                                                    <th scope="col" style="width: 15%;">Progress OPD</th>
                                                @else
                                                    <th scope="col" style="width: 15%;">Progress OPD</th>
                                                    <th scope="col" style="width: 15%;">Progress Verifikasi</th>
                                                @endif
                                                <th scope="col" style="width: 15%;">Nilai</th>
                                                <th scope="col" style="width: 12%;">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($this->opdList as $index => $opd)
                                                <tr>
                                                    <td>{{ $this->opdList->firstItem() + $index }}</td>
                                                    <td>{{ $opd->nama }}</td>
                                                    <td>
                                                        @php
                                                            $progressOpd = $opd->progress_opd ?? ($opd->progress ?? 0);
                                                            $progressOpdClass =
                                                                $progressOpd == 0
                                                                    ? 'bg-secondary'
                                                                    : ($progressOpd < 50
                                                                        ? 'bg-danger'
                                                                        : ($progressOpd < 100
                                                                            ? 'bg-warning'
                                                                            : 'bg-success'));
                                                        @endphp
                                                        <div
                                                            class="progress animated-progress custom-progress progress-label">
                                                            <div class="progress-bar {{ $progressOpdClass }}"
                                                                role="progressbar" style="width: {{ $progressOpd }}%"
                                                                aria-valuenow="{{ $progressOpd }}" aria-valuemin="0"
                                                                aria-valuemax="100">
                                                                <div class="label">
                                                                    {{ number_format($progressOpd, 0) }}%
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    @if (Auth::user()->role->jenis == 'verifikator')
                                                        <td>
                                                            @php
                                                                $progress = $opd->progress ?? 0;
                                                                $progressClass =
                                                                    $progress == 0
                                                                        ? 'bg-secondary'
                                                                        : ($progress < 50
                                                                            ? 'bg-danger'
                                                                            : ($progress < 100
                                                                                ? 'bg-warning'
                                                                                : 'bg-success'));
                                                            @endphp
                                                            <div
                                                                class="progress animated-progress custom-progress progress-label">
                                                                <div class="progress-bar {{ $progressClass }}"
                                                                    role="progressbar"
                                                                    style="width: {{ $progress }}%"
                                                                    aria-valuenow="{{ $progress }}"
                                                                    aria-valuemin="0" aria-valuemax="100">
                                                                    <div class="label">
                                                                        {{ number_format($progress, 0) }}%
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </td>
                                                    @endif
                                                    <td>
                                                        @if ($opd->nilai_total > 0)
                                                            <span
                                                                class="badge text-bg-primary">{{ number_format($opd->nilai_total, 2) }}</span>
                                                        @else
                                                            <span>-</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <button wire:click="selectOpd({{ $opd->id }})"
                                                            type="button" class="btn btn-sm btn-primary">
                                                            <i
                                                                class="ri-file-list-3-line align-bottom me-1"></i>Verifikasi
                                                        </button>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="{{ Auth::user()->role->jenis == 'admin' ? '5' : '6' }}"
                                                        class="text-center">Tidak ada data OPD</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                                <div class="mt-3">
                                    {{ $this->opdList->links(data: ['scrollTo' => false]) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- TIER 2: CARD KOMPONEN (setelah OPD dipilih) --}}
            @if ($opd_session)
                {{-- Breadcrumb Alert (navigation only) --}}
                <div class="row">
                    <div class="col-12">
                        <div class="alert alert-primary alert-border-left" role="alert">
                            <i class="ri-building-line me-2 align-middle fs-16"></i>
                            <strong>OPD:</strong> {{ \App\Models\Opd::find($opd_session)->nama }}
                        </div>
                    </div>
                </div>

                {{-- Nav Pills untuk Komponen --}}
                <div class="row">
                    <div class="col-xxl-12">
                        <div class="card">
                            <div class="card-header align-items-center d-flex">
                                {{-- <h4 class="card-title mb-0 flex-grow-1">Pilih Komponen untuk Verifikasi</h4> --}}
                                <p class="text-dark fw-semibold flex-grow-1">Pilih Komponen untuk Verifikasi</p>
                                <div class="flex-shrink-0">
                                    <button wire:click="backToOpd" type="button"
                                        class="btn btn-sm btn-soft-secondary btn-label waves-effect waves-light">
                                        <i class="ri-arrow-go-back-line label-icon align-middle fs-16 me-2"></i> Pilih
                                        OPD Lain
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <ul class="nav nav-pills nav-justified gap-2" role="tablist">
                                    @foreach ($this->komponenOptions as $komponen)
                                        <li wire:key="{{ $komponen->id }}" class="nav-item">
                                            <a class="nav-link waves-effect waves-light py-3"
                                                :class="{ 'active': isKomponenSelected({{ $komponen->id }}) }"
                                                @click.prevent="selectKomponen({{ $komponen->id }})" href="#"
                                                role="tab">
                                                {{ $komponen->nama }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Card Grid untuk Sub Komponen --}}
                <div class="row">
                    @foreach ($this->subKomponenOptions as $sub_komponen)
                        <div wire:key="{{ $sub_komponen->id }}" class="col-xxl-4 col-lg-4"
                            x-show="isKomponenSelected({{ $sub_komponen->komponen_id }})" x-cloak>
                            <div class="card card-body text-center">
                                <div class="avatar-sm mx-auto mb-3">
                                    <div class="avatar-title bg-soft-primary text-primary fs-17 rounded">
                                        <i class="ri-folder-2-line"></i>
                                    </div>
                                </div>
                                <h4 class="card-title">{{ $sub_komponen->nama }}</h4>
                                <p class="card-text text-muted">{{ $sub_komponen->kode }}</p>
                                <p class="text-muted">
                                    <span class="badge bg-info-subtle text-info me-2">Bobot:
                                        {{ number_format($sub_komponen->bobot_persen, 2) }}%</span>
                                    <span class="badge bg-success-subtle text-success">Nilai:
                                        {{ number_format($sub_komponen->nilai_rata_rata ?? 0, 2) }}</span>
                                </p>
                                <p class="text-muted">Jumlah Kriteria: {{ $sub_komponen->kriteria_komponen->count() }}
                                </p>
                                <a href="{{ route('lembar-kerja.kriteria-komponen', ['sub_komponen_id' => $sub_komponen->id]) }}"
                                    class="btn btn-primary">Pilih</a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        @endif

        {{-- ========================================
             TAMPILAN UNTUK PENJAMIN & PENILAI (TABLE BASED)
             ======================================== --}}
        @if (in_array(Auth::user()->role->jenis, ['penjamin', 'penilai']))

            {{-- TIER 1: TABEL OPD --}}
            @if (!$opd_session)

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header align-items-center d-flex">
                                {{-- <h4 class="card-title mb-0 flex-grow-1">Pilih OPD untuk Evaluasi</h4> --}}
                                <p class="mb-0 text-dark fw-semibold flex-grow-1">Pilih OPD untuk Evaluasi</p>
                                <div class="flex-shrink-0">
                                    <div class="search-box">
                                        <input type="text" wire:model.live="searchOpd" class="form-control search"
                                            placeholder="Cari OPD...">
                                        <i class="ri-search-line search-icon"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table align-middle table-nowrap mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th scope="col" style="width: 5%;">No</th>
                                                <th scope="col">OPD</th>
                                                <th scope="col" style="width: 13%;">Progress OPD</th>
                                                @if (Auth::user()->role->jenis == 'penjamin')
                                                    <th scope="col" style="width: 13%;">Progress Penjaminan</th>
                                                @else
                                                    <th scope="col" style="width: 13%;">Progress Evaluasi</th>
                                                @endif
                                                <th scope="col" style="width: 12%;">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($this->opdList as $index => $opd)
                                                <tr>
                                                    <td>{{ $this->opdList->firstItem() + $index }}</td>
                                                    <td>{{ $opd->nama }}</td>
                                                    <td>
                                                        @php
                                                            $progressOpd = $opd->progress_opd ?? 0;
                                                            $progressOpdClass =
                                                                $progressOpd == 0
                                                                    ? 'bg-secondary'
                                                                    : ($progressOpd < 50
                                                                        ? 'bg-danger'
                                                                        : ($progressOpd < 100
                                                                            ? 'bg-warning'
                                                                            : 'bg-success'));
                                                        @endphp
                                                        <div
                                                            class="progress animated-progress custom-progress progress-label">
                                                            <div class="progress-bar {{ $progressOpdClass }}"
                                                                role="progressbar"
                                                                style="width: {{ $progressOpd }}%"
                                                                aria-valuenow="{{ $progressOpd }}" aria-valuemin="0"
                                                                aria-valuemax="100">
                                                                <div class="label">
                                                                    {{ number_format($progressOpd, 0) }}%
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        @php
                                                            $progress = $opd->progress ?? 0;
                                                            $progressClass =
                                                                $progress == 0
                                                                    ? 'bg-secondary'
                                                                    : ($progress < 50
                                                                        ? 'bg-danger'
                                                                        : ($progress < 100
                                                                            ? 'bg-warning'
                                                                            : 'bg-success'));
                                                        @endphp
                                                        <div
                                                            class="progress animated-progress custom-progress progress-label">
                                                            <div class="progress-bar {{ $progressClass }}"
                                                                role="progressbar"
                                                                style="width: {{ $progress }}%"
                                                                aria-valuenow="{{ $progress }}" aria-valuemin="0"
                                                                aria-valuemax="100">
                                                                <div class="label">{{ number_format($progress, 0) }}%
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    {{-- <td>
                                                        @if ($opd->nilai_total > 0)
                                                            <span
                                                                class="badge text-bg-primary">{{ number_format($opd->nilai_total, 2) }}</span>
                                                        @else
                                                            <span>-</span>
                                                        @endif
                                                    </td> --}}
                                                    <td>
                                                        <button wire:click="selectOpd({{ $opd->id }})"
                                                            type="button" class="btn btn-sm btn-primary">
                                                            <i
                                                                class="ri-file-list-3-line align-bottom me-1"></i>Evaluasi
                                                        </button>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5" class="text-center">Tidak ada data OPD</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>

                                {{-- Pagination Links --}}
                                <div class="mt-3">
                                    {{ $this->opdList->links(data: ['scrollTo' => false]) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- TIER 2: TABEL KOMPONEN (setelah OPD dipilih, sebelum komponen dipilih) --}}
            @if ($opd_session && !$selected_komponen_id)
                {{-- Breadcrumb Alert (navigation only) --}}
                <div class="row">
                    <div class="col-12">
                        <div class="alert alert-primary alert-border-left" role="alert">
                            <i class="ri-building-line me-2 align-middle fs-16"></i>
                            <strong>OPD:</strong> {{ \App\Models\Opd::find($opd_session)->nama }}
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header align-items-center d-flex">
                                {{-- <h4 class="card-title mb-0 flex-grow-1">Pilih Komponen untuk Evaluasi</h4> --}}
                                <p class="mb-0 text-dark fw-semibold flex-grow-1">Pilih Komponen untuk Evaluasi</p>
                                <div class="flex-shrink-0">
                                    <button wire:click="backToOpd" type="button"
                                        class="btn btn-sm btn-soft-primary btn-label waves-effect waves-light">
                                        <i class="ri-arrow-go-back-line label-icon align-middle fs-16 me-2"></i> Pilih
                                        OPD Lain
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table align-middle table-nowrap mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th scope="col" style="width: 5%;">No</th>
                                                <th scope="col" style="width: 12%;">Kode</th>
                                                <th scope="col">Nama Komponen</th>
                                                <th scope="col" style="width: 12%;">Bobot</th>
                                                <th scope="col" style="width: 12%;">Nilai</th>
                                                <th scope="col" style="width: 12%;">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php
                                                $totalBobot = 0;
                                                $totalNilai = 0;
                                            @endphp
                                            @forelse ($this->komponenOptions as $index => $komponen)
                                                @php
                                                    $totalBobot += $komponen->bobot;
                                                    $totalNilai += $komponen->nilai_rata_rata ?? 0;
                                                @endphp
                                                <tr>
                                                    <td>{{ $index + 1 }}</td>
                                                    <td><span>{{ $komponen->kode }}</span>
                                                    </td>
                                                    <td>{{ $komponen->nama }}</td>
                                                    <td>
                                                        <span>
                                                            {{ number_format($komponen->bobot, 2) }}%
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge text-bg-primary">
                                                            {{ number_format($komponen->nilai_rata_rata ?? 0, 2) }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <button wire:click="selectKomponen({{ $komponen->id }})"
                                                            type="button" class="btn btn-sm btn-primary">
                                                            <i
                                                                class="ri-file-list-3-line align-bottom me-1"></i>Evaluasi
                                                        </button>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="6" class="text-center">Tidak ada data komponen
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                        <tfoot class="table-light">
                                            <tr>
                                                <th colspan="3" class="text-end">Total:</th>
                                                <th>
                                                    <span>
                                                        {{ number_format($totalBobot, 2) }}%
                                                    </span>
                                                </th>
                                                <th>
                                                    <span class="badge text-bg-primary">
                                                        {{ number_format($totalNilai, 2) }}
                                                    </span>
                                                </th>
                                                <th></th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- TIER 3: TABEL SUB KOMPONEN (setelah komponen dipilih) --}}
            @if ($opd_session && $selected_komponen_id)
                {{-- Breadcrumb Navigation --}}
                <div class="row">
                    <div class="col-12">
                        <div class="alert alert-primary alert-border-left" role="alert">
                            <i class="ri-building-line me-2 align-middle fs-16"></i>
                            <strong>OPD:</strong> {{ \App\Models\Opd::find($opd_session)->nama }}
                            <i class="ri-arrow-right-s-line mx-2"></i>
                            <strong>Komponen:</strong> {{ \App\Models\Komponen::find($selected_komponen_id)->nama }}
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header align-items-center d-flex">
                                {{-- <h4 class="card-title mb-0 flex-grow-1">Pilih Sub Komponen untuk Evaluasi</h4> --}}
                                <p class="mb-0 text-dark fw-semibold flex-grow-1">Pilih Sub Komponen untuk Evaluasi</p>
                                <div class="flex-shrink-0">
                                    <button wire:click="backToKomponen" type="button"
                                        class="btn btn-sm btn-soft-primary btn-label waves-effect waves-light">
                                        <i class="ri-arrow-left-line label-icon align-middle fs-16 me-2"></i> Kembali
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table align-middle table-nowrap mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th scope="col" style="width: 5%;">No</th>
                                                <th scope="col" style="width: 12%;">Kode</th>
                                                <th scope="col">Nama Sub Komponen</th>
                                                <th scope="col" style="width: 12%;">Bobot</th>
                                                <th scope="col" style="width: 12%;">Nilai</th>
                                                <th scope="col" style="width: 12%;">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php
                                                $totalBobot = 0;
                                                $totalNilai = 0;
                                            @endphp
                                            @forelse ($this->subKomponenOptions as $index => $subKomponen)
                                                @php
                                                    $totalBobot += $subKomponen->bobot_persen;
                                                    $totalNilai += $subKomponen->nilai_rata_rata ?? 0;
                                                @endphp
                                                <tr>
                                                    <td>{{ $index + 1 }}</td>
                                                    <td><span>{{ $subKomponen->kode }}</span>
                                                    </td>
                                                    <td>{{ $subKomponen->nama }}</td>
                                                    <td>
                                                        <span class="badge text-bg-primary">
                                                            {{ number_format($subKomponen->bobot_persen, 2) }}%
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge text-bg-primary">
                                                            {{ number_format($subKomponen->nilai_rata_rata ?? 0, 2) }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <a href="{{ route('lembar-kerja.kriteria-komponen', ['sub_komponen_id' => $subKomponen->id]) }}"
                                                            class="btn btn-sm btn-primary">
                                                            <i
                                                                class="ri-file-list-3-line align-bottom me-1"></i>Evaluasi
                                                        </a>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="6" class="text-center">Tidak ada data sub komponen
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                        <tfoot class="table-light">
                                            <tr>
                                                <th colspan="3" class="text-end">Total:</th>
                                                <th>
                                                    <span>
                                                        {{ number_format($totalBobot, 2) }}%
                                                    </span>
                                                </th>
                                                <th>
                                                    <span class="badge text-bg-primary">
                                                        {{ number_format($totalNilai, 2) }}
                                                    </span>
                                                </th>
                                                <th></th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
            @endif
        @endif

    </div>
</div>
