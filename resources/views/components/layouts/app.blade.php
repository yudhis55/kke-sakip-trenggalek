<!doctype html>
<html lang="en" data-layout="horizontal" data-topbar="light" data-sidebar="dark" data-sidebar-size="lg"
    data-sidebar-image="none" data-preloader="disable">

<head>

    <meta charset="utf-8" />
    <title>{{ $title ?? 'Dashboard KKE SAKIP Trenggalek' }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="Kertas Kerja Evaluasi Sistem Akuntabilitas Kinerja Instansi Pemerintah Kab Trenggalek"
        name="description" />
    <meta content="Diskominfo Trenggalek" name="author" />
    <!-- App favicon -->
    <link rel="shortcut icon" href="{{ asset('assets/images/logo-trenggalek-mini.png') }}">

    <!-- Filepond css -->
    <link rel="stylesheet" href="{{ asset('assets/libs/filepond/filepond.min.css') }}" type="text/css" />
    <link rel="stylesheet"
        href="{{ asset('assets/libs/filepond-plugin-image-preview/filepond-plugin-image-preview.min.css') }}">

    <!-- plugin css -->
    <link href="{{ asset('assets/libs/jsvectormap/css/jsvectormap.min.css') }}" rel="stylesheet" type="text/css" />

    <!-- Layout config Js -->
    <script src="{{ asset('assets/js/layout.js') }}"></script>
    <!-- Bootstrap Css -->
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css" />
    <!-- Icons Css -->
    <link href="{{ asset('assets/css/icons.min.css') }}" rel="stylesheet" type="text/css" />
    <!-- App Css-->
    <link href="{{ asset('assets/css/app.min.css') }}" rel="stylesheet" type="text/css" />
    <!-- custom Css-->
    <link href="{{ asset('assets/css/custom.min.css') }}" rel="stylesheet" type="text/css" />

    <!-- Custom Badge Notification Style -->
    {{-- <style>
        .menu-badge-notification {
            position: absolute;
            top: 8px;
            right: 15px;
            min-width: 20px;
            height: 20px;
            padding: 0 6px;
            font-size: 11px;
            font-weight: 600;
            line-height: 20px;
            text-align: center;
            border-radius: 10px;
            background-color: #f06548;
            color: white;
            box-shadow: 0 2px 4px rgba(240, 101, 72, 0.4);
            animation: pulse-badge 2s ease-in-out infinite;
        }

        @keyframes pulse-badge {
            0%, 100% {
                box-shadow: 0 2px 4px rgba(240, 101, 72, 0.4);
            }
            50% {
                box-shadow: 0 0 0 4px rgba(240, 101, 72, 0.2);
            }
        }

        .nav-item {
            position: relative;
        }
    </style> --}}

    <style>
        .nav-item {
            position: relative;
        }

        .badge-pulsate-app {
            display: inline-block;
            background-color: red;
            border-radius: 50%;
            width: 5px;
            height: 5px;
            padding: 0;
            position: absolute;
            top: 12px;
            right: 15px;
        }

        .badge-pulsate-app::before {
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

    @filepondScripts
</head>

<body>

    <!-- Begin page -->
    <div id="layout-wrapper">

        <header id="page-topbar">
            <div class="layout-width">
                <div class="navbar-header">
                    <div class="d-flex">
                        <!-- LOGO -->
                        <div class="navbar-brand-box horizontal-logo">
                            <a href="javascript:void(0)" class="logo logo-dark">
                                <span class="logo-sm">
                                    <img src="{{ asset('assets/images/logo-kke-sakip.svg') }}" alt=""
                                        height="22">
                                </span>
                                <span class="logo-lg">
                                    <img src="{{ asset('assets/images/logo-kke-sakip.svg') }}" alt=""
                                        height="50">
                                </span>
                            </a>

                            <a href="javascript:void(0)" class="logo logo-light">
                                <span class="logo-sm">
                                    <img src="{{ asset('assets/images/logo-kke-sakip.svg') }}" alt=""
                                        height="22">
                                </span>
                                <span class="logo-lg">
                                    <img src="{{ asset('assets/images/logo-kke-sakip.svg') }}" alt=""
                                        height="50">
                                </span>
                            </a>
                        </div>

                        <button type="button"
                            class="btn btn-sm px-3 fs-16 header-item vertical-menu-btn topnav-hamburger shadow-none"
                            id="topnav-hamburger-icon">
                            <span class="hamburger-icon">
                                <span></span>
                                <span></span>
                                <span></span>
                            </span>
                        </button>
                    </div>

                    <div class="d-flex align-items-center">
                        @if (Auth::user()->role->jenis != 'admin')
                            <livewire:dashboard.countdown-timer />
                        @endif
                        <livewire:dashboard.tahun-dropdown />

                        {{-- <div class="ms-1 header-item d-none d-sm-flex">
                            <button type="button"
                                class="btn btn-icon btn-topbar btn-ghost-secondary rounded-circle shadow-none"
                                data-toggle="fullscreen">
                                <i class='bx bx-fullscreen fs-22'></i>
                            </button>
                        </div>

                        <div class="ms-1 header-item d-none d-sm-flex">
                            <button type="button"
                                class="btn btn-icon btn-topbar btn-ghost-secondary rounded-circle light-dark-mode shadow-none">
                                <i class='bx bx-moon fs-22'></i>
                            </button>
                        </div> --}}

                        <div class="dropdown ms-sm-3 header-item topbar-user">
                            <button type="button" class="btn shadow-none" id="page-header-user-dropdown"
                                data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="d-flex align-items-center">
                                    <img class="rounded-circle header-profile-user"
                                        src="{{ asset('assets/images/users/user-dummy-img.jpg') }}"
                                        alt="Header Avatar">
                                    <span class="text-start ms-xl-2">
                                        <span
                                            class="d-none d-xl-inline-block ms-1 fw-medium user-name-text">{{ Auth::user()->name }}</span>
                                        <span
                                            class="d-none d-xl-block ms-1 fs-12 text-muted user-name-sub-text text-capitalize">{{ Auth::user()->role->nama }}</span>
                                    </span>
                                </span>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end">
                                <!-- item-->
                                <h6 class="dropdown-header">Welcome {{ Auth::user()->name }}</h6>
                                <a class="dropdown-item" href="pages-profile.html"><i
                                        class="mdi mdi-account-circle text-muted fs-16 align-middle me-1"></i> <span
                                        class="align-middle">Profile</span></a>
                                <livewire:auth.logout />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        {{ $slot }}

        <!-- removeNotificationModal -->
        <div id="removeNotificationModal" class="modal fade zoomIn" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                            id="NotificationModalbtn-close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mt-2 text-center">
                            <lord-icon src="https://cdn.lordicon.com/gsqxdxog.json" trigger="loop"
                                colors="primary:#f7b84b,secondary:#f06548"
                                style="width:100px;height:100px"></lord-icon>
                            <div class="mt-4 pt-2 fs-15 mx-4 mx-sm-5">
                                <h4>Are you sure ?</h4>
                                <p class="text-muted mx-4 mb-0">Are you sure you want to remove this Notification ?</p>
                            </div>
                        </div>
                        <div class="d-flex gap-2 justify-content-center mt-4 mb-2">
                            <button type="button" class="btn w-sm btn-light" data-bs-dismiss="modal">Close</button>
                            <button type="button" class="btn w-sm btn-danger" id="delete-notification">Yes, Delete
                                It!</button>
                        </div>
                    </div>

                </div><!-- /.modal-content -->
            </div><!-- /.modal-dialog -->
        </div><!-- /.modal -->
        <!-- ========== App Menu ========== -->
        <div class="app-menu navbar-menu">
            <!-- LOGO -->
            <div class="navbar-brand-box">
                <!-- Dark Logo-->
                <a href="javascript:void(0)" class="logo logo-dark">
                    <span class="logo-sm">
                        <img src="{{ asset('assets/images/logo-kke-sakip.svg') }}" alt="" height="22">
                    </span>
                    <span class="logo-lg">
                        <img src="{{ asset('assets/images/logo-kke-sakip.svg') }}" alt="" height="17">
                    </span>
                </a>
                <!-- Light Logo-->
                <a href="javascript:void(0)" class="logo logo-light">
                    <span class="logo-sm">
                        <img src="{{ asset('assets/images/logo-kke-sakip.svg') }}" alt="" height="22">
                    </span>
                    <span class="logo-lg">
                        <img src="{{ asset('assets/images/logo-kke-sakip.svg') }}" alt="" height="17">
                    </span>
                </a>
                <button type="button" class="btn btn-sm p-0 fs-20 header-item float-end btn-vertical-sm-hover"
                    id="vertical-hover">
                    <i class="ri-record-circle-line"></i>
                </button>
            </div>

            <div id="scrollbar">
                <div class="container-fluid">

                    <div id="two-column-menu">
                    </div>
                    <ul class="navbar-nav" id="navbar-nav">
                        <li class="menu-title"><span data-key="t-menu">Menu</span></li>
                        <li class="nav-item">
                            <a wire:current="active" class="nav-link menu-link" href="/dashboard" role="button"
                                aria-expanded="true" aria-controls="sidebarDashboards">
                                <i class="mdi mdi-speedometer"></i> <span data-key="t-dashboards">Dashboards</span>
                            </a>
                        </li> <!-- end Dashboard Menu -->

                        @if (Auth::user()->role->jenis == 'admin')
                            <li class="nav-item">
                                <a wire:current="active" class="nav-link menu-link" href="/mapping" role="button"
                                    aria-expanded="true" aria-controls="sidebarApps">
                                    <i class="mdi mdi-view-grid-plus-outline"></i> <span
                                        data-key="t-apps">Mapping</span>
                                </a>
                            </li>
                        @endif

                        <li class="nav-item">
                            <a wire:current="active" class="nav-link menu-link" href="/lembar-kerja" role="button"
                                aria-expanded="false" aria-controls="sidebarLayouts">
                                <i class="mdi mdi-view-carousel-outline"></i> <span data-key="t-layouts">Lembar
                                    Kerja</span>
                            </a>
                        </li>

                        @if (Auth::user()->role->jenis == 'opd')
                            <li class="nav-item">
                                @php
                                    $opdId = Auth::user()->opd_id;
                                    $tahunSession = session('tahun_session');
                                    $verifikatorRoleIds = \App\Models\Role::where('jenis', 'verifikator')
                                        ->pluck('id')
                                        ->toArray();
                                    $penjaminRoleId = \App\Models\Role::where('jenis', 'penjamin')->first()?->id;
                                    $roleIds = array_merge($verifikatorRoleIds, [$penjaminRoleId]);

                                    $badgeCountPenolakan = \App\Models\PenilaianHistory::whereIn('role_id', $roleIds)
                                        ->where('opd_id', $opdId)
                                        ->where('is_verified', 0)
                                        ->whereNotNull('keterangan')
                                        ->where('status_perbaikan', 'belum_diperbaiki')
                                        ->when($tahunSession, function ($query) use ($tahunSession) {
                                            $query->whereHas('kriteria_komponen', function ($q) use ($tahunSession) {
                                                $q->where('tahun_id', $tahunSession);
                                            });
                                        })
                                        ->count();
                                @endphp
                                <a wire:current="active" class="nav-link menu-link" href="/rekap-penolakan"
                                    role="button" aria-expanded="false" aria-controls="sidebarLayouts">
                                    <i class="mdi mdi-file-cancel-outline"></i> <span data-key="t-layouts">Rekap
                                        Penolakan</span>
                                    @if ($badgeCountPenolakan > 0)
                                        <span class="badge-pulsate-app"></span>
                                    @endif
                                </a>
                            </li>
                        @endif

                        @if (in_array(Auth::user()->role->jenis, ['verifikator', 'penjamin', 'penilai']))
                            <li class="nav-item">
                                @php
                                    $tahunSession = session('tahun_session');

                                    // Setiap user hanya hitung perbaikan dari dokumen yang mereka sendiri tolak
                                    $badgeCountPerbaikan = \App\Models\PenilaianHistory::where('role_id', Auth::user()->role_id)
                                        ->where('is_verified', 0)
                                        ->whereNotNull('keterangan')
                                        ->where('status_perbaikan', 'sudah_diperbaiki')
                                        ->when($tahunSession, function ($query) use ($tahunSession) {
                                            $query->whereHas('kriteria_komponen', function ($q) use ($tahunSession) {
                                                $q->where('tahun_id', $tahunSession);
                                            });
                                        })
                                        ->count();
                                @endphp
                                <a wire:current="active" class="nav-link menu-link" href="/rekap-perbaikan"
                                    role="button" aria-expanded="false" aria-controls="sidebarLayouts">
                                    <i class="mdi mdi-file-check-outline"></i> <span data-key="t-layouts">Rekap
                                        Perbaikan</span>
                                    @if ($badgeCountPerbaikan > 0)
                                        <span class="badge-pulsate-app"></span>
                                    @endif
                                </a>
                            </li>
                        @endif

                        {{-- @if (Auth::user()->role->jenis == 'admin')
                            <li class="nav-item">
                                <a wire:current="active" class="nav-link menu-link" href="/monitoring"
                                    role="button" aria-expanded="false" aria-controls="sidebarLayouts">
                                    <i class="mdi mdi-chart-box-outline"></i> <span
                                        data-key="t-layouts">Monitoring</span>
                                </a>
                            </li>
                            <!-- end Dashboard Menu -->
                        @endif --}}

                        {{-- <li class="menu-title"><i class="ri-more-fill"></i> <span data-key="t-pages">Pages</span>
                        </li> --}}

                        @if (Auth::user()->role->jenis == 'admin')
                            <li class="nav-item">
                                <a wire:current="active" class="nav-link menu-link" href="/ekspor-laporan"
                                    role="button" aria-expanded="false" aria-controls="sidebarLayouts">
                                    <i class="mdi mdi-file-export-outline"></i> <span data-key="t-layouts">Ekspor
                                        Laporan</span>
                                </a>
                            </li>
                        @endif

                        @if (Auth::user()->role->jenis == 'admin')
                            <li class="nav-item">
                                <a wire:current="active" class="nav-link menu-link" href="/sinkron-data"
                                    role="button" aria-expanded="false" aria-controls="sidebarLayouts">
                                    <i class="mdi mdi-sync"></i> <span data-key="t-layouts">Sinkron Data</span>
                                </a>
                            </li>
                        @endif

                        @if (Auth::user()->role->jenis == 'admin')
                            <li class="nav-item">
                                <a wire:current="active" class="nav-link menu-link" href="{{ route('pengaturan') }}"
                                    role="button" aria-expanded="false" aria-controls="sidebarAuth">
                                    <i class="mdi mdi-cog-outline"></i> <span
                                        data-key="t-authentication">Pengaturan</span>
                                </a>
                            </li>
                        @endif
                    </ul>
                </div>
                <!-- Sidebar -->
            </div>

            <div class="sidebar-background"></div>
        </div>
        <!-- Left Sidebar End -->
        <!-- Vertical Overlay-->
        <div class="vertical-overlay"></div>

        <!-- ============================================================== -->
        <!-- Start right Content here -->
        <!-- ============================================================== -->
        <div class="main-content">



            <footer class="footer">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-sm-6">
                            {{ date('Y') }} © Diskominfo Trenggalek.
                        </div>
                        <div class="col-sm-6">
                            {{-- <div class="text-sm-end d-none d-sm-block">
                                Design & Develop by Diskominfo Trenggalek
                            </div> --}}
                        </div>
                    </div>
                </div>
            </footer>
        </div>
        <!-- end main content-->

    </div>
    <!-- END layout-wrapper -->

    <!--start back-to-top-->
    <button onclick="topFunction()" class="btn btn-danger btn-icon" id="back-to-top">
        <i class="ri-arrow-up-line"></i>
    </button>
    <!--end back-to-top-->

    <!--preloader-->
    <div id="preloader">
        <div id="status">
            <div class="spinner-border text-primary avatar-sm" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    </div>

    <!-- JAVASCRIPT -->
    <script src="{{ asset('assets/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/libs/simplebar/simplebar.min.js') }}"></script>
    <script src="{{ asset('assets/libs/node-waves/waves.min.js') }}"></script>
    <script src="{{ asset('assets/libs/feather-icons/feather.min.js') }}"></script>
    <script src="{{ asset('assets/js/pages/plugins/lord-icon-2.1.0.js') }}"></script>
    <script src="{{ asset('assets/js/plugins.js') }}"></script>

    <!-- apexcharts -->
    <script src="{{ asset('assets/libs/apexcharts/apexcharts.min.js') }}"></script>

    <!-- prismjs plugin -->
    <script src="{{ asset('assets/libs/prismjs/prism.js') }}"></script>

    <!-- Vector map-->
    <script src="{{ asset('assets/libs/jsvectormap/js/jsvectormap.min.js') }}"></script>
    <script src="{{ asset('assets/libs/jsvectormap/maps/world-merc.js') }}"></script>

    <!-- dropzone min -->
    <script src="{{ asset('assets/libs/dropzone/dropzone-min.js') }}"></script>

    <!-- filepond js -->
    <script src="{{ asset('assets/libs/filepond/filepond.min.js') }}"></script>
    <script src="{{ asset('assets/libs/filepond-plugin-image-preview/filepond-plugin-image-preview.min.js') }}"></script>
    <script src="{{ asset('assets/libs/filepond-plugin-file-validate-size/filepond-plugin-file-validate-size.min.js') }}">
    </script>
    <script
        src="{{ asset('assets/libs/filepond-plugin-image-exif-orientation/filepond-plugin-image-exif-orientation.min.js') }}">
    </script>
    <script src="{{ asset('assets/libs/filepond-plugin-file-encode/filepond-plugin-file-encode.min.js') }}"></script>
    <script src="{{ asset('assets/js/pages/form-file-upload.init.js') }}"></script>
    <!-- Dashboard init -->
    <script src="{{ asset('assets/js/pages/dashboard-analytics.init.js') }}"></script>
    <script src="{{ asset('assets/js/pages/apexcharts-column.init.js') }}"></script>


    <!-- App js -->
    <script src="{{ asset('assets/js/app.js') }}"></script>
</body>

</html>
