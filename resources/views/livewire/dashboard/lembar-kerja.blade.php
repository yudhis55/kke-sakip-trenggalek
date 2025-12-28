<div class="page-content">
    <style>
        .badge-pulsate {
            display: inline-block;
            background-color: red;
            border-radius: 50%;
            width: 5px;
            height: 5px;
            padding: 0;
            /* margin-left: 0.8rem; */
            position: relative;
        }

        .badge-pulsate::before {
            content: '';
            display: block;
            position: absolute;
            top: -3px;
            left: -3px;
            right: -3px;
            bottom: -3px;
            animation: pulse 1s ease infinite;
            border-radius: 50%;
            border: 2px solid rgba(255, 100, 100, 0.6);
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
                opacity: 1;
            }

            60% {
                transform: scale(1.3);
                opacity: 0.4;
            }

            100% {
                transform: scale(1.4);
                opacity: 0;
            }
        }
    </style>
    <div x-data="{ tab: 'bukti_dukung', menu: 'dokumen' }" x-on:filter-changed.window="tab = 'bukti_dukung'; menu = 'dokumen'"
        class="container-fluid" x-cloak>

        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0 font-size-18">Lembar Kerja</h4>

                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="javascript: void(0);">Dashboard</a></li>
                            <li class="breadcrumb-item active">Lembar Kerja</li>
                        </ol>
                    </div>

                </div>
            </div>
        </div>
        <!-- end page title -->

        @if ($opd_session == null && in_array(Auth::user()->role->jenis, ['admin', 'verifikator', 'penjamin', 'penilai']))
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header align-items-center d-flex">
                            <p class="mb-0 text-dark fw-semibold flex-grow-1">Pilih OPD untuk Dievaluasi</p>
                            <div class="flex-shrink-0">
                                <div class="search-box">
                                    <input type="text" class="form-control" placeholder="Cari OPD..."
                                        wire:model.live="searchOpd">
                                    <i class="ri-search-line search-icon"></i>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table align-middle table-nowrap mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th scope="col">No</th>
                                            <th scope="col">OPD</th>
                                            <th scope="col">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($this->opdList as $index => $opd)
                                            <tr>
                                                <td>{{ $this->opdList->firstItem() + $index }}</td>
                                                <td>{{ $opd->nama }}</td>
                                                <td>
                                                    <a wire:click="selectOpd({{ $opd->id }})"
                                                        href="javascript:void(0);" class="btn btn-sm btn-primary">
                                                        <i class="ri-file-list-3-line align-bottom me-1"></i>Evaluasi
                                                    </a>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="3" class="text-center">Tidak ada data OPD.</td>
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

        @if ($opd_session)
            {{-- Alert OPD: Hanya tampil untuk non-OPD (admin, verifikator, penjamin, penilai) --}}
            @if (Auth::user()->role->jenis != 'opd')
                <div class="">
                    <div class="col-12">
                        <div class="alert alert-primary alert-border-left d-flex align-items-center" role="alert">
                            <div class="flex-grow-1">
                                <span class="d-flex align-items-center">
                                    <i class="ri-building-line me-3 align-middle"></i>
                                    <span>
                                        <span class="d-inline-block fw-semibold">OPD: {{ $this->opdName() }}</span>
                                        @if ($this->cardTitle())
                                            <span class="d-block">{{ $this->cardTitle() }}</span>
                                        @endif
                                    </span>
                                </span>
                            </div>
                            <div class="flex-shrink-0">
                                <button wire:click="resetOpd" type="button"
                                    class="btn btn-sm btn-primary btn-label waves-effect waves-light">
                                    <i class="ri-arrow-go-back-line label-icon align-middle fs-16 me-2"></i> Pilih
                                    OPD Lain
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <p class="text-primary fw-semibold mb-1">{{ $this->cardTitle() }}</p>

                        </div>
                    </div>
                </div>
            </div> --}}

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-lg-4">
                                    <div>
                                        <label for="employeeName" class="form-label">Komponen</label>
                                        <select wire:model.live="komponen_session"
                                            wire:change="$dispatch('filter-changed')" class="form-select"
                                            id="inputGroupSelect01">
                                            <option value="" selected>Pilih komponen...</option>
                                            @foreach ($this->komponenList() as $komponen)
                                                <option value="{{ $komponen->id }}">{{ $komponen->nama }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div>
                                        <label for="employeeName" class="form-label">Sub Komponen</label>
                                        <select wire:model.live="sub_komponen_session"
                                            wire:change="$dispatch('filter-changed')" class="form-select"
                                            id="inputGroupSelect01">
                                            <option value="" selected>Pilih sub komponen...</option>
                                            @foreach ($this->subKomponenList() as $subKomponen)
                                                <option value="{{ $subKomponen->id }}">{{ $subKomponen->nama }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div>
                                        <label for="employeeName" class="form-label">Kriteria Komponen</label>
                                        <select wire:model.live="kriteria_komponen_session"
                                            wire:change="$dispatch('filter-changed')" class="form-select"
                                            id="inputGroupSelect01">
                                            <option value="" selected>Pilih kriteria komponen...</option>
                                            @foreach ($this->kriteriaKomponenList() as $kriteriaKomponen)
                                                <option value="{{ $kriteriaKomponen->id }}">
                                                    {{ $kriteriaKomponen->nama }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                {{-- <div class="col-lg-2 align-items-center d-flex">
                                    <button type="button" class="btn btn-primary me-2">
                                        <span class=""><i class="ri-filter-3-line align-bottom me-1"></i>
                                            Filter</span>
                                    </button>
                                    <button type="button" class="btn btn-light">
                                        <span class=""><i class="ri-restart-line align-bottom me-1"></i>
                                            Reset</span>
                                    </button>
                                </div> --}}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @if (!$kriteria_komponen_session)
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header align-items-center d-flex">
                                <div class="flex-grow-1">
                                    <p class="mb-0 text-dark fw-semibold">
                                        {{ $this->cardTitle() }}
                                        @if ($komponen_session && !$sub_komponen_session)
                                            {{-- Level Sub Komponen: tampilkan bobot komponen --}}
                                            @php
                                                $komponenSelected = \App\Models\Komponen::find($komponen_session);
                                            @endphp
                                            @if ($komponenSelected)
                                                <span class="badge text-bg-primary ms-2">
                                                    ( Bobot: {{ number_format($komponenSelected->bobot, 2) }}% )
                                                </span>
                                            @endif
                                        @elseif ($komponen_session && $sub_komponen_session)
                                            {{-- Level Kriteria: tampilkan bobot sub komponen saja --}}
                                            @php
                                                $subKomponenSelected = \App\Models\SubKomponen::find(
                                                    $sub_komponen_session,
                                                );
                                            @endphp
                                            @if ($subKomponenSelected)
                                                <span class="badge text-bg-primary ms-2">
                                                    ( Bobot: {{ number_format($subKomponenSelected->bobot, 2) }}% )
                                                </span>
                                            @endif
                                        @endif
                                    </p>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    @php
                                        // Tentukan level tampilan berdasarkan filter
                                        $isKomponenLevel = !$komponen_session;
                                        $isSubKomponenLevel = $komponen_session && !$sub_komponen_session;
                                        $isKriteriaLevel = $komponen_session && $sub_komponen_session;
                                    @endphp
                                    <table class="table align-middle mb-0">
                                        @if ($this->lembarKerjaList() != [])
                                            <thead class="table-light">
                                                <tr>
                                                    <th scope="col" style="width: 5%">No.</th>
                                                    <th scope="col"
                                                        style="width: {{ $isKriteriaLevel ? '35%' : '50%' }}">
                                                        @if ($isKomponenLevel)
                                                            Komponen
                                                        @elseif ($isSubKomponenLevel)
                                                            Sub Komponen
                                                        @elseif ($isKriteriaLevel)
                                                            Kriteria Komponen
                                                        @endif
                                                    </th>
                                                    <th scope="col" style="width: 8%">Bobot</th>

                                                    {{-- Kolom Progres Penilaian --}}
                                                    @if (!$isKriteriaLevel)
                                                        {{-- Komponen dan Sub Komponen level --}}
                                                        <th scope="col" style="width: 8%">Progres<br>Kriteria</th>
                                                        <th scope="col" style="width: 8%">Progres<br>Bukti</th>
                                                    @else
                                                        {{-- Kriteria level --}}
                                                        <th scope="col" style="width: 8%">Progres<br>Bukti</th>
                                                    @endif

                                                    {{-- Kolom untuk semua level --}}
                                                    {{-- Penilaian Mandiri (semua role bisa lihat) --}}
                                                    @if (in_array(Auth::user()->role->jenis, ['admin', 'opd', 'verifikator', 'penjamin', 'penilai']))
                                                        <th scope="col" style="width: 10%">Penilaian<br>Mandiri
                                                        </th>
                                                    @endif

                                                    @php
                                                        // Cek apakah ada item dengan penilaian_di = 'kriteria' untuk menampilkan kolom verifikasi
                                                        $showVerifikasiKolom =
                                                            $isKriteriaLevel &&
                                                            $this->lembarKerjaList()->contains(
                                                                fn($item) => $item->penilaian_di === 'kriteria',
                                                            );
                                                    @endphp

                                                    {{-- Verifikasi Verifikator (HANYA untuk kriteria dengan penilaian_di='kriteria') --}}
                                                    @if ($showVerifikasiKolom && in_array(Auth::user()->role->jenis, ['admin', 'verifikator', 'penjamin', 'penilai']))
                                                        <th scope="col" style="width: 8%">Verifikasi<br>Verifikator
                                                        </th>
                                                    @endif

                                                    {{-- Verifikasi Penjamin (HANYA untuk kriteria dengan penilaian_di='kriteria') --}}
                                                    @if ($showVerifikasiKolom && in_array(Auth::user()->role->jenis, ['admin', 'penjamin', 'penilai']))
                                                        <th scope="col" style="width: 8%">Verifikasi<br>Evaluator
                                                        </th>
                                                    @endif

                                                    {{-- Penjaminan Kualitas (nilai dari penjamin) --}}
                                                    @if (in_array(Auth::user()->role->jenis, ['admin', 'penjamin', 'penilai']))
                                                        <th scope="col" style="width: 10%">Evaluator
                                                        </th>
                                                    @endif

                                                    {{-- Evaluator (nilai dari penilai) --}}
                                                    @if (in_array(Auth::user()->role->jenis, ['admin', 'penilai']))
                                                        <th scope="col" style="width: 10%">Penjamin<br>Kualitas
                                                        </th>
                                                    @endif

                                                    <th scope="col" style="width: 5%"></th>

                                                    {{-- Kolom Tracking HANYA untuk kriteria level dengan penilaian_di = kriteria --}}
                                                    @if ($isKriteriaLevel && in_array(Auth::user()->role->jenis, ['admin', 'opd']))
                                                        <th scope="col" style="width: 5%">Tracking</th>
                                                    @endif
                                                </tr>
                                            </thead>
                                        @endif
                                        <tbody>
                                            @php
                                                $totalNilaiOpd = 0;
                                                $totalNilaiVerifikator = 0;
                                                $totalNilaiPenjamin = 0;
                                                $totalNilaiPenilai = 0;

                                                // Role IDs untuk perhitungan nilai
                                                $opdRoleId = \App\Models\Role::where('jenis', 'opd')->first()?->id;
                                                $verifikatorRoleId = \App\Models\Role::where(
                                                    'jenis',
                                                    'verifikator',
                                                )->first()?->id;
                                                $penjaminRoleId = \App\Models\Role::where('jenis', 'penjamin')->first()
                                                    ?->id;
                                                $penilaiRoleId = \App\Models\Role::where('jenis', 'penilai')->first()
                                                    ?->id;
                                            @endphp
                                            @forelse ($this->lembarKerjaList() as $index => $lembar_kerja)
                                                @php
                                                    // Hitung nilai untuk semua level
                                                    $nilaiOpd = $lembar_kerja->getNilai($this->opd_session, $opdRoleId);
                                                    $nilaiVerifikator = $lembar_kerja->getNilai(
                                                        $this->opd_session,
                                                        $verifikatorRoleId,
                                                    );
                                                    $nilaiPenjamin = $lembar_kerja->getNilai(
                                                        $this->opd_session,
                                                        $penjaminRoleId,
                                                    );
                                                    $nilaiPenilai = $lembar_kerja->getNilai(
                                                        $this->opd_session,
                                                        $penilaiRoleId,
                                                    );

                                                    $totalNilaiOpd += $nilaiOpd;
                                                    $totalNilaiVerifikator += $nilaiVerifikator;
                                                    $totalNilaiPenjamin += $nilaiPenjamin;
                                                    $totalNilaiPenilai += $nilaiPenilai;

                                                    // Info dan metadata per level
                                                    if ($isKomponenLevel && !$komponen_session) {
                                                        $hasRejection = $this->hasRejection($lembar_kerja, 'komponen');
                                                        $jumlahSubKomponen = $lembar_kerja->sub_komponen()->count();
                                                        $jumlahKriteria = \DB::table('kriteria_komponen')
                                                            ->where('komponen_id', $lembar_kerja->id)
                                                            ->count();
                                                        $infoText = "Jumlah sub komponen: {$jumlahSubKomponen} | Jumlah kriteria: {$jumlahKriteria}";
                                                        $bobotKriteria = $lembar_kerja->bobot ?? 0;
                                                    } elseif ($isKomponenLevel) {
                                                        $hasRejection = $this->hasRejection(
                                                            $lembar_kerja,
                                                            'sub_komponen',
                                                        );
                                                        $jumlahKriteria = $lembar_kerja->kriteria_komponen()->count();
                                                        $jumlahBuktiDukung = \DB::table('bukti_dukung')
                                                            ->join(
                                                                'kriteria_komponen',
                                                                'bukti_dukung.kriteria_komponen_id',
                                                                '=',
                                                                'kriteria_komponen.id',
                                                            )
                                                            ->where(
                                                                'kriteria_komponen.sub_komponen_id',
                                                                $lembar_kerja->id,
                                                            )
                                                            ->count();
                                                        $infoText = "Jumlah kriteria: {$jumlahKriteria} | Jumlah bukti dukung: {$jumlahBuktiDukung}";
                                                        $bobotKriteria = $lembar_kerja->bobot ?? 0;
                                                    } elseif ($isSubKomponenLevel) {
                                                        $hasRejection = $this->hasRejection($lembar_kerja, 'kriteria');
                                                        $jumlahKriteria = $lembar_kerja->kriteria_komponen()->count();
                                                        $jumlahBuktiDukung = $lembar_kerja->bukti_dukung()->count();
                                                        $infoText = "Jumlah kriteria: {$jumlahKriteria} | Jumlah bukti dukung: {$jumlahBuktiDukung}";
                                                        $bobotKriteria = $lembar_kerja->bobot ?? 0;
                                                    } else {
                                                        $hasRejection = $this->hasRejection($lembar_kerja, 'kriteria');
                                                        $jumlahBuktiDukung = $lembar_kerja->bukti_dukung()->count();
                                                        $penilaianDiText =
                                                            $lembar_kerja->penilaian_di === 'kriteria'
                                                                ? 'Kriteria'
                                                                : 'Bukti';
                                                        $infoText = "Jumlah bukti dukung: {$jumlahBuktiDukung} | Jenis Penilaian: {$penilaianDiText}";
                                                        $bobotKriteria = $lembar_kerja->bobot ?? 0;
                                                    }
                                                @endphp
                                                <tr>
                                                    <td>{{ $index + 1 }}</td>
                                                    <td>
                                                        <span class="d-flex align-items-center">
                                                            <span class="me-3">
                                                                <button type="button"
                                                                    class="btn btn-sm btn-outline-primary position-relative p-0 avatar-xs rounded-circle">
                                                                    <span
                                                                        class="avatar-title bg-transparent text-reset">
                                                                        <i class="bx bx-menu"></i>
                                                                    </span>
                                                                    @if ($hasRejection)
                                                                        <span
                                                                            class="position-absolute top-0 start-100 translate-middle badge-pulsate"></span>
                                                                    @endif
                                                                    {{-- <span
                                                                        class="position-absolute top-0 start-100 translate-middle badge border border-light rounded-circle bg-warning p-1"><span
                                                                            class="visually-hidden">unread
                                                                            messages</span></span> --}}
                                                                </button>
                                                            </span>
                                                            <span>
                                                                @if ($isKomponenLevel)
                                                                    <span
                                                                        wire:click="selectKomponen({{ $lembar_kerja->id }})"
                                                                        class="d-inline-block text-primary fw-semibold"
                                                                        style="cursor: pointer;">{{ $lembar_kerja->nama }}</span>
                                                                @elseif ($isSubKomponenLevel)
                                                                    <span
                                                                        wire:click="selectSubKomponen({{ $lembar_kerja->id }})"
                                                                        class="d-inline-block text-primary fw-semibold"
                                                                        style="cursor: pointer;">{{ $lembar_kerja->nama }}</span>
                                                                @else
                                                                    <span
                                                                        wire:click="selectKriteriaKomponen({{ $lembar_kerja->id }})"
                                                                        class="d-inline-block text-primary fw-semibold"
                                                                        style="cursor: pointer;">{{ $lembar_kerja->nama }}</span>
                                                                @endif
                                                                <span
                                                                    class="d-block text-muted"><small>{{ $infoText }}</small></span>
                                                            </span>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span>{{ number_format($bobotKriteria, 2) }}%</span>
                                                    </td>

                                                    {{-- Kolom Progres Penilaian --}}
                                                    @php
                                                        $userRole = Auth::user()->role->jenis;
                                                        $roleId = Auth::user()->role->id;
                                                    @endphp

                                                    @if (!$isKriteriaLevel)
                                                        {{-- Komponen atau Sub Komponen level --}}
                                                        @if ($isKomponenLevel && !$komponen_session)
                                                            {{-- Level Komponen: Hitung progres kriteria dan bukti dari semua sub komponen --}}
                                                            @php
                                                                // Total kriteria hanya yang penilaian_di='kriteria'
                                                                $totalKriteria = \DB::table('kriteria_komponen')
                                                                    ->where('komponen_id', $lembar_kerja->id)
                                                                    ->where('penilaian_di', 'kriteria')
                                                                    ->count();

                                                                // Hitung kriteria dengan penilaian_di='kriteria' yang sudah dinilai/diverifikasi
                                                                $selesaiKriteria = \DB::table('kriteria_komponen')
                                                                    ->where('komponen_id', $lembar_kerja->id)
                                                                    ->where('penilaian_di', 'kriteria')
                                                                    ->whereExists(function ($query) use (
                                                                        $roleId,
                                                                        $userRole,
                                                                    ) {
                                                                        $query
                                                                            ->select(\DB::raw(1))
                                                                            ->from('penilaian')
                                                                            ->whereColumn(
                                                                                'penilaian.kriteria_komponen_id',
                                                                                'kriteria_komponen.id',
                                                                            )
                                                                            ->where(
                                                                                'penilaian.opd_id',
                                                                                $this->opd_session,
                                                                            )
                                                                            ->where('penilaian.role_id', $roleId)
                                                                            ->where(function ($q) use ($userRole) {
                                                                                if ($userRole == 'opd') {
                                                                                    $q->whereNotNull(
                                                                                        'tingkatan_nilai_id',
                                                                                    );
                                                                                } elseif ($userRole == 'verifikator') {
                                                                                    $q->whereNotNull('is_verified');
                                                                                } elseif ($userRole == 'penjamin') {
                                                                                    $q->whereNotNull('is_verified');
                                                                                } elseif ($userRole == 'penilai') {
                                                                                    $q->whereNotNull(
                                                                                        'tingkatan_nilai_id',
                                                                                    );
                                                                                }
                                                                            });
                                                                    })
                                                                    ->count();

                                                                $totalBukti = \DB::table('bukti_dukung')
                                                                    ->join(
                                                                        'kriteria_komponen',
                                                                        'bukti_dukung.kriteria_komponen_id',
                                                                        '=',
                                                                        'kriteria_komponen.id',
                                                                    )
                                                                    ->where(
                                                                        'kriteria_komponen.komponen_id',
                                                                        $lembar_kerja->id,
                                                                    )
                                                                    ->where('kriteria_komponen.penilaian_di', 'bukti')
                                                                    ->count();

                                                                $selesaiBukti = \DB::table('bukti_dukung')
                                                                    ->join(
                                                                        'kriteria_komponen',
                                                                        'bukti_dukung.kriteria_komponen_id',
                                                                        '=',
                                                                        'kriteria_komponen.id',
                                                                    )
                                                                    ->where(
                                                                        'kriteria_komponen.komponen_id',
                                                                        $lembar_kerja->id,
                                                                    )
                                                                    ->where('kriteria_komponen.penilaian_di', 'bukti')
                                                                    ->whereExists(function ($query) use (
                                                                        $roleId,
                                                                        $userRole,
                                                                    ) {
                                                                        $query
                                                                            ->select(\DB::raw(1))
                                                                            ->from('penilaian')
                                                                            ->whereColumn(
                                                                                'penilaian.bukti_dukung_id',
                                                                                'bukti_dukung.id',
                                                                            )
                                                                            ->where(
                                                                                'penilaian.opd_id',
                                                                                $this->opd_session,
                                                                            )
                                                                            ->where('penilaian.role_id', $roleId)
                                                                            ->where(function ($q) use ($userRole) {
                                                                                if ($userRole == 'opd') {
                                                                                    $q->whereNotNull(
                                                                                        'tingkatan_nilai_id',
                                                                                    );
                                                                                } elseif ($userRole == 'verifikator') {
                                                                                    $q->whereNotNull('is_verified');
                                                                                } elseif ($userRole == 'penjamin') {
                                                                                    $q->whereNotNull('is_verified');
                                                                                } elseif ($userRole == 'penilai') {
                                                                                    $q->whereNotNull(
                                                                                        'tingkatan_nilai_id',
                                                                                    );
                                                                                }
                                                                            });
                                                                    })
                                                                    ->count();
                                                            @endphp
                                                            <td>
                                                                <span>{{ $selesaiKriteria }}/{{ $totalKriteria }}</span>
                                                            </td>
                                                            <td>
                                                                <span>{{ $selesaiBukti }}/{{ $totalBukti }}</span>
                                                            </td>
                                                        @else
                                                            {{-- Level Sub Komponen --}}
                                                            @php
                                                                // Total kriteria hanya yang penilaian_di='kriteria'
                                                                $totalKriteria = $lembar_kerja
                                                                    ->kriteria_komponen()
                                                                    ->where('penilaian_di', 'kriteria')
                                                                    ->count();

                                                                $selesaiKriteria = $lembar_kerja
                                                                    ->kriteria_komponen()
                                                                    ->where('penilaian_di', 'kriteria')
                                                                    ->whereHas('penilaian', function ($query) use (
                                                                        $roleId,
                                                                        $userRole,
                                                                    ) {
                                                                        $query
                                                                            ->where('opd_id', $this->opd_session)
                                                                            ->where('role_id', $roleId)
                                                                            ->where(function ($q) use ($userRole) {
                                                                                if ($userRole == 'opd') {
                                                                                    $q->whereNotNull(
                                                                                        'tingkatan_nilai_id',
                                                                                    );
                                                                                } elseif ($userRole == 'verifikator') {
                                                                                    $q->whereNotNull('is_verified');
                                                                                } elseif ($userRole == 'penjamin') {
                                                                                    $q->whereNotNull('is_verified');
                                                                                } elseif ($userRole == 'penilai') {
                                                                                    $q->whereNotNull(
                                                                                        'tingkatan_nilai_id',
                                                                                    );
                                                                                }
                                                                            });
                                                                    })
                                                                    ->count();

                                                                $totalBukti = \DB::table('bukti_dukung')
                                                                    ->join(
                                                                        'kriteria_komponen',
                                                                        'bukti_dukung.kriteria_komponen_id',
                                                                        '=',
                                                                        'kriteria_komponen.id',
                                                                    )
                                                                    ->where(
                                                                        'kriteria_komponen.sub_komponen_id',
                                                                        $lembar_kerja->id,
                                                                    )
                                                                    ->where('kriteria_komponen.penilaian_di', 'bukti')
                                                                    ->count();

                                                                $selesaiBukti = \DB::table('bukti_dukung')
                                                                    ->join(
                                                                        'kriteria_komponen',
                                                                        'bukti_dukung.kriteria_komponen_id',
                                                                        '=',
                                                                        'kriteria_komponen.id',
                                                                    )
                                                                    ->where(
                                                                        'kriteria_komponen.sub_komponen_id',
                                                                        $lembar_kerja->id,
                                                                    )
                                                                    ->where('kriteria_komponen.penilaian_di', 'bukti')
                                                                    ->whereExists(function ($query) use (
                                                                        $roleId,
                                                                        $userRole,
                                                                    ) {
                                                                        $query
                                                                            ->select(\DB::raw(1))
                                                                            ->from('penilaian')
                                                                            ->whereColumn(
                                                                                'penilaian.bukti_dukung_id',
                                                                                'bukti_dukung.id',
                                                                            )
                                                                            ->where(
                                                                                'penilaian.opd_id',
                                                                                $this->opd_session,
                                                                            )
                                                                            ->where('penilaian.role_id', $roleId)
                                                                            ->where(function ($q) use ($userRole) {
                                                                                if ($userRole == 'opd') {
                                                                                    $q->whereNotNull(
                                                                                        'tingkatan_nilai_id',
                                                                                    );
                                                                                } elseif ($userRole == 'verifikator') {
                                                                                    $q->whereNotNull('is_verified');
                                                                                } elseif ($userRole == 'penjamin') {
                                                                                    $q->whereNotNull('is_verified');
                                                                                } elseif ($userRole == 'penilai') {
                                                                                    $q->whereNotNull(
                                                                                        'tingkatan_nilai_id',
                                                                                    );
                                                                                }
                                                                            });
                                                                    })
                                                                    ->count();
                                                            @endphp
                                                            <td>
                                                                <span>{{ $selesaiKriteria }}/{{ $totalKriteria }}</span>
                                                            </td>
                                                            <td>
                                                                <span>{{ $selesaiBukti }}/{{ $totalBukti }}</span>
                                                            </td>
                                                        @endif
                                                    @else
                                                        {{-- Level Kriteria Komponen --}}
                                                        @php
                                                            if ($lembar_kerja->penilaian_di === 'bukti') {
                                                                $totalBukti = $lembar_kerja->bukti_dukung()->count();

                                                                $selesaiBukti = $lembar_kerja
                                                                    ->bukti_dukung()
                                                                    ->whereHas('penilaian', function ($query) use (
                                                                        $roleId,
                                                                        $userRole,
                                                                    ) {
                                                                        $query
                                                                            ->where('opd_id', $this->opd_session)
                                                                            ->where('role_id', $roleId)
                                                                            ->where(function ($q) use ($userRole) {
                                                                                if ($userRole == 'opd') {
                                                                                    $q->whereNotNull(
                                                                                        'tingkatan_nilai_id',
                                                                                    );
                                                                                } elseif ($userRole == 'verifikator') {
                                                                                    $q->whereNotNull('is_verified');
                                                                                } elseif ($userRole == 'penjamin') {
                                                                                    $q->whereNotNull('is_verified');
                                                                                } elseif ($userRole == 'penilai') {
                                                                                    $q->whereNotNull(
                                                                                        'tingkatan_nilai_id',
                                                                                    );
                                                                                }
                                                                            });
                                                                    })
                                                                    ->count();
                                                            } else {
                                                                $totalBukti = 0;
                                                                $selesaiBukti = 0;
                                                            }
                                                        @endphp
                                                        <td>
                                                            @if ($lembar_kerja->penilaian_di === 'bukti')
                                                                <span>{{ $selesaiBukti }}/{{ $totalBukti }}</span>
                                                            @else
                                                                <span class="text-muted">-</span>
                                                            @endif
                                                        </td>
                                                    @endif

                                                    {{-- Kolom Penilaian Mandiri (semua level) --}}
                                                    @if (in_array(Auth::user()->role->jenis, ['admin', 'opd', 'verifikator', 'penjamin', 'penilai']))
                                                        <td>
                                                            {{ $nilaiOpd > 0 ? number_format($nilaiOpd, 2) . '%' : '-' }}
                                                        </td>
                                                    @endif

                                                    {{-- Kolom Verifikasi Verifikator (HANYA untuk kriteria dengan penilaian_di='kriteria') --}}
                                                    @if (
                                                        $isKriteriaLevel &&
                                                            in_array(Auth::user()->role->jenis, ['admin', 'verifikator', 'penjamin', 'penilai']) &&
                                                            ($showVerifikasiKolom ?? false))
                                                        <td>
                                                            @if ($lembar_kerja->penilaian_di === 'kriteria')
                                                                @php
                                                                    $verifikatorRoleId = \App\Models\Role::where(
                                                                        'jenis',
                                                                        'verifikator',
                                                                    )->first()?->id;
                                                                    $isVerifiedByVerifikator = \App\Models\Penilaian::where(
                                                                        'opd_id',
                                                                        $this->opd_session,
                                                                    )
                                                                        ->where(
                                                                            'kriteria_komponen_id',
                                                                            $lembar_kerja->id,
                                                                        )
                                                                        ->where('role_id', $verifikatorRoleId)
                                                                        ->where('is_verified', true)
                                                                        ->exists();
                                                                @endphp
                                                                @if ($isVerifiedByVerifikator)
                                                                    <i
                                                                        class="ri-checkbox-circle-fill text-success fs-18"></i>
                                                                @else
                                                                    <span class="text-muted">-</span>
                                                                @endif
                                                            @else
                                                                <small class="text-muted fst-italic">di bukti
                                                                    dukung</small>
                                                            @endif
                                                        </td>
                                                    @endif

                                                    {{-- Kolom Verifikasi Penjamin (HANYA untuk kriteria dengan penilaian_di='kriteria') --}}
                                                    @if (
                                                        $isKriteriaLevel &&
                                                            in_array(Auth::user()->role->jenis, ['admin', 'penjamin', 'penilai']) &&
                                                            ($showVerifikasiKolom ?? false))
                                                        <td>
                                                            @if ($lembar_kerja->penilaian_di === 'kriteria')
                                                                @php
                                                                    $penjaminRoleId = \App\Models\Role::where(
                                                                        'jenis',
                                                                        'penjamin',
                                                                    )->first()?->id;
                                                                    $isVerifiedByPenjamin = \App\Models\Penilaian::where(
                                                                        'opd_id',
                                                                        $this->opd_session,
                                                                    )
                                                                        ->where(
                                                                            'kriteria_komponen_id',
                                                                            $lembar_kerja->id,
                                                                        )
                                                                        ->where('role_id', $penjaminRoleId)
                                                                        ->where('is_verified', true)
                                                                        ->exists();
                                                                @endphp
                                                                @if ($isVerifiedByPenjamin)
                                                                    <i
                                                                        class="ri-checkbox-circle-fill text-success fs-18"></i>
                                                                @else
                                                                    <span class="text-muted">-</span>
                                                                @endif
                                                            @else
                                                                <small class="text-muted fst-italic">di bukti
                                                                    dukung</small>
                                                            @endif
                                                        </td>
                                                    @endif

                                                    {{-- Kolom Penjaminan Kualitas (nilai dari penjamin - semua level) --}}
                                                    @if (in_array(Auth::user()->role->jenis, ['admin', 'penjamin', 'penilai']))
                                                        <td>
                                                            {{ $nilaiPenjamin > 0 ? number_format($nilaiPenjamin, 2) . '%' : '-' }}
                                                        </td>
                                                    @endif

                                                    {{-- Kolom Evaluator (nilai dari penilai - semua level) --}}
                                                    @if (in_array(Auth::user()->role->jenis, ['admin', 'penilai']))
                                                        <td>
                                                            {{ $nilaiPenilai > 0 ? number_format($nilaiPenilai, 2) . '%' : '-' }}
                                                        </td>
                                                    @endif

                                                    <td>
                                                        @if ($isKomponenLevel)
                                                            {{-- Level Komponen: Pilih untuk drill down ke sub komponen --}}
                                                            <button
                                                                wire:click="selectKomponen({{ $lembar_kerja->id }})"
                                                                class="btn btn-sm btn-primary">
                                                                <i
                                                                    class="ri-folder-open-line align-bottom me-1"></i>Pilih
                                                            </button>
                                                        @elseif ($isSubKomponenLevel)
                                                            {{-- Level Sub Komponen: Pilih untuk drill down ke kriteria --}}
                                                            <button
                                                                wire:click="selectSubKomponen({{ $lembar_kerja->id }})"
                                                                class="btn btn-sm btn-primary">
                                                                <i
                                                                    class="ri-folder-open-line align-bottom me-1"></i>Pilih
                                                            </button>
                                                        @else
                                                            {{-- Level Kriteria: Evaluasi --}}
                                                            <button
                                                                wire:click="selectKriteriaKomponen({{ $lembar_kerja->id }})"
                                                                class="btn btn-sm btn-primary">
                                                                <i
                                                                    class="ri-file-list-3-line align-bottom me-1"></i>Evaluasi
                                                            </button>
                                                        @endif
                                                    </td>

                                                    {{-- Tombol tracking HANYA untuk kriteria level dengan penilaian_di = kriteria --}}
                                                    @if ($isKriteriaLevel && in_array(Auth::user()->role->jenis, ['admin', 'opd']))
                                                        @if ($lembar_kerja->penilaian_di == 'kriteria')
                                                            <td>
                                                                <button type="button"
                                                                    wire:click="showTracking({{ $lembar_kerja->id }})"
                                                                    class="btn btn-sm btn-primary btn-icon waves-effect waves-light"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#trackingModal"><i
                                                                        class="ri-eye-line"></i></button>
                                                            </td>
                                                        @else
                                                            <td class="text-muted fst-italic"><small>Di bukti
                                                                    dukung</small></td>
                                                        @endif
                                                    @endif
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="10" class="text-center">
                                                        <div class="py-4">
                                                            <lord-icon src="https://cdn.lordicon.com/msoeawqm.json"
                                                                trigger="loop"
                                                                colors="primary:#121331,secondary:#08a88a"
                                                                style="width:75px;height:75px">
                                                            </lord-icon>
                                                            <h5 class="mt-2">Data tidak ditemukan</h5>
                                                            <p class="text-muted mb-0">Silahkan memilih pada kolom
                                                                filter
                                                                terlebih dahulu</p>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                        @if ($this->lembarKerjaList() != [])
                                            {{-- Footer untuk semua level (Komponen, Sub Komponen, Kriteria) --}}
                                            <tfoot class="table-light">
                                                <tr>
                                                    @if ($isKomponenLevel || $isSubKomponenLevel)
                                                        {{-- Komponen dan Sub Komponen: No + Nama + Bobot + Progres Kriteria + Progres Bukti = 5 kolom --}}
                                                        <td colspan="5" class="text-end"><strong>JUMLAH:</strong>
                                                        </td>
                                                    @elseif ($isKriteriaLevel)
                                                        {{-- Kriteria: No + Nama + Bobot + Progres Bukti = 4 kolom --}}
                                                        <td colspan="4" class="text-end"><strong>JUMLAH:</strong>
                                                        </td>
                                                    @endif

                                                    {{-- Penilaian Mandiri --}}
                                                    @if (in_array(Auth::user()->role->jenis, ['admin', 'opd', 'verifikator', 'penjamin', 'penilai']))
                                                        <td>
                                                            <strong>{{ number_format($totalNilaiOpd, 2) }}%</strong>
                                                        </td>
                                                    @endif

                                                    {{-- Verifikasi Verifikator (kosong, tidak ada total) - HANYA kriteria level --}}
                                                    @if (
                                                        $isKriteriaLevel &&
                                                            ($showVerifikasiKolom ?? false) &&
                                                            in_array(Auth::user()->role->jenis, ['admin', 'verifikator', 'penjamin', 'penilai']))
                                                        <td></td>
                                                    @endif

                                                    {{-- Verifikasi Penjamin (kosong, tidak ada total) - HANYA kriteria level --}}
                                                    @if (
                                                        $isKriteriaLevel &&
                                                            ($showVerifikasiKolom ?? false) &&
                                                            in_array(Auth::user()->role->jenis, ['admin', 'penjamin', 'penilai']))
                                                        <td></td>
                                                    @endif

                                                    {{-- Penjaminan Kualitas --}}
                                                    @if (in_array(Auth::user()->role->jenis, ['admin', 'penjamin', 'penilai']))
                                                        <td>
                                                            <strong>{{ number_format($totalNilaiPenjamin, 2) }}%</strong>
                                                        </td>
                                                    @endif

                                                    {{-- Evaluator --}}
                                                    @if (in_array(Auth::user()->role->jenis, ['admin', 'penilai']))
                                                        <td>
                                                            <strong>{{ number_format($totalNilaiPenilai, 2) }}%</strong>
                                                        </td>
                                                    @endif

                                                    {{-- Kolom Aksi: kosong --}}
                                                    <td></td>

                                                    {{-- Kolom Tracking: kosong (HANYA kriteria level) --}}
                                                    @if ($isKriteriaLevel && in_array(Auth::user()->role->jenis, ['admin', 'opd']))
                                                        <td></td>
                                                    @endif
                                                </tr>
                                            </tfoot>
                                        @endif
                                    </table>
                                    <!-- end table -->
                                </div>
                                <!-- end table responsive -->
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            @if ($kriteria_komponen_session)
                <div x-show="tab == 'bukti_dukung'" class="row" wire:key="bukti-dukung-tab">
                    <div class="col-12">
                        <div class="card">
                            {{-- <div class="card-header align-items-center d-flex">
                        <h4 class="card-title mb-0 flex-grow-1">Daftar Bukti Dukung</h4>
                    </div> --}}
                            <div class="card-body" style="padding-bottom: 0">
                                <ul class="nav nav-tabs nav-justified nav-border-top nav-border-top-primary"
                                    role="tablist">
                                    <li class="nav-item">
                                        <a @click="$wire.resetBuktiDukungId(); tab = 'bukti_dukung'"
                                            :class="tab === 'bukti_dukung' ? 'active' : ''" href="javascript:void(0);"
                                            class="nav-link py-3">
                                            <i class="ri-home-5-line align-middle me-1"></i>
                                            Bukti Dukung
                                        </a>
                                    </li>
                                    {{-- Tab Penilaian: Selalu tampil, tapi disabled jika penilaian di level bukti --}}
                                    <li class="nav-item">
                                        <a @click="@if ($this->penilaianDiKriteria) $wire.resetBuktiDukungId(); tab = 'penilaian' @endif"
                                            :class="tab === 'penilaian' ? 'active' : ''" href="javascript:void(0);"
                                            class="nav-link py-3 {{ !$this->penilaianDiKriteria ? 'disabled' : '' }}"
                                            style="{{ !$this->penilaianDiKriteria ? 'cursor: not-allowed; opacity: 0.5;' : '' }}">
                                            <i class="ri-user-line me-1 align-middle"></i>
                                            @if (Auth::user()->role->jenis == 'penjamin' || Auth::user()->role->jenis == 'penilai')
                                                Lembar Penilaian
                                            @elseif (Auth::user()->role->jenis == 'verifikator')
                                                Lembar Verifikasi
                                            @else
                                                Penilaian Mandiri
                                            @endif
                                            @if (!$this->penilaianDiKriteria)
                                                <small class="d-block text-muted"
                                                    style="font-size: 0.7rem;">(Penilaian per
                                                    Bukti)</small>
                                            @endif
                                        </a>
                                    </li>
                                </ul>
                            </div>
                            <div class="card-header d-flex align-items-center justify-content-between gap-3">
                                @if ($this->kriteriaKomponen)
                                    <div class="flex-grow-1" style="min-width: 0; max-width: calc(100% - 120px);">
                                        <p class="mb-1 text-dark fw-semibold"
                                            style="word-wrap: break-word; overflow-wrap: break-word;">Kriteria
                                            Komponen:
                                            {{ $this->kriteriaKomponen->kode }}
                                            -
                                            {{ $this->kriteriaKomponen->nama }}
                                            <span class="badge text-bg-primary ms-2">
                                                ( Bobot: {{ number_format($this->bobotKriteria, 2) }}% )
                                            </span>
                                            {{-- @php
                                        $nilaiRataRata = $this->kriteriaKomponen->getNilaiRataRata($opd_id);
                                    @endphp
                                    <span class="badge badge-soft-primary fs-6 ms-2">(Rata-rata:
                                        {{ number_format($nilaiRataRata, 2) }})</span> --}}
                                        </p>
                                        <p class="mb-0 text-muted" style="font-size: 0.875rem;">
                                            <span class="me-3"><small>Jenis Penilaian: <span
                                                        class="fw-medium">{{ $this->kriteriaKomponen->jenis_nilai->nama }}</span></small></span>
                                            <span><small>Metode Penilaian: <span
                                                        class="fw-medium">{{ $this->kriteriaKomponen->penilaian_di == 'bukti' ? 'Bukti Dukung' : 'Kriteria Komponen' }}
                                                    </span></small></span>
                                        </p>
                                    </div>
                                @else
                                    <div class="flex-grow-1">
                                        <p class="mb-0 text-muted fst-italic">Pilih kriteria komponen terlebih dahulu
                                        </p>
                                    </div>
                                @endif

                                <!-- Buttons with Label -->
                                <div class="flex-shrink-0" style="min-width: 110px;">
                                    <button wire:click="navigateBack" type="button"
                                        class="btn btn-sm btn-soft-primary btn-label waves-effect waves-light"><i
                                            class=" ri-arrow-go-back-line label-icon align-middle fs-16 me-2"></i>
                                        Kembali</button>
                                </div>
                            </div>
                            <div class="card-body">
                                @if ($this->kriteriaKomponen)
                                    <div class="live-preview">
                                        <div class="table-responsive">
                                            <table class="table align-middle mb-0" style="table-layout: fixed;"
                                                wire:key="table-bukti-{{ $kriteria_komponen_session }}">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th scope="col" style="width: 5%;">No</th>
                                                        <th scope="col"
                                                            style="width: {{ !$this->penilaianDiKriteria ? '35%' : '40%' }};">
                                                            Bukti Dukung</th>
                                                        <th scope="col" style="width: 8%;">Bobot</th>

                                                        @if (!$this->penilaianDiKriteria)
                                                            {{-- Tampilkan kolom nilai kondisional per role --}}
                                                            @if (in_array(Auth::user()->role->jenis, ['admin', 'opd', 'verifikator', 'penjamin', 'penilai']))
                                                                <th scope="col" style="width: 10%;">Penilaian
                                                                    <br>Mandiri
                                                                </th>
                                                            @endif

                                                            @if (in_array(Auth::user()->role->jenis, ['admin', 'verifikator', 'penjamin', 'penilai']))
                                                                <th scope="col" style="width: 8%;">
                                                                    Verifikasi<br>Verifikator</th>
                                                            @endif

                                                            @if (in_array(Auth::user()->role->jenis, ['admin', 'penjamin', 'penilai']))
                                                                <th scope="col" style="width: 8%;">
                                                                    Verifikasi<br>Evaluator</th>
                                                            @endif

                                                            @if (in_array(Auth::user()->role->jenis, ['admin', 'penjamin', 'penilai']))
                                                                <th scope="col" style="width: 10%;">Evaluator
                                                                </th>
                                                            @endif

                                                            @if (in_array(Auth::user()->role->jenis, ['admin', 'penilai']))
                                                                <th scope="col" style="width: 10%;">
                                                                    Penjamin<br>Kualitas</th>
                                                            @endif
                                                        @endif

                                                        <th scope="col"
                                                            style="width: {{ !$this->penilaianDiKriteria ? '12%' : '40%' }};">
                                                            Aksi</th>

                                                        {{-- Kolom Tracking hanya untuk mode bukti (admin dan OPD) --}}
                                                        @if (!$this->penilaianDiKriteria && in_array(Auth::user()->role->jenis, ['admin', 'opd']))
                                                            <th scope="col" style="width: 5%;">Tracking</th>
                                                        @endif
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @php
                                                        $totalNilaiOpd = 0;
                                                        $totalNilaiVerifikator = 0;
                                                        $totalNilaiPenjamin = 0;
                                                        $totalNilaiPenilai = 0;
                                                    @endphp
                                                    @foreach ($this->buktiDukungList as $index => $bukti_dukung)
                                                        @php
                                                            // Hitung nilai per bukti
                                                            $nilaiOpd = 0;
                                                            $nilaiVerifikator = 0;
                                                            $nilaiPenjamin = 0;
                                                            $nilaiPenilai = 0;

                                                            if (
                                                                $bukti_dukung->penilaian_opd &&
                                                                $bukti_dukung->penilaian_opd->tingkatan_nilai
                                                            ) {
                                                                $nilaiOpd =
                                                                    $bukti_dukung->penilaian_opd->tingkatan_nilai
                                                                        ->bobot * $this->bobotPerBukti;
                                                            }

                                                            if ($bukti_dukung->penilaian_verifikator) {
                                                                $nilaiVerifikator = $bukti_dukung->penilaian_verifikator
                                                                    ->is_verified
                                                                    ? 1
                                                                    : 0;
                                                            }

                                                            if (
                                                                $bukti_dukung->penilaian_penjamin &&
                                                                $bukti_dukung->penilaian_penjamin->tingkatan_nilai
                                                            ) {
                                                                $nilaiPenjamin =
                                                                    $bukti_dukung->penilaian_penjamin->tingkatan_nilai
                                                                        ->bobot * $this->bobotPerBukti;
                                                            }

                                                            if (
                                                                $bukti_dukung->penilaian_penilai &&
                                                                $bukti_dukung->penilaian_penilai->tingkatan_nilai
                                                            ) {
                                                                $nilaiPenilai =
                                                                    $bukti_dukung->penilaian_penilai->tingkatan_nilai
                                                                        ->bobot * $this->bobotPerBukti;
                                                            }

                                                            // Akumulasi total
                                                            $totalNilaiOpd += $nilaiOpd;
                                                            $totalNilaiPenjamin += $nilaiPenjamin;
                                                            $totalNilaiPenilai += $nilaiPenilai;

                                                            // Cek apakah bukti dukung ini ditolak
                                                            $hasRejectionBukti = $this->hasRejection(
                                                                $bukti_dukung,
                                                                'bukti',
                                                            );
                                                        @endphp
                                                        <tr wire:key="bukti-row-{{ $bukti_dukung->id }}">
                                                            <th scope="row"><a
                                                                    class="fw-medium">{{ $index + 1 }}</a></th>
                                                            <td>
                                                                <span class="position-relative">
                                                                    {{ $bukti_dukung->nama }}
                                                                    @if ($hasRejectionBukti)
                                                                        <span
                                                                            class="position-absolute top-0 start-100 translate-middle badge-pulsate ms-2"></span>
                                                                    @endif
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <span>{{ number_format($this->bobotPerBukti, 2) }}%</span>
                                                            </td>
                                                            @if (!$this->penilaianDiKriteria)
                                                                {{-- Kolom Penilaian Mandiri --}}
                                                                @if (in_array(Auth::user()->role->jenis, ['admin', 'opd', 'verifikator', 'penjamin', 'penilai']))
                                                                    <td>
                                                                        @if ($bukti_dukung->penilaian_opd && $bukti_dukung->penilaian_opd->tingkatan_nilai)
                                                                            <span>
                                                                                {{ number_format($nilaiOpd, 2) }}%
                                                                            </span>
                                                                        @else
                                                                            <span class="text-muted">-</span>
                                                                        @endif
                                                                    </td>
                                                                @endif

                                                                {{-- Kolom Verifikasi Verifikator --}}
                                                                @if (in_array(Auth::user()->role->jenis, ['admin', 'verifikator', 'penjamin', 'penilai']))
                                                                    <td>
                                                                        @if ($bukti_dukung->penilaian_verifikator && $bukti_dukung->penilaian_verifikator->is_verified === true)
                                                                            <i
                                                                                class="ri-checkbox-circle-fill text-success fs-18"></i>
                                                                        @else
                                                                            <span class="text-muted">-</span>
                                                                        @endif
                                                                    </td>
                                                                @endif

                                                                {{-- Kolom Verifikasi Penjamin --}}
                                                                @if (in_array(Auth::user()->role->jenis, ['admin', 'penjamin', 'penilai']))
                                                                    <td>
                                                                        @if ($bukti_dukung->penilaian_penjamin && $bukti_dukung->penilaian_penjamin->is_verified === true)
                                                                            <i
                                                                                class="ri-checkbox-circle-fill text-success fs-18"></i>
                                                                        @else
                                                                            <span class="text-muted">-</span>
                                                                        @endif
                                                                    </td>
                                                                @endif

                                                                {{-- Kolom Penjaminan Kualitas (Nilai saja) --}}
                                                                @if (in_array(Auth::user()->role->jenis, ['admin', 'penjamin', 'penilai']))
                                                                    <td>
                                                                        @if ($bukti_dukung->penilaian_penjamin && $bukti_dukung->penilaian_penjamin->tingkatan_nilai)
                                                                            <span>
                                                                                {{ number_format($nilaiPenjamin, 2) }}%
                                                                            </span>
                                                                        @else
                                                                            <span class="text-muted">-</span>
                                                                        @endif
                                                                    </td>
                                                                @endif

                                                                {{-- Kolom Evaluator (Nilai) --}}
                                                                @if (in_array(Auth::user()->role->jenis, ['admin', 'penilai']))
                                                                    <td>
                                                                        @if ($bukti_dukung->penilaian_penilai && $bukti_dukung->penilaian_penilai->tingkatan_nilai)
                                                                            <span>
                                                                                {{ number_format($nilaiPenilai, 2) }}%
                                                                            </span>
                                                                        @else
                                                                            <span class="text-muted">-</span>
                                                                        @endif
                                                                    </td>
                                                                @endif
                                                            @endif

                                                            {{-- Kolom Aksi --}}
                                                            <td>
                                                                @if (Auth::user()->role->jenis == 'opd' || Auth::user()->role->jenis == 'admin')
                                                                    {{-- OPD: Tampilkan 'Lihat' jika sudah ada file, 'Unggah' jika belum --}}
                                                                    <button
                                                                        @click="tab = 'penilaian'; menu = 'dokumen'"
                                                                        wire:click="setBuktiDukungId({{ $bukti_dukung->id }})"
                                                                        class="btn btn-sm btn-light add-btn">
                                                                        @if ($bukti_dukung->penilaian_opd && $bukti_dukung->penilaian_opd->link_file)
                                                                            <i
                                                                                class="ri-eye-line align-bottom me-1"></i>Lihat
                                                                        @elseif ($this->dalamRentangAkses)
                                                                            <i
                                                                                class="ri-upload-2-line align-bottom me-1"></i>Unggah
                                                                        @else
                                                                            <i
                                                                                class="ri-eye-line align-bottom me-1"></i>Lihat
                                                                        @endif
                                                                    </button>
                                                                @elseif (!$bukti_dukung->penilaian_opd || !$bukti_dukung->penilaian_opd->link_file)
                                                                    <span class="fst-italic text-muted">-</span>
                                                                @elseif (!$this->penilaianDiKriteria)
                                                                    {{-- Mode bukti: Verifikator/Penjamin/Penilai punya tombol --}}
                                                                    @if (Auth::user()->role->jenis == 'verifikator')
                                                                        <button
                                                                            @click="tab = 'penilaian'; menu = 'dokumen'"
                                                                            wire:click="setBuktiDukungId({{ $bukti_dukung->id }})"
                                                                            class="btn btn-sm btn-light add-btn"><i
                                                                                class="ri-file-edit-line align-bottom me-1"></i>Evaluasi</button>
                                                                    @elseif (Auth::user()->role->jenis == 'penjamin')
                                                                        <button
                                                                            @click="tab = 'penilaian'; menu = 'dokumen'"
                                                                            wire:click="setBuktiDukungId({{ $bukti_dukung->id }})"
                                                                            class="btn btn-sm btn-light add-btn"><i
                                                                                class="ri-file-edit-line align-bottom me-1"></i>Penilaian</button>
                                                                    @elseif (Auth::user()->role->jenis == 'penilai')
                                                                        <button
                                                                            @click="tab = 'penilaian'; menu = 'dokumen'"
                                                                            wire:click="setBuktiDukungId({{ $bukti_dukung->id }})"
                                                                            class="btn btn-sm btn-light add-btn"><i
                                                                                class="ri-file-edit-line align-bottom me-1"></i>Penilaian</button>
                                                                    @endif
                                                                @else
                                                                    {{-- Mode kriteria: Tidak ada tombol untuk Verifikator/Penjamin/Penilai --}}
                                                                    <span class="fst-italic text-muted">Lihat tab
                                                                        penilaian</span>
                                                                @endif
                                                            </td>
                                                            @if (!$this->penilaianDiKriteria && in_array(Auth::user()->role->jenis, ['admin', 'opd']))
                                                                {{-- Tombol tracking hanya untuk mode bukti --}}
                                                                <td>
                                                                    <button type="button"
                                                                        wire:click="showTracking({{ $bukti_dukung->id }})"
                                                                        class="btn btn-sm btn-primary btn-icon waves-effect waves-light"
                                                                        data-bs-toggle="modal"
                                                                        data-bs-target="#trackingModal"><i
                                                                            class="ri-eye-line"></i></button>
                                                                </td>
                                                            @endif
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                                @if (!$this->penilaianDiKriteria)
                                                    <tfoot class="table-light">
                                                        <tr>
                                                            <td colspan="3" class="text-end">
                                                                <strong>JUMLAH:</strong>
                                                            </td>

                                                            {{-- Penilaian Mandiri --}}
                                                            @if (in_array(Auth::user()->role->jenis, ['admin', 'opd', 'verifikator', 'penjamin', 'penilai']))
                                                                <td>
                                                                    <strong>{{ number_format($totalNilaiOpd, 2) }}%</strong>
                                                                </td>
                                                            @endif

                                                            {{-- Verifikasi Verifikator (kosong, tidak ada total) --}}
                                                            @if (in_array(Auth::user()->role->jenis, ['admin', 'verifikator', 'penjamin', 'penilai']))
                                                                <td></td>
                                                            @endif

                                                            {{-- Verifikasi Penjamin (kosong, tidak ada total) --}}
                                                            @if (in_array(Auth::user()->role->jenis, ['admin', 'penjamin', 'penilai']))
                                                                <td></td>
                                                            @endif

                                                            {{-- Penjaminan Kualitas --}}
                                                            @if (in_array(Auth::user()->role->jenis, ['admin', 'penjamin', 'penilai']))
                                                                <td>
                                                                    <strong>{{ number_format($totalNilaiPenjamin, 2) }}%</strong>
                                                                </td>
                                                            @endif

                                                            {{-- Evaluator --}}
                                                            @if (in_array(Auth::user()->role->jenis, ['admin', 'penilai']))
                                                                <td>
                                                                    <strong>{{ number_format($totalNilaiPenilai, 2) }}%</strong>
                                                                </td>
                                                            @endif

                                                            {{-- Kolom Aksi: kosong --}}
                                                            <td></td>

                                                            {{-- Kolom Tracking: kosong --}}
                                                            @if (!$this->penilaianDiKriteria && in_array(Auth::user()->role->jenis, ['admin', 'opd']))
                                                                <td></td>
                                                            @endif
                                                        </tr>
                                                    </tfoot>
                                                @endif
                                            </table>
                                            <!-- end table -->
                                        </div>
                                        <!-- end table responsive -->
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            @if ($kriteria_komponen_session)
                <div x-show="tab == 'penilaian'" class="row" wire:key="penilaian-tab">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body" style="padding-bottom: 0">
                                <ul class="nav nav-tabs nav-justified nav-border-top nav-border-top-primary"
                                    role="tablist">
                                    <li class="nav-item">
                                        <a @click="$wire.resetBuktiDukungId(); tab = 'bukti_dukung'"
                                            :class="tab === 'bukti_dukung' ? 'active' : ''" href="javascript:void(0);"
                                            class="nav-link py-3">
                                            <i class="ri-home-5-line align-middle me-1"></i>
                                            Bukti Dukung
                                        </a>
                                    </li>
                                    {{-- Tab Penilaian: Selalu tampil, tapi disabled jika penilaian di bukti --}}
                                    <li class="nav-item">
                                        <a @click="@if ($this->penilaianDiKriteria) $wire.resetBuktiDukungId(); tab = 'penilaian' @endif"
                                            :class="tab === 'penilaian' ? 'active' : ''" href="javascript:void(0);"
                                            class="nav-link py-3 {{ !$this->penilaianDiKriteria ? 'disabled' : '' }}"
                                            style="{{ !$this->penilaianDiKriteria ? 'cursor: not-allowed; opacity: 0.5;' : '' }}">
                                            <i class="ri-user-line me-1 align-middle"></i>
                                            @if (Auth::user()->role->jenis == 'penjamin' || Auth::user()->role->jenis == 'penilai')
                                                Lembar Penilaian
                                            @elseif (Auth::user()->role->jenis == 'verifikator')
                                                Lembar Verifikasi
                                            @else
                                                Penilaian Mandiri
                                            @endif
                                            @if (!$this->penilaianDiKriteria)
                                                <small class="d-block text-muted">(Penilaian per Bukti)</small>
                                            @endif
                                        </a>
                                    </li>
                                </ul>
                            </div>
                            <div class="card-header align-items-center d-flex">
                                <p class="mb-sm-0 text-dark fw-semibold">
                                    @if ($this->kriteriaKomponen)
                                        @if ($this->penilaianDiKriteria)
                                            Kriteria Komponen: {{ $this->kriteriaKomponen->kode }} -
                                            {{ $this->kriteriaKomponen->nama }}
                                        @else
                                            Bukti Dukung:
                                            {{ $this->selectedBuktiDukung?->nama ?? 'Pilih bukti dukung terlebih dahulu' }}
                                        @endif
                                    @else
                                        <span class="text-muted fst-italic">Pilih kriteria komponen terlebih
                                            dahulu</span>
                                    @endif
                                </p>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    {{-- <div class="col-lg-3">

                            </div>
                            <div class="col-lg-9">
                            </div> --}}

                                    <div class="col-md-2">
                                        <div class="nav flex-column nav-pills text-center" id="v-pills-tab"
                                            role="tablist" aria-orientation="vertical">
                                            {{-- Menu Dokumen: Semua role bisa lihat --}}
                                            <a @click="menu = 'dokumen'" :class="menu === 'dokumen' ? 'active' : ''"
                                                href="javascript:void(0)" class="nav-link mb-2"><i
                                                    class="ri-file-line me-1 align-middle"></i>Dokumen</a>

                                            {{-- Menu Unggah: Hanya admin dan opd, dan dalam rentang akses --}}
                                            {{-- Di mode kriteria + tab penilaian: Hide menu unggah karena user harus pilih bukti dukung dulu --}}
                                            @if (in_array(Auth::user()->role->jenis, ['admin', 'opd']) && $this->dalamRentangAkses && $bukti_dukung_id)
                                                <a @click="menu = 'unggah'"
                                                    :class="menu === 'unggah' ? 'active' : ''"
                                                    href="javascript:void(0)" class="nav-link mb-2"><i
                                                        class="ri-upload-line me-1 align-middle"></i>Unggah</a>
                                            @endif

                                            {{-- Menu Verifikasi: Validasi upload bukti dukung --}}
                                            @if (in_array(Auth::user()->role->jenis, ['admin', 'verifikator', 'penjamin']) && $this->dalamRentangAkses)
                                                @php $canDoPenilaian = $this->canDoPenilaian; @endphp
                                                <a @click="@if ($canDoPenilaian['allowed']) menu = 'verifikasi' @endif"
                                                    :class="menu === 'verifikasi' ? 'active' : ''"
                                                    href="javascript:void(0)"
                                                    class="nav-link mb-2 {{ !$canDoPenilaian['allowed'] ? 'disabled' : '' }}"
                                                    style="{{ !$canDoPenilaian['allowed'] ? 'cursor: not-allowed; opacity: 0.5;' : '' }}"
                                                    title="{{ !$canDoPenilaian['allowed'] ? $canDoPenilaian['message'] : '' }}">
                                                    <i class="ri-check-double-line me-1 align-middle"></i>Verifikasi
                                                </a>
                                            @endif

                                            {{-- Menu Penilaian: Validasi upload bukti dukung --}}
                                            @if (in_array(Auth::user()->role->jenis, ['admin', 'penilai', 'opd', 'penjamin']) && $this->dalamRentangAkses)
                                                @php $canDoPenilaian = $this->canDoPenilaian; @endphp
                                                <a @click="@if ($canDoPenilaian['allowed']) menu = 'penilaian' @endif"
                                                    :class="menu === 'penilaian' ? 'active' : ''"
                                                    href="javascript:void(0)"
                                                    class="nav-link mb-2 {{ !$canDoPenilaian['allowed'] ? 'disabled' : '' }}"
                                                    style="{{ !$canDoPenilaian['allowed'] ? 'cursor: not-allowed; opacity: 0.5;' : '' }}"
                                                    title="{{ !$canDoPenilaian['allowed'] ? $canDoPenilaian['message'] : '' }}">
                                                    <i class="ri-file-edit-line me-1 align-middle"></i>Penilaian
                                                </a>
                                            @endif

                                            {{-- Menu History: Semua role bisa lihat --}}
                                            <a @click="menu = 'history'" :class="menu === 'history' ? 'active' : ''"
                                                href="javascript:void(0)" class="nav-link mb-2">
                                                <i class="ri-history-line me-1 align-middle"></i>History
                                            </a>
                                        </div>
                                    </div><!-- end col -->
                                    <div class="col-md-10">
                                        <div class="tab-content mt-4 mt-md-0">
                                            <div x-show="menu === 'dokumen'" aria-labelledby="v-pills-home-tab">
                                                @if ($this->penilaianDiKriteria)
                                                    {{-- Mode Kriteria: Cek apakah ada bukti_dukung_id --}}
                                                    @if ($bukti_dukung_id)
                                                        {{-- Ada bukti_dukung_id: Tampilkan hanya dokumen dari bukti dukung spesifik --}}
                                                        @php
                                                            // Cache static role OPD untuk performa
                                                            static $roleOpdCached = null;
                                                            if ($roleOpdCached === null) {
                                                                $roleOpdCached = \App\Models\Role::where(
                                                                    'jenis',
                                                                    'opd',
                                                                )->first();
                                                            }

                                                            $selectedBukti = \App\Models\BuktiDukung::find(
                                                                $bukti_dukung_id,
                                                            );
                                                            $penilaianOpd = null;

                                                            if ($selectedBukti && $opd_session && $roleOpdCached) {
                                                                $penilaianOpd = \App\Models\Penilaian::where(
                                                                    'bukti_dukung_id',
                                                                    $bukti_dukung_id,
                                                                )
                                                                    ->where('opd_id', $opd_session)
                                                                    ->where('role_id', $roleOpdCached->id)
                                                                    ->whereNotNull('link_file')
                                                                    ->first();
                                                            }
                                                        @endphp
                                                        @if ($selectedBukti)
                                                            <div class="mb-4">
                                                                <h6 class="fw-bold text-primary mb-3">
                                                                    <i
                                                                        class="ri-folder-2-line me-1"></i>{{ $selectedBukti->nama }}
                                                                </h6>
                                                                @if ($penilaianOpd && $penilaianOpd->link_file)
                                                                    @php
                                                                        $files = $penilaianOpd->link_file;
                                                                        if (!is_array($files)) {
                                                                            $files = [];
                                                                        }
                                                                    @endphp

                                                                    @if (count($files) > 0)
                                                                        {{-- Info metadata dokumen --}}
                                                                        <div class="alert alert-info mb-3">
                                                                            @if ($penilaianOpd->is_perubahan)
                                                                                <span class="badge bg-warning mb-2">
                                                                                    <i
                                                                                        class="ri-refresh-line me-1"></i>Dokumen
                                                                                    Perubahan
                                                                                </span>
                                                                            @endif
                                                                            @if ($penilaianOpd->keterangan)
                                                                                <p class="mb-1">
                                                                                    <strong>Keterangan:</strong>
                                                                                    {{ $penilaianOpd->keterangan }}
                                                                                </p>
                                                                            @endif
                                                                            <p class="mb-0 small text-muted">
                                                                                <i class="ri-time-line me-1"></i>
                                                                                Diunggah:
                                                                                {{ $penilaianOpd->created_at->format('d M Y H:i') }}
                                                                            </p>
                                                                        </div>

                                                                        {{-- Display files --}}
                                                                        @if (count($files) > 1)
                                                                            <ul class="nav nav-tabs nav-bordered mb-3"
                                                                                role="tablist">
                                                                                @foreach ($files as $fileIndex => $file)
                                                                                    <li class="nav-item"
                                                                                        role="presentation">
                                                                                        <a href="#selected-bukti-file-{{ $fileIndex }}"
                                                                                            data-bs-toggle="tab"
                                                                                            class="nav-link {{ $fileIndex === 0 ? 'active' : '' }}"
                                                                                            role="tab">
                                                                                            <i
                                                                                                class="ri-file-line me-1"></i>
                                                                                            Dokumen
                                                                                            {{ $fileIndex + 1 }}
                                                                                        </a>
                                                                                    </li>
                                                                                @endforeach
                                                                            </ul>

                                                                            <div class="tab-content mb-3">
                                                                                @foreach ($files as $fileIndex => $file)
                                                                                    <div class="tab-pane {{ $fileIndex === 0 ? 'show active' : '' }}"
                                                                                        id="selected-bukti-file-{{ $fileIndex }}"
                                                                                        role="tabpanel">
                                                                                        @if (str_ends_with(strtolower($file['path'] ?? ''), '.pdf'))
                                                                                            <embed
                                                                                                src="{{ asset('storage/' . $file['path']) }}"
                                                                                                type="application/pdf"
                                                                                                width="100%"
                                                                                                height="500" />
                                                                                        @else
                                                                                            <img src="{{ asset('storage/' . ($file['path'] ?? '')) }}"
                                                                                                class="img-fluid"
                                                                                                alt="{{ $file['original_name'] ?? 'Dokumen' }}" />
                                                                                        @endif
                                                                                    </div>
                                                                                @endforeach
                                                                            </div>
                                                                        @else
                                                                            {{-- Single file --}}
                                                                            @php $file = $files[0]; @endphp
                                                                            <div class="mb-3">
                                                                                @if (str_ends_with(strtolower($file['path'] ?? ''), '.pdf'))
                                                                                    <embed
                                                                                        src="{{ asset('storage/' . $file['path']) }}"
                                                                                        type="application/pdf"
                                                                                        width="100%"
                                                                                        height="500" />
                                                                                @else
                                                                                    <img src="{{ asset('storage/' . ($file['path'] ?? '')) }}"
                                                                                        class="img-fluid"
                                                                                        alt="{{ $file['original_name'] ?? 'Dokumen' }}" />
                                                                                @endif
                                                                            </div>
                                                                        @endif
                                                                    @else
                                                                        <p class="text-muted fst-italic ms-3">Belum ada
                                                                            dokumen
                                                                        </p>
                                                                    @endif
                                                                @else
                                                                    <p class="text-muted fst-italic ms-3">Belum ada
                                                                        dokumen</p>
                                                                @endif
                                                            </div>
                                                        @else
                                                            <div class="alert alert-warning text-center">
                                                                <i class="ri-alert-line fs-3"></i>
                                                                <p class="mb-0">Bukti dukung tidak ditemukan.</p>
                                                            </div>
                                                        @endif
                                                    @else
                                                        {{-- Tidak ada bukti_dukung_id: Tampilkan semua dokumen dari semua bukti dukung (grouped) --}}
                                                        @php $semuaBuktiDukung = $this->semuaBuktiDukungDenganDokumen(); @endphp
                                                        @if ($semuaBuktiDukung->isNotEmpty())
                                                            @foreach ($semuaBuktiDukung as $buktiItem)
                                                                <div
                                                                    class="mb-4 pb-3 {{ !$loop->last ? 'border-bottom' : '' }}">
                                                                    <h6 class="fw-bold text-primary mb-3">
                                                                        <i
                                                                            class="ri-folder-2-line me-1"></i>{{ $buktiItem->nama }}
                                                                    </h6>
                                                                    @if ($buktiItem->penilaian_opd && $buktiItem->penilaian_opd->link_file)
                                                                        @php
                                                                            // link_file sudah auto-decoded karena cast di model
                                                                            $files =
                                                                                $buktiItem->penilaian_opd->link_file;
                                                                            if (!is_array($files)) {
                                                                                $files = [];
                                                                            }
                                                                        @endphp

                                                                        @if (count($files) > 0)
                                                                            {{-- Info metadata dokumen --}}
                                                                            <div class="alert alert-info mb-3">
                                                                                @if ($buktiItem->penilaian_opd->is_perubahan)
                                                                                    <span
                                                                                        class="badge bg-warning mb-2">
                                                                                        <i
                                                                                            class="ri-refresh-line me-1"></i>Dokumen
                                                                                        Perubahan
                                                                                    </span>
                                                                                @endif
                                                                                @if ($buktiItem->penilaian_opd->keterangan)
                                                                                    <p class="mb-1">
                                                                                        <strong>Keterangan:</strong>
                                                                                        {{ $buktiItem->penilaian_opd->keterangan }}
                                                                                    </p>
                                                                                @endif
                                                                                <p class="mb-0 small text-muted">
                                                                                    <i class="ri-time-line me-1"></i>
                                                                                    Diunggah:
                                                                                    {{ $buktiItem->penilaian_opd->created_at->format('d M Y H:i') }}
                                                                                </p>
                                                                            </div>

                                                                            {{-- Tabs untuk multiple files --}}
                                                                            @if (count($files) > 1)
                                                                                <ul class="nav nav-tabs nav-bordered mb-3"
                                                                                    role="tablist">
                                                                                    @foreach ($files as $fileIndex => $file)
                                                                                        @php
                                                                                            $tabId =
                                                                                                'bukti-' .
                                                                                                $buktiItem->id .
                                                                                                '-file-' .
                                                                                                $fileIndex;
                                                                                        @endphp
                                                                                        <li class="nav-item"
                                                                                            role="presentation">
                                                                                            <a href="#{{ $tabId }}"
                                                                                                data-bs-toggle="tab"
                                                                                                aria-expanded="{{ $fileIndex === 0 ? 'true' : 'false' }}"
                                                                                                class="nav-link {{ $fileIndex === 0 ? 'active' : '' }}"
                                                                                                role="tab"
                                                                                                title="{{ $file['original_name'] ?? 'Dokumen ' . ($fileIndex + 1) }}">
                                                                                                <i
                                                                                                    class="ri-file-line me-1"></i>
                                                                                                Dokumen
                                                                                                {{ $fileIndex + 1 }}
                                                                                            </a>
                                                                                        </li>
                                                                                    @endforeach
                                                                                </ul>

                                                                                <div class="tab-content mb-3">
                                                                                    @foreach ($files as $fileIndex => $file)
                                                                                        @php
                                                                                            $tabId =
                                                                                                'bukti-' .
                                                                                                $buktiItem->id .
                                                                                                '-file-' .
                                                                                                $fileIndex;
                                                                                        @endphp
                                                                                        <div class="tab-pane {{ $fileIndex === 0 ? 'show active' : '' }}"
                                                                                            id="{{ $tabId }}"
                                                                                            role="tabpanel">
                                                                                            @if (str_ends_with(strtolower($file['path'] ?? ''), '.pdf'))
                                                                                                <embed
                                                                                                    src="{{ asset('storage/' . $file['path']) }}"
                                                                                                    type="application/pdf"
                                                                                                    width="100%"
                                                                                                    height="500" />
                                                                                            @else
                                                                                                <img src="{{ asset('storage/' . ($file['path'] ?? '')) }}"
                                                                                                    class="img-fluid"
                                                                                                    alt="{{ $file['original_name'] ?? 'Dokumen' }}" />
                                                                                            @endif
                                                                                        </div>
                                                                                    @endforeach
                                                                                </div>
                                                                            @else
                                                                                {{-- Single file - tampilkan langsung tanpa tab --}}
                                                                                @php $file = $files[0]; @endphp
                                                                                <div class="mb-3">
                                                                                    @if (str_ends_with(strtolower($file['path'] ?? ''), '.pdf'))
                                                                                        <embed
                                                                                            src="{{ asset('storage/' . $file['path']) }}"
                                                                                            type="application/pdf"
                                                                                            width="100%"
                                                                                            height="500" />
                                                                                    @else
                                                                                        <img src="{{ asset('storage/' . ($file['path'] ?? '')) }}"
                                                                                            class="img-fluid"
                                                                                            alt="{{ $file['original_name'] ?? 'Dokumen' }}" />
                                                                                    @endif
                                                                                </div>
                                                                            @endif
                                                                        @else
                                                                            <p class="text-muted fst-italic ms-3">Belum
                                                                                ada
                                                                                dokumen
                                                                            </p>
                                                                        @endif
                                                                    @else
                                                                        <p class="text-muted fst-italic ms-3">Belum ada
                                                                            dokumen
                                                                        </p>
                                                                    @endif
                                                                </div>
                                                            @endforeach
                                                        @else
                                                            <div class="alert alert-warning text-center">
                                                                <i class="ri-alert-line fs-3"></i>
                                                                <p class="mb-0">Belum ada bukti dukung yang tersedia.
                                                                </p>
                                                            </div>
                                                        @endif
                                                    @endif
                                                @else
                                                    {{-- Mode Bukti: Tampilkan dokumen dari single bukti dukung yang dipilih --}}
                                                    @if ($this->selectedFileBuktiDukung)
                                                        {{-- Info keterangan dan status perubahan --}}
                                                        @php
                                                            $penilaianOpdRecord = \App\Models\Penilaian::where(
                                                                'bukti_dukung_id',
                                                                $bukti_dukung_id,
                                                            )
                                                                ->where('opd_id', $opd_session)
                                                                ->where('role_id', function ($query) {
                                                                    $query
                                                                        ->select('id')
                                                                        ->from('role')
                                                                        ->where('jenis', 'opd')
                                                                        ->limit(1);
                                                                })
                                                                ->whereNotNull('link_file')
                                                                ->first();
                                                        @endphp
                                                        @if ($penilaianOpdRecord)
                                                            <div
                                                                class="alert alert-info d-flex justify-content-between align-items-start mb-3">
                                                                <div>
                                                                    @if ($penilaianOpdRecord->is_perubahan)
                                                                        <span class="badge bg-warning mb-2">
                                                                            <i class="ri-refresh-line me-1"></i>Dokumen
                                                                            Perubahan
                                                                        </span>
                                                                    @endif
                                                                    @if ($penilaianOpdRecord->keterangan)
                                                                        <p class="mb-0"><strong>Keterangan:</strong>
                                                                            {{ $penilaianOpdRecord->keterangan }}</p>
                                                                    @endif
                                                                    <p class="mb-0 small text-muted">Diunggah:
                                                                        {{ $penilaianOpdRecord->created_at->format('d M Y H:i') }}
                                                                    </p>
                                                                </div>
                                                                @if (in_array(Auth::user()->role->jenis, ['admin', 'opd']) && $this->dalamRentangAkses)
                                                                    <button wire:click="deleteFileBuktiDukung"
                                                                        wire:confirm="Yakin ingin menghapus semua dokumen? Tindakan ini tidak dapat dibatalkan."
                                                                        class="btn btn-sm btn-danger">
                                                                        <i class="ri-delete-bin-line me-1"></i>Hapus
                                                                        Dokumen
                                                                    </button>
                                                                @endif
                                                            </div>
                                                        @endif

                                                        @if (count($this->selectedFileBuktiDukung) > 1)
                                                            {{-- Multiple files: show tabs --}}
                                                            <ul class="nav nav-tabs nav-bordered mb-3" role="tablist">
                                                                @foreach ($this->selectedFileBuktiDukung as $index => $file)
                                                                    <li class="nav-item" role="presentation">
                                                                        <a href="#dokumen-file-{{ $index }}"
                                                                            data-bs-toggle="tab"
                                                                            aria-expanded="{{ $index === 0 ? 'true' : 'false' }}"
                                                                            class="nav-link {{ $index === 0 ? 'active' : '' }}"
                                                                            role="tab"
                                                                            title="{{ $file['original_name'] ?? 'Dokumen ' . ($index + 1) }}">
                                                                            <i class="ri-file-line me-1"></i>Dokumen
                                                                            {{ $index + 1 }}
                                                                        </a>
                                                                    </li>
                                                                @endforeach
                                                            </ul>
                                                            <div class="tab-content">
                                                                @foreach ($this->selectedFileBuktiDukung as $index => $file)
                                                                    <div class="tab-pane {{ $index === 0 ? 'show active' : '' }}"
                                                                        id="dokumen-file-{{ $index }}"
                                                                        role="tabpanel">
                                                                        @if (str_ends_with(strtolower($file['path']), '.pdf'))
                                                                            <embed
                                                                                src="{{ asset('storage/' . $file['path']) }}"
                                                                                type="application/pdf" width="100%"
                                                                                height="600" />
                                                                        @else
                                                                            <img src="{{ asset('storage/' . $file['path']) }}"
                                                                                class="img-fluid"
                                                                                alt="{{ $file['original_name'] }}" />
                                                                        @endif
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        @else
                                                            {{-- Single file --}}
                                                            @php $file = $this->selectedFileBuktiDukung[0]; @endphp
                                                            @if (str_ends_with(strtolower($file['path']), '.pdf'))
                                                                <embed src="{{ asset('storage/' . $file['path']) }}"
                                                                    type="application/pdf" width="100%"
                                                                    height="600" />
                                                            @else
                                                                <img src="{{ asset('storage/' . $file['path']) }}"
                                                                    class="img-fluid"
                                                                    alt="{{ $file['original_name'] }}" />
                                                            @endif
                                                        @endif
                                                    @else
                                                        <div class="alert alert-warning text-center">
                                                            <i class="ri-alert-line fs-3"></i>
                                                            <p class="mb-0">Tidak ada dokumen yang tersedia. Silakan
                                                                unggah
                                                                dokumen terlebih dahulu.</p>
                                                        </div>
                                                    @endif
                                                @endif
                                            </div>
                                            <div x-show="menu === 'unggah'" aria-labelledby="v-pills-profile-tab">
                                                <div class="mb-2" x-data="{ isUploading: false, hasFiles: false }"
                                                    @filepond-upload-started.window="isUploading = true; hasFiles = true"
                                                    @filepond-upload-completed.window="isUploading = false"
                                                    @filepond-upload-file-removed.window="if ($event.detail.isEmpty) { hasFiles = false; isUploading = false }">
                                                    <div class="ms-3">
                                                        <x-filepond::upload wire:model="file_bukti_dukung" multiple />

                                                        <div class="mb-3 mt-3">
                                                            <label for="keterangan_upload" class="form-label">
                                                                Keterangan
                                                                <span x-show="isUploading" class="text-warning small">
                                                                    <i class="ri-loader-4-line"></i> Mengunggah file...
                                                                </span>
                                                            </label>
                                                            <textarea wire:model="keterangan_upload" class="form-control" id="keterangan_upload" rows="3"
                                                                :disabled="isUploading" placeholder="Tambahkan keterangan atau catatan untuk dokumen yang diunggah..."></textarea>
                                                            @error('keterangan_upload')
                                                                <span
                                                                    class="text-danger small">{{ $message }}</span>
                                                            @enderror
                                                            <div x-show="isUploading" class="form-text text-warning">
                                                                <i class="ri-information-line"></i> Tunggu hingga
                                                                upload file
                                                                selesai
                                                            </div>
                                                        </div>

                                                        <div class="form-check form-switch mb-3">
                                                            <input wire:model="is_perubahan" type="checkbox"
                                                                class="form-check-input" id="is_perubahan_switch"
                                                                :disabled="isUploading" role="switch">
                                                            <label class="form-check-label" for="is_perubahan_switch">
                                                                <i class="ri-refresh-line me-1"></i>Tandai sebagai
                                                                Perubahan
                                                            </label>
                                                            <div class="form-text">Centang jika dokumen ini merupakan
                                                                perbaikan/perubahan dari dokumen sebelumnya</div>
                                                        </div>

                                                        <div class="form-check form-switch mb-3">
                                                            <input wire:model="ganti_semua_dokumen" type="checkbox"
                                                                class="form-check-input" id="ganti_semua_switch"
                                                                :disabled="isUploading" role="switch">
                                                            <label class="form-check-label" for="ganti_semua_switch">
                                                                <i class="ri-file-replace-line me-1"></i>Ganti Semua
                                                                Dokumen
                                                            </label>
                                                            <div class="form-text">Centang jika ingin menghapus dokumen
                                                                lama
                                                                dan menggantinya dengan dokumen baru. Jika tidak
                                                                dicentang,
                                                                dokumen baru akan ditambahkan tanpa menghapus yang lama.
                                                            </div>
                                                        </div>

                                                        <button wire:click="uploadBuktiDukung"
                                                            class="btn btn-primary mt-2"
                                                            :disabled="isUploading || !hasFiles">
                                                            <i class="ri-upload-line me-1"></i>
                                                            <span x-show="!isUploading">Simpan</span>
                                                            <span x-show="isUploading">
                                                                <span class="spinner-border spinner-border-sm me-1"
                                                                    role="status" aria-hidden="true"></span>
                                                                Mengunggah...
                                                            </span>
                                                        </button>
                                                        @if ($errors->any())
                                                            <div class="alert alert-danger mt-3">
                                                                <ul class="mb-0">
                                                                    @foreach ($errors->all() as $error)
                                                                        <li>{{ $error }}</li>
                                                                    @endforeach
                                                                </ul>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                            <div x-show="menu === 'penilaian'"
                                                aria-labelledby="tombol-penilaian-mandiri">
                                                <div>
                                                    @php $canDoPenilaian = $this->canDoPenilaian; @endphp
                                                    @if (!$canDoPenilaian['allowed'])
                                                        <div class="alert alert-warning" role="alert">
                                                            <i class="ri-alert-line me-2"></i>
                                                            <strong>Upload Bukti Dukung Diperlukan</strong>
                                                            <p class="mb-0 mt-2">{{ $canDoPenilaian['message'] }}</p>
                                                        </div>
                                                    @else
                                                        <div class="mb-3">
                                                            @if ($this->penilaianTersimpan && $this->penilaianTersimpan->tingkatan_nilai_id && !$is_editing_penilaian)
                                                                {{-- Preview Mode: Tampilkan nilai tersimpan --}}
                                                                <div class="card border-success">
                                                                    <div class="card-body">
                                                                        <div
                                                                            class="d-flex justify-content-between align-items-start mb-3">
                                                                            <h5 class="card-title mb-0">
                                                                                <i
                                                                                    class="ri-checkbox-circle-fill text-success me-2"></i>
                                                                                Penilaian Tersimpan
                                                                            </h5>
                                                                            <div>
                                                                                <button wire:click="editPenilaian"
                                                                                    class="btn btn-sm btn-warning me-1">
                                                                                    <i
                                                                                        class="ri-edit-line me-1"></i>Ubah
                                                                                </button>
                                                                                @if ($this->dalamRentangAkses)
                                                                                    <button wire:click="hapusNilai"
                                                                                        wire:confirm="Yakin ingin menghapus nilai penilaian? Keterangan dari upload dokumen akan tetap tersimpan."
                                                                                        class="btn btn-sm btn-danger">
                                                                                        <i
                                                                                            class="ri-delete-bin-line me-1"></i>Hapus
                                                                                    </button>
                                                                                @endif
                                                                            </div>
                                                                        </div>

                                                                        <div class="row align-items-center">
                                                                            <div class="col-auto">
                                                                                <div class="avatar-lg">
                                                                                    <div
                                                                                        class="avatar-title bg-success text-white fs-1 rounded">
                                                                                        {{ $this->penilaianTersimpan->tingkatan_nilai->kode_nilai ?? '-' }}
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                            <div class="col">
                                                                                <h4 class="mb-1">
                                                                                    {{ $this->penilaianTersimpan->tingkatan_nilai->kode_nilai ?? '-' }}
                                                                                </h4>
                                                                                <p class="text-muted mb-1">
                                                                                    <strong>Nilai:</strong>
                                                                                    {{ $this->penilaianTersimpan->tingkatan_nilai->bobot ?? 0 }}
                                                                                </p>
                                                                                @if ($this->penilaianTersimpan->tingkatan_nilai && $this->penilaianTersimpan->tingkatan_nilai->deskripsi)
                                                                                    <p class="text-muted mb-1">
                                                                                        <strong>Deskripsi:</strong>
                                                                                        {{ $this->penilaianTersimpan->tingkatan_nilai->deskripsi }}
                                                                                    </p>
                                                                                @endif
                                                                                @if ($this->penilaianTersimpan->keterangan)
                                                                                    <p class="text-muted mb-1">
                                                                                        <strong>Keterangan:</strong>
                                                                                        {{ $this->penilaianTersimpan->keterangan }}
                                                                                    </p>
                                                                                @endif
                                                                                <p class="text-muted small mb-0">
                                                                                    <i class="ri-time-line me-1"></i>
                                                                                    Disimpan pada:
                                                                                    {{ $this->penilaianTersimpan->created_at->format('d M Y H:i') }}
                                                                                </p>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            @else
                                                                {{-- Edit Mode / Belum Ada Penilaian --}}
                                                                @if ($is_editing_penilaian)
                                                                    <div
                                                                        class="alert alert-warning d-flex justify-content-between align-items-center mb-3">
                                                                        <div>
                                                                            <i class="ri-edit-box-line me-1"></i>
                                                                            <strong>MODE EDIT PENILAIAN</strong>
                                                                            <div class="small mt-1">Pilih tingkatan
                                                                                nilai baru
                                                                                untuk
                                                                                mengubah penilaian</div>
                                                                        </div>
                                                                        <button wire:click="batalEditPenilaian"
                                                                            class="btn btn-sm btn-secondary">
                                                                            <i class="ri-close-line me-1"></i>Batal
                                                                        </button>
                                                                    </div>
                                                                @endif

                                                                <h6 class="mb-3">Pilih Tingkatan Nilai</h6>
                                                                @if ($this->tingkatanNilaiList()->isNotEmpty())
                                                                    <div class="row">
                                                                        @foreach ($this->tingkatanNilaiList() as $tingkatan)
                                                                            @php
                                                                                $isSelected =
                                                                                    $tingkatan_nilai_id ==
                                                                                    $tingkatan->id;
                                                                            @endphp
                                                                            <div
                                                                                class="col-xxl-3 col-lg-4 col-md-6 mb-3">
                                                                                <div class="card card-body text-center
                                                                    {{ $isSelected ? 'border-primary' : '' }}"
                                                                                    style="cursor: pointer;"
                                                                                    wire:click="$set('tingkatan_nilai_id', {{ $tingkatan->id }})">
                                                                                    <div
                                                                                        class="avatar-sm mx-auto mb-3">
                                                                                        <div
                                                                                            class="avatar-title
                                                                            {{ $tingkatan_nilai_id == $tingkatan->id ? 'bg-primary text-white' : 'bg-soft-primary text-primary' }}
                                                                            fs-17 rounded">
                                                                                            {{ $tingkatan->kode_nilai }}
                                                                                        </div>
                                                                                    </div>
                                                                                    <p
                                                                                        class="card-text text-muted mb-0">
                                                                                        Nilai:
                                                                                        {{ $tingkatan->bobot }}</p>
                                                                                    @if ($tingkatan->deskripsi)
                                                                                        <p
                                                                                            class="card-text text-muted small">
                                                                                            {{ Str::limit($tingkatan->deskripsi, 50) }}
                                                                                        </p>
                                                                                    @endif
                                                                                </div>
                                                                            </div>
                                                                        @endforeach
                                                                    </div>

                                                                    <div class="mt-3">
                                                                        <button type="button" class="btn btn-primary"
                                                                            wire:click="simpanPenilaian"
                                                                            {{ $tingkatan_nilai_id ? '' : 'disabled' }}>
                                                                            <i class="ri-save-line me-1"></i>
                                                                            {{ $is_editing_penilaian ? 'Update Penilaian' : 'Simpan Penilaian' }}
                                                                        </button>
                                                                        @if ($is_editing_penilaian)
                                                                            <button type="button"
                                                                                class="btn btn-secondary"
                                                                                wire:click="batalEditPenilaian">
                                                                                <i class="ri-close-line me-1"></i>Batal
                                                                            </button>
                                                                        @endif
                                                                    </div>
                                                                @else
                                                                    <div class="alert alert-warning">
                                                                        Tidak ada tingkatan nilai yang tersedia untuk
                                                                        kriteria
                                                                        ini.
                                                                    </div>
                                                                @endif
                                                            @endif
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                            <div x-show="menu === 'verifikasi'" aria-labelledby="v-pills-profile-tab">
                                                <div>
                                                    @php $canDoPenilaian = $this->canDoPenilaian; @endphp
                                                    @if (!$canDoPenilaian['allowed'])
                                                        <div class="alert alert-warning" role="alert">
                                                            <i class="ri-alert-line me-2"></i>
                                                            <strong>Upload Bukti Dukung Diperlukan</strong>
                                                            <p class="mb-0 mt-2">{{ $canDoPenilaian['message'] }}</p>
                                                        </div>
                                                    @else
                                                        <div class="mb-2">
                                                            <div class="ms-3">
                                                                {{-- Riwayat Verifikasi --}}
                                                                @if ($this->riwayatVerifikasi->isNotEmpty())
                                                                    <div class="live-preview mb-4">
                                                                        <h6 class="mb-3">Riwayat Verifikasi</h6>
                                                                        <div class="table-responsive"
                                                                            x-data="{
                                                                                initPopovers() {
                                                                                    this.$nextTick(() => {
                                                                                        const popoverTriggerList = [].slice.call(this.$el.querySelectorAll('[data-bs-toggle=&quot;popover&quot;]'));
                                                                                        popoverTriggerList.map(function(popoverTriggerEl) {
                                                                                            return new bootstrap.Popover(popoverTriggerEl);
                                                                                        });
                                                                                    });
                                                                                }
                                                                            }"
                                                                            x-init="initPopovers()">
                                                                            <table
                                                                                class="table align-middle table-nowrap mb-0">
                                                                                <thead class="table-light">
                                                                                    <tr>
                                                                                        <th scope="col">Status
                                                                                            Verifikasi
                                                                                        </th>
                                                                                        <th scope="col"
                                                                                            class="text-center">
                                                                                            Keterangan
                                                                                        </th>
                                                                                        <th scope="col">Oleh</th>
                                                                                        <th scope="col">Tanggal</th>
                                                                                    </tr>
                                                                                </thead>
                                                                                <tbody>
                                                                                    @foreach ($this->riwayatVerifikasi as $verifikasi)
                                                                                        <tr>
                                                                                            <td>
                                                                                                @if ($verifikasi->is_verified)
                                                                                                    <span
                                                                                                        class="badge bg-success">
                                                                                                        <i
                                                                                                            class="ri-check-line me-1"></i>Terverifikasi
                                                                                                    </span>
                                                                                                    @elseif ($verifikasi->role->nama == 'opd')
                                                                                                    <span>-</span>
                                                                                                    @elseif ($verifikasi->is_verified == false)
                                                                                                    <span
                                                                                                        class="badge bg-danger">
                                                                                                        <i
                                                                                                            class="ri-close-line me-1"></i>Tidak
                                                                                                        Sesuai
                                                                                                    </span>
                                                                                                @else
                                                                                                    <span>-</span>
                                                                                                @endif
                                                                                            </td>
                                                                                            <td class="text-center">
                                                                                                @if ($verifikasi->keterangan)
                                                                                                    <button
                                                                                                        tabindex="0"
                                                                                                        class="btn btn-sm btn-soft-primary"
                                                                                                        role="button"
                                                                                                        data-bs-container="body"
                                                                                                        data-bs-toggle="popover"
                                                                                                        data-bs-trigger="focus"
                                                                                                        data-bs-placement="top"
                                                                                                        data-bs-content="Keterangan: {{ $verifikasi->keterangan }}">
                                                                                                        <i
                                                                                                            class="ri-information-line"></i>
                                                                                                    </button>
                                                                                                @else
                                                                                                    <span
                                                                                                        class="text-muted">-</span>
                                                                                                @endif
                                                                                            </td>
                                                                                            <td>{{ $verifikasi->role->nama ?? '-' }}
                                                                                            </td>
                                                                                            <td>{{ $verifikasi->created_at->format('d M Y H:i') }}
                                                                                            </td>
                                                                                        </tr>
                                                                                    @endforeach
                                                                                </tbody>
                                                                            </table>
                                                                        </div>
                                                                    </div>
                                                                @endif

                                                                {{-- Form Verifikasi Baru --}}
                                                                <h6 class="mb-3">Form Verifikasi</h6>
                                                                <div class="form-check form-switch mt-3">
                                                                    <input wire:model.live="is_verified"
                                                                        value="1" type="radio"
                                                                        class="form-check-input" role="switch"
                                                                        id="verifikasiSesuai"
                                                                        name="verifikasi_status">
                                                                    <label class="form-check-label"
                                                                        for="verifikasiSesuai">
                                                                        <i
                                                                            class="ri-check-line text-success me-1"></i>Ya,
                                                                        sudah
                                                                        diperiksa dan sesuai
                                                                    </label>
                                                                </div>
                                                                <div class="form-check form-switch mt-3">
                                                                    <input wire:model.live="is_verified"
                                                                        value="0" type="radio"
                                                                        class="form-check-input" role="switch"
                                                                        id="verifikasiTidakSesuai"
                                                                        name="verifikasi_status">
                                                                    <label class="form-check-label"
                                                                        for="verifikasiTidakSesuai">
                                                                        <i
                                                                            class="ri-close-line text-danger me-1"></i>Tidak,
                                                                        belum ada kesesuaian
                                                                    </label>
                                                                </div>
                                                                <div class="mb-3 mt-3">
                                                                    <label for="keterangan"
                                                                        class="form-label">Keterangan</label>
                                                                    <textarea wire:model="keterangan_verifikasi" class="form-control" id="keterangan" rows="3"
                                                                        placeholder="Tambahkan catatan atau keterangan..."></textarea>
                                                                    @error('keterangan_verifikasi')
                                                                        <span
                                                                            class="text-danger small">{{ $message }}</span>
                                                                    @enderror
                                                                </div>
                                                                <button wire:click="simpanVerifikasi"
                                                                    class="btn btn-primary">
                                                                    <i class="ri-save-line me-1"></i>Simpan
                                                                </button>
                                                                @error('is_verified')
                                                                    <div class="text-danger small mt-2">
                                                                        {{ $message }}</div>
                                                                @enderror
                                                            </div>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div><!-- end menu verifikasi -->

                                            <div x-show="menu === 'history'" aria-labelledby="v-pills-history-tab">
                                                <div>
                                                    @php
                                                        $historyData = $this->getHistoryPenilaian;
                                                    @endphp

                                                    @if ($historyData->isEmpty())
                                                        <div class="alert alert-info text-center">
                                                            <i class="ri-information-line fs-3"></i>
                                                            <p class="mb-0 mt-2">
                                                                @if (!$this->penilaianDiKriteria && !$bukti_dukung_id)
                                                                    Silakan pilih bukti dukung terlebih dahulu untuk
                                                                    melihat history.
                                                                @else
                                                                    Belum ada history penilaian untuk
                                                                    {{ $this->penilaianDiKriteria ? 'kriteria komponen' : 'bukti dukung' }}
                                                                    ini.
                                                                @endif
                                                            </p>
                                                        </div>
                                                    @else
                                                        <div class="table-responsive" x-data="{
                                                            initPopovers() {
                                                                this.$nextTick(() => {
                                                                    const popoverTriggerList = [].slice.call(this.$el.querySelectorAll('[data-bs-toggle=&quot;popover&quot;]'));
                                                                    popoverTriggerList.map(function(popoverTriggerEl) {
                                                                        return new bootstrap.Popover(popoverTriggerEl);
                                                                    });
                                                                });
                                                            }
                                                        }"
                                                            x-init="initPopovers()"
                                                            @historyUpdated.window="initPopovers()">
                                                            <table
                                                                class="table table-striped table-hover align-middle mb-0">
                                                                <thead class="table-light">
                                                                    <tr>
                                                                        <th scope="col" style="width: 5%">No</th>
                                                                        <th scope="col" style="width: 15%">Tanggal
                                                                            & Waktu</th>
                                                                        <th scope="col" style="width: 15%">User
                                                                        </th>
                                                                        <th scope="col" style="width: 10%">Role
                                                                        </th>
                                                                        <th scope="col" style="width: 20%">Aksi
                                                                        </th>
                                                                        <th scope="col" style="width: 8%">Nilai
                                                                        </th>
                                                                        <th scope="col" style="width: 10%">Status
                                                                        </th>
                                                                        <th scope="col" style="width: 10%"
                                                                            class="text-center">
                                                                            Keterangan</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    @foreach ($historyData as $index => $history)
                                                                        <tr>
                                                                            <td>{{ $index + 1 }}</td>
                                                                            <td>
                                                                                <small class="text-muted">
                                                                                    {{ $history->created_at->format('d/m/Y') }}<br>
                                                                                    {{ $history->created_at->format('H:i') }}
                                                                                    WIB
                                                                                </small>
                                                                            </td>
                                                                            <td>
                                                                                <div class="d-flex align-items-center">
                                                                                    <span
                                                                                        class="fw-medium">{{ $history->user->name ?? '-' }}</span>
                                                                                </div>
                                                                            </td>
                                                                            <td>
                                                                                <span>
                                                                                    {{ ucfirst($history->role->jenis ?? '-') }}
                                                                                </span>
                                                                            </td>
                                                                            <td>
                                                                                <span
                                                                                    class="text-dark">{{ $history->getActionDescription() }}</span>
                                                                                @if ($history->is_perubahan)
                                                                                    <span
                                                                                        class="badge bg-soft-warning text-warning ms-1">
                                                                                        <i
                                                                                            class="ri-refresh-line me-1"></i>Revisi
                                                                                    </span>
                                                                                @endif
                                                                            </td>
                                                                            <td>
                                                                                @if ($history->tingkatan_nilai)
                                                                                    <span
                                                                                        class="badge bg-primary fs-6">
                                                                                        {{ $history->tingkatan_nilai->kode_nilai }}
                                                                                    </span>
                                                                                @else
                                                                                    <span class="text-muted">-</span>
                                                                                @endif
                                                                            </td>
                                                                            <td>
                                                                                @if ($history->is_verified === true)
                                                                                    <span class="badge bg-success">
                                                                                        <i
                                                                                            class="ri-check-line me-1"></i>Disetujui
                                                                                    </span>
                                                                                    @elseif ($history->is_verified === false)
                                                                                    <span class="badge bg-danger">
                                                                                        <i
                                                                                            class="ri-close-line me-1"></i>Ditolak
                                                                                    </span>
                                                                                @else
                                                                                    <span class="text-muted">-</span>
                                                                                @endif
                                                                            </td>
                                                                            <td class="text-center">
                                                                                @if ($history->keterangan)
                                                                                    <button tabindex="0"
                                                                                        class="btn btn-sm btn-soft-primary"
                                                                                        role="button"
                                                                                        data-bs-container="body"
                                                                                        data-bs-toggle="popover"
                                                                                        data-bs-trigger="focus"
                                                                                        data-bs-placement="top"
                                                                                        data-bs-content="Keterangan: {{ $history->keterangan }}">
                                                                                        <i
                                                                                            class="ri-information-line"></i>
                                                                                    </button>
                                                                                @else
                                                                                    <span class="text-muted">-</span>
                                                                                @endif
                                                                            </td>
                                                                        </tr>
                                                                    @endforeach
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div><!-- end menu history -->
                                        </div><!-- end tab-content -->
                                    </div><!-- end col-md-10 -->
                                </div><!--  end row -->
                            </div><!-- end card-body -->
                        </div><!-- end card -->
                    </div><!-- end col -->
                </div><!-- end row (tab penilaian) -->
            @endif
        @endif

        <!-- Tracking Modal -->
        <div wire:ignore.self id="trackingModal" class="modal fade" tabindex="-1" aria-labelledby="myModalLabel"
            aria-hidden="true" style="display: none;">
            <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="myModalLabel">
                            Tracking Status Evaluasi
                            @if ($this->getSelectedBuktiDukungName())
                                <br>
                                <small class="text-muted">{{ $this->getSelectedBuktiDukungName() }}</small>
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
                                    color: #6c757d;
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
                                        <p class="mb-0 mt-2">Pilih OPD/bukti dukung untuk melihat tracking evaluasi.
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
                                                    @if ($item['nilai_numerik'])
                                                        - {{ number_format($item['nilai_numerik'], 2) }}%
                                                    @endif
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

                </div><!-- /.modal-content -->
            </div><!-- /.modal-dialog -->
        </div><!-- /.modal -->

    </div>
</div>
