<?php

return [
    /*
    |--------------------------------------------------------------------------
    | esakip API Configuration
    |--------------------------------------------------------------------------
    |
    | Konfigurasi untuk integrasi dengan API esakip Kabupaten Trenggalek
    |
    */

    'api_base_url' => env('ESAKIP_API_URL', 'https://e-sakip.trenggalekkab.go.id/api'),

    /*
    |--------------------------------------------------------------------------
    | Document Types Mapping
    |--------------------------------------------------------------------------
    |
    | Mapping jenis dokumen yang tersedia di esakip
    | Key = value yang disimpan di database (format API)
    | Value = label yang ditampilkan di UI
    |
    */

    'document_types' => [
        // Dokumen dengan periode
        'rpjmd' => 'RPJMD',
        'proses-bisnis' => 'Proses Bisnis',
        'cascading' => 'Pohon Kinerja (Cascading)',
        'renstra' => 'Renstra',
        'iku' => 'IKU',
        'iki' => 'IKI',

        // Dokumen dengan tahun
        'renja' => 'Renja',
        'perjanjian-kinerja' => 'Perjanjian Kinerja',
        'rencana-aksi' => 'Rencana Aksi',
        'lppd' => 'LPPD',
        'lkjip' => 'LKJIP',
        'lapkin' => 'Laporan Kinerja',
        'capaian' => 'Capaian',
        'mekanisme-pengumpulan-data' => 'Mekanisme Pengumpulan Data',
        'berita-acara-evaluasi' => 'Berita Acara Evaluasi',
        'lhe-inspektorat' => 'LHE Inspektorat',
        'lhe-kemenpan' => 'LHE Kemenpan',
        'paparan-sakip' => 'Paparan SAKIP',
        'rkpd' => 'RKPD',
        'lainnya' => 'Lainnya',
    ],

    /*
    |--------------------------------------------------------------------------
    | API Endpoints
    |--------------------------------------------------------------------------
    |
    | Endpoint untuk setiap jenis dokumen
    |
    */

    'endpoints' => [
        'list_documents' => '/list-dokumen',
        'list_opd' => '/list-opd',
        'document_base' => '/dokumen', // Base path, akan digabung dengan jenis dokumen
    ],

    /*
    |--------------------------------------------------------------------------
    | Sync Configuration
    |--------------------------------------------------------------------------
    |
    | Konfigurasi untuk proses sinkronisasi
    |
    */

    'sync' => [
        // Timeout untuk API request (dalam detik)
        'timeout' => env('ESAKIP_SYNC_TIMEOUT', 30),

        // Jumlah retry jika request gagal
        'retry_count' => env('ESAKIP_SYNC_RETRY', 3),

        // Delay antar retry (dalam detik)
        'retry_delay' => env('ESAKIP_SYNC_RETRY_DELAY', 2),

        // Mode fuzzy matching untuk nama dokumen
        'fuzzy_match' => env('ESAKIP_FUZZY_MATCH', true),

        // Threshold similarity untuk fuzzy matching (0-100)
        'similarity_threshold' => env('ESAKIP_SIMILARITY_THRESHOLD', 70),
    ],

    /*
    |--------------------------------------------------------------------------
    | Auto Verification Settings
    |--------------------------------------------------------------------------
    |
    | Konfigurasi untuk auto-verification setelah sinkronisasi
    |
    */

    'auto_verify' => [
        // Enable auto-verification
        'enabled' => env('ESAKIP_AUTO_VERIFY_ENABLED', true),

        // Default tingkatan nilai untuk auto-verification (null = tidak set)
        'default_tingkatan_nilai_id' => env('ESAKIP_DEFAULT_TINGKATAN_NILAI', null),
    ],
];
