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
                    </h4>

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

        <div class="row">
            @foreach ($this->subKomponenOptions as $sub_komponen)
                <div wire:key="{{ $sub_komponen->id}}" class="col-xxl-4 col-lg-4" x-show="isKomponenSelected({{ $sub_komponen->komponen_id }})"
                    x-cloak>
                    <div class="card card-body text-center">
                        <div class="avatar-sm mx-auto mb-3">
                            <div class="avatar-title bg-soft-primary text-primary fs-17 rounded">
                                <i class="ri-folder-2-line"></i>
                            </div>
                        </div>
                        <h4 class="card-title">{{ $sub_komponen->nama }}</h4>
                        <p class="card-text text-muted">{{ $sub_komponen->kode }}</p>
                        <p class="text-muted">Jumlah Kriteria: {{ $sub_komponen->kriteria_komponen->count() }}</p>
                        <a href="{{ route('lembar-kerja.kriteria-komponen', ['sub_komponen_id' => $sub_komponen->id]) }}"
                            class="btn btn-primary">Pilih</a>
                    </div>
                </div><!-- end col -->
            @endforeach
        </div>

    </div>
    <!-- container-fluid -->
</div>
<!-- End Page-content -->
