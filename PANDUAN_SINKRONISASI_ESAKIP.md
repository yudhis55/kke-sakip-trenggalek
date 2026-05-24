# Panduan Sinkronisasi Data E-SAKIP

Dokumen ini menjelaskan sistem integrasi data antara aplikasi KKE-SAKIP dengan API E-SAKIP Kabupaten Trenggalek. Sistem menggunakan pendekatan **smart-merge** otomatis untuk memastikan data yang diunggah secara manual tetap terjaga sementara dokumen dari E-SAKIP disinkronkan secara efisien.

Sistem sinkronisasi ini dirancang untuk mengurangi beban kerja operator OPD dalam mengunggah dokumen yang sudah tersedia di sistem pusat, sekaligus menjaga integritas data yang telah divalidasi atau diunggah secara khusus di tingkat lokal. Dengan adanya integrasi ini, koordinasi antar instansi menjadi lebih cepat dan akurat.

Sistem ini bersifat dinamis dan adaptif terhadap perubahan struktur organisasi di lingkungan pemerintah daerah. Dengan mengandalkan teknologi API, sinkronisasi dapat dilakukan kapan saja sesuai dengan kebutuhan evaluasi kinerja tahunan yang sedang berjalan. Integrasi ini juga mendukung transparansi data di mana setiap dokumen yang ditarik memiliki identitas sumber yang jelas.

Penggunaan sistem ini sangat disarankan untuk menjaga konsistensi data antara perencanaan di E-SAKIP dan evaluasi di KKE-SAKIP. Dengan sinkronisasi yang teratur, pimpinan dapat memantau progres kelengkapan dokumen pendukung secara real-time melalui dashboard yang tersedia.

---

## 🚀 Konsep Utama: Smart Merge & Source Tracking

Sistem tidak lagi menggunakan pilihan mode sinkronisasi manual yang membingungkan bagi pengguna akhir. Sebagai gantinya, aplikasi menggunakan logika **smart merge** berdasarkan pelacakan sumber data (source).

### 1. Pelacakan Sumber (Source Tracking)
Setiap data penilaian yang memiliki dokumen bukti dukung ditandai dengan kolom source:
- source = 'upload': Dokumen diunggah secara manual oleh operator OPD melalui aplikasi KKE-SAKIP. Dokumen ini dianggap sebagai prioritas utama karena mungkin merupakan dokumen spesifik yang tidak tersedia di E-SAKIP pusat.
- source = 'esakip': Dokumen ditarik secara otomatis dari API E-SAKIP. Dokumen ini dapat diperbarui secara otomatis jika ada versi terbaru di server pusat selama statusnya belum dikunci oleh verifikator.

Logika source = 'upload' dan source = 'esakip' ini disimpan secara permanen di database untuk setiap baris penilaian. Hal ini mencegah terjadinya tumpang tindih data antara hasil kerja manual manusia dan otomatisasi mesin. Pemisahan sumber ini juga memudahkan proses audit jika terjadi perbedaan dokumen antara sistem lokal dan pusat.

### 2. Logika Smart Merge
Saat proses sinkronisasi berjalan melalui komponen SinkronData:
- Jika ditemukan data dengan source = 'upload', proses sinkronisasi akan **melewati (skip)** kriteria tersebut. Hal ini menjamin bahwa kerja manual operator OPD tidak akan pernah tertimpa oleh otomatisasi sistem.
- Jika ditemukan data dengan source = 'esakip', sistem akan membandingkan data yang ada. Jika terdapat perubahan atau dokumen baru di API, sistem akan melakukan pembaruan (update) secara otomatis.
- Jika belum ada data penilaian untuk kriteria tersebut, sistem akan membuat catatan penilaian baru, menarik dokumen dari API, dan menandainya sebagai source = 'esakip'.

Logika ini memastikan bahwa validitas data tetap terjaga tanpa intervensi manual yang berlebihan dari pihak admin atau operator, sekaligus mengurangi risiko redundansi data. Sistem **smart merge** ini bekerja secara background tanpa memerlukan konfigurasi tambahan dari pengguna.

---

## 📂 Setup Mapping Bukti Dukung

Sebelum melakukan sinkronisasi, admin harus melakukan pemetaan (mapping) antara Bukti Dukung di KKE-SAKIP dengan jenis dokumen di API E-SAKIP. Pemetaan ini krusial agar sistem tahu dokumen mana yang harus ditarik untuk kriteria penilaian tertentu.

### Langkah Mapping Detail:
1. Navigasi ke menu **Master Data** > **Komponen**.
2. Pilih **SubKomponen** dan temukan **Kriteria** yang relevan.
3. Klik tombol **Edit** pada **Bukti Dukung** yang ingin dipetakan.
4. Isi kolom-kolom integrasi berikut:
    - **E-SAKIP Document Type**: Pilih jenis dokumen yang sesuai dari dropdown (contoh: enja, iku, lkjip, enstra). Daftar ini dikelola di config/esakip.php.
    - **Is N-1**: Centang jika dokumen yang dibutuhkan adalah dokumen dari tahun sebelumnya. Ini sangat penting untuk kriteria yang mengevaluasi perencanaan jangka menengah atau tindak lanjut hasil evaluasi tahun lalu. Flag is_n_minus_1 sangat menentukan akurasi pengambilan data.
    - **Verifikasi Otomatis**: Jika dicentang, dokumen yang berhasil ditarik melalui SinkronData akan langsung berstatus "Terverifikasi". Ini sangat membantu mempercepat proses penilaian massal.

### Contoh Konfigurasi Mapping (JSON):
```json
{
  "nama_bukti_dukung": "Laporan Kinerja Tahunan (LKjIP)",
  "esakip_type": "lkjip",
  "is_n_minus_1": false,
  "is_auto_verify": true,
  "keterangan": "Sinkronisasi otomatis dari E-SAKIP Kabupaten"
}
```

Pastikan mapping dilakukan dengan teliti agar dokumen yang ditarik relevan dengan kriteria evaluasi yang dimaksud. Kesalahan mapping dapat menyebabkan hasil penilaian tidak akurat.

---

## 🏗️ Penanganan OPD Baru & Reorganisasi

Aplikasi KKE-SAKIP memiliki fitur canggih untuk menangani skenario **reorganisasi** perangkat daerah. Di lingkungan pemerintah, sering terjadi pemecahan, penggabungan, atau perubahan nama OPD yang berdampak pada sejarah dokumen.

### Parameter Penting di Tabel OPD:
Untuk mendukung transisi yang mulus, setiap OPD memiliki tiga parameter pemetaan utama:
1. esakip_opd_id: ID unik OPD di sistem E-SAKIP pusat. Digunakan sebagai parameter kunci saat melakukan request ke API.
2. 	ahun_mulai_berlaku: Tahun di mana OPD tersebut mulai beroperasi secara administratif dengan identitas baru (misal: 2026). Kolom 	ahun_mulai_berlaku ini menjadi acuan utama sistem untuk menentukan apakah harus mencari dokumen di identitas baru atau identitas lama.
3. predecessor_opd_id: Referensi ke ID OPD lama (induk) yang menyimpan sejarah dokumen sebelum reorganisasi terjadi. Parameter predecessor_opd_id digunakan sebagai fallback otomatis oleh EsakipSyncService.

### Skenario Kasus: OPD Baru 2026
Misalkan terdapat **OPD baru** bernama "Dinas Pendidikan" (sebelumnya bagian dari Dinas Pendidikan, Pemuda dan Olahraga). OPD ini dibentuk sebagai hasil **reorganisasi** struktural di awal tahun anggaran 2026.

| Atribut | Nilai | Keterangan |
|---------|-------|------------|
| Nama OPD | Dinas Pendidikan | Nama entitas baru |
| esakip_opd_id | 43 | Mapping ID API |
| 	ahun_mulai_berlaku | 2026 | Baru aktif di sistem KKE mulai 2026 |
| predecessor_opd_id | 5 | Mengambil sejarah dari Disdikpora (ID 5) |

**Logika Pengambilan Data Otomatis:**
Jika admin melakukan sinkronisasi untuk tahun **2025** bagi Dinas Pendidikan (ID 43), sistem akan mendeteksi bahwa tahun tersebut (2025) lebih kecil dari nilai 	ahun_mulai_berlaku (2026). Secara cerdas, sistem akan mengalihkan permintaan API ke predecessor_opd_id (ID 5). 

Dengan demikian, meskipun **OPD baru** tersebut secara administratif belum ada di tahun 2025, dokumen sejarahnya tetap dapat ditemukan di sistem E-SAKIP melalui identitas induk lamanya. Fitur **reorganisasi** ini memastikan kontinuitas evaluasi tetap terjaga meskipun terjadi perubahan nomenklatur instansi secara besar-besaran.

---

## 🗓️ Mekanisme Dokumen N-1 (Year-Minus-One)

Beberapa kriteria evaluasi (seperti evaluasi Renstra atau tindak lanjut LHE) membutuhkan dokumen dari satu tahun sebelum tahun evaluasi berjalan. Mekanisme ini ditangani secara otomatis melalui flag is_n_minus_1.

### Cara Kerja is_n_minus_1 Detail:
1. Operator menjalankan SinkronData untuk **Tahun Evaluasi 2026**.
2. Sistem menemukan Bukti Dukung yang memiliki flag is_n_minus_1 = true.
3. Sistem menghitung tahun sumber: sourceYear = 2026 - 1 = 2025.
4. Sistem memanggil API E-SAKIP menggunakan parameter 	ahun=2025 dan opd_id yang sesuai.

### Integrasi N-1 dengan Reorganisasi:
Logika ini menjadi sangat krusial saat menangani **OPD baru**. Jika is_n_minus_1 aktif DAN tahun target (N-1) ternyata jatuh sebelum 	ahun_mulai_berlaku OPD tersebut, sistem akan secara otomatis melakukan "pencarian mundur" menggunakan predecessor_opd_id.

Misalnya, untuk mendapatkan dokumen Renstra (N-1) bagi **OPD baru** yang efektif di tahun 2026, sistem harus menarik data tahun 2025 dari predecessor_opd_id. Penggunaan flag is_n_minus_1 ini terintegrasi penuh dengan logika audit trail sistem dan memastikan tidak ada data yang hilang selama masa transisi organisasi.

**Contoh Logika Internal (PHP):**
```php
// Penentuan sumber dokumen di EsakipSyncService
 = ->tahun;
 = ->esakip_opd_id;

if (->is_n_minus_1) {
     =  - 1; // Mundur 1 tahun (N-1 logic)
}

// Logika Reorganisasi: Cek tahun berlaku vs tahun dokumen
if (->tahun_mulai_berlaku &&  < ->tahun_mulai_berlaku) {
    if (->predecessor_opd_id) {
        // Alihkan ke ID OPD lama jika tahun target di luar masa berlaku OPD baru
         = ->predecessor_opd_id; 
    }
}
```

---

## 🛠️ Panduan Penggunaan Antarmuka SinkronData

Proses sinkronisasi dilakukan melalui antarmuka Livewire terpusat yang dapat diakses melalui route /sinkron-data. Menu ini dirancang untuk memberikan kendali penuh namun tetap mudah digunakan oleh operator pusat.

### Langkah-langkah Operasional:
1. **Akses Menu**: Buka menu **Sinkronisasi** pada sidebar dashboard. Pastikan Anda memiliki hak akses sebagai Admin atau Verifikator yang berwenang.
2. **Filter Data**:
    - **Tahun**: Pilih tahun evaluasi yang sedang dikerjakan (misalnya 2026).
    - **OPD**: Pilih satu OPD untuk sinkronisasi spesifik jika ingin fokus pada satu instansi, atau kosongkan untuk memproses seluruh OPD di Kabupaten Trenggalek secara massal.
    - **Jenis Dokumen**: Pilih jenis dokumen tertentu (misal: hanya Renja atau IKU) untuk mempercepat proses.
3. **Preview Hasil**:
    - Klik tombol **Preview Sinkronisasi**.
    - Sistem akan melakukan simulasi koneksi ke API dan menampilkan daftar dokumen yang ditemukan.
    - Statistik akan menampilkan: berapa dokumen baru, berapa yang akan di-update, dan berapa yang akan di-skip karena status source = 'upload'.
4. **Eksekusi**:
    - Klik tombol **Proses Sinkronisasi**.
    - Monitor progress bar hingga mencapai 100%. Jangan menutup halaman atau berpindah menu selama proses berlangsung untuk menghindari terjadinya data korup.
5. **Verifikasi Akhir**:
    - Setelah selesai, sistem akan menampilkan ringkasan detail keberhasilan (Success, Failed, Skipped).
    - Periksa hasil di halaman Evaluasi masing-masing OPD untuk memastikan dokumen telah tersemat dengan benar.

---

## 📋 Referensi Struktur Database

Berikut adalah rincian teknis kolom-kolom yang mendukung fitur sinkronisasi dan manajemen **reorganisasi** dalam basis data aplikasi KKE-SAKIP:

### 1. Tabel opd (Master Organisasi)
- esakip_opd_id: ID integer yang merujuk pada identitas OPD di database E-SAKIP pusat.
- 	ahun_mulai_berlaku: Nilai integer tahun (YYYY) yang menandai titik awal OPD ini diakui secara mandiri di sistem KKE. Kolom 	ahun_mulai_berlaku wajib diisi untuk entitas baru hasil pemekaran.
- predecessor_opd_id: Foreign key yang menunjuk kembali ke baris lain di tabel opd sebagai sumber sejarah. Parameter predecessor_opd_id sangat vital untuk integritas data sejarah.

### 2. Tabel ukti_dukung (Definisi Kriteria)
- esakip_type: String kunci (slug) yang dipetakan ke endpoint API (contoh: enja, cascading).
- is_n_minus_1: Boolean yang menentukan apakah query API harus dikurangi satu tahun dari tahun evaluasi. Flag is_n_minus_1 ini memastikan relevansi dokumen tahun lalu.
- is_auto_verify: Boolean yang jika aktif akan otomatis mengubah status penilaian menjadi 'Terverifikasi' setelah sync berhasil melalui SinkronData.

### 3. Tabel penilaian (Data Transaksi)
- source: Menyimpan string source = 'upload' (input manual) atau source = 'esakip' (hasil sinkronisasi). Pelacakan source = 'upload' dan source = 'esakip' ini sangat penting untuk audit trail data.
- ile_url: URL dokumen publik dari server E-SAKIP (untuk source = 'esakip').
- ile_path: Lokasi file di storage lokal (untuk source = 'upload').

---

## 📑 Konfigurasi API (config/esakip.php)

Daftar dokumen yang tersedia dikelola melalui file konfigurasi Laravel secara terpusat. Admin dapat menambah atau mengubah label dokumen tanpa perlu menyentuh kode program inti.

```php
/* Mapping Jenis Dokumen dari API E-SAKIP */
'document_types' => [
    'rpjmd'   => 'RPJMD Kabupaten',
    'renstra' => 'Renstra Perangkat Daerah',
    'renja'   => 'Renja Tahunan',
    'iku'     => 'Indikator Kinerja Utama',
    'lkjip'   => 'Laporan Kinerja (LKjIP)',
    'cascading' => 'Pohon Kinerja (Cascading)',
    // Tambahkan tipe baru di sini jika API pusat menyediakan endpoint baru
],
```

### Dokumen Bersama (Pemkab)
Beberapa dokumen seperti RPJMD, RKPD, atau LHE Kabupaten bersifat lintas organisasi (lintas OPD). Dokumen ini ditarik secara khusus menggunakan identitas esakip_opd_id = 1 (Pemerintah Kabupaten) dan secara otomatis didistribusikan ke seluruh OPD. Sistem tetap mempertimbangkan flag is_n_minus_1 untuk dokumen bersama ini jika konfigurasi mapping memintanya.

---

## 🏗️ Skenario Teknis Reorganisasi (Contoh SQL)

Untuk memahami bagaimana data dikelola di belakang layar, berikut adalah contoh query yang mungkin digunakan untuk menyiapkan data **OPD baru** yang lahir dari **reorganisasi**.

```sql
-- Contoh mendaftarkan OPD baru hasil pemecahan
INSERT INTO opd (nama, esakip_opd_id, tahun_mulai_berlaku, predecessor_opd_id)
VALUES ('Dinas Lingkungan Hidup Baru', '44', 2026, 8);

-- Verifikasi mapping bukti dukung N-1
UPDATE bukti_dukung 
SET is_n_minus_1 = 1, esakip_type = 'renstra' 
WHERE nama LIKE '%Renstra%';
```

---

## 🔍 Troubleshooting & Audit Trail

Aplikasi menyediakan mekanisme pelacakan yang kuat untuk setiap sesi sinkronisasi demi transparansi data:

1. **Riwayat Sinkron**: Setiap kali SinkronData dijalankan, sistem mencatat siapa yang menjalankan, kapan, dan bagaimana hasilnya di tabel iwayat_sinkron.
2. **Log Aplikasi**: Detail teknis termasuk kegagalan koneksi API, timeout, atau data yang tidak ditemukan dicatat di storage/logs/laravel.log dengan prefiks [ESAKIP-SYNC].
3. **Verifikasi Source**: Jika dokumen tidak muncul atau tidak diperbarui, pastikan mapping esakip_type sudah benar dan kolom source pada data lama bukan bertuliskan source = 'upload'.

### Efisiensi dan Akurasi Data
Sistem ini terus dikembangkan untuk mendukung akuntabilitas kinerja yang lebih baik dan efisien. Penggunaan flag is_n_minus_1 dan pelacakan relasi predecessor_opd_id adalah bagian dari komitmen kami untuk menyediakan data yang akurat bagi seluruh perangkat daerah, terutama bagi **OPD baru** yang sedang dalam masa transisi organisasi.

Integrasi cerdas ini diharapkan dapat meningkatkan efektivitas evaluasi kinerja instansi pemerintah secara keseluruhan di Kabupaten Trenggalek, menjamin keabsahan data, dan mempermudah pelaporan kepada pimpinan daerah secara real-time.

---

### Perintah Artisan Terkait
Untuk keperluan debugging atau sinkronisasi massal via terminal/command line, tersedia perintah yang dapat dipanggil melalui php artisan:

```bash
# Contoh perintah untuk trigger sync via CLI
php artisan esakip:sync --tahun=2026 --opd=all
```

*Dokumen ini diperbarui secara berkala mengikuti perkembangan fitur integrasi KKE-SAKIP. Terakhir diperbarui: Mei 2026.*


### Dokumentasi Tambahan untuk Verifikator
Verifikator dapat memantau proses sinkronisasi melalui menu Log. Flag is_n_minus_1 membantu memastikan bahwa dokumen yang ditinjau adalah dokumen yang relevan dengan periode penilaian. Jika terdapat OPD baru yang belum memiliki dokumen sejarah, sistem akan secara otomatis merujuk ke predecessor_opd_id sesuai dengan konfigurasi tahun_mulai_berlaku yang telah ditetapkan oleh Admin.

Proses reorganisasi seringkali menyebabkan data terfragmentasi, namun dengan sistem ini, seluruh kriteria penilaian akan tetap memiliki bukti dukung yang lengkap baik dari source = 'upload' maupun source = 'esakip'.

---

### Lampiran Teknis SinkronData
Berikut adalah rincian endpoint yang digunakan oleh EsakipSyncService saat memproses SinkronData:

| Endpoint | Deskripsi | Parameter |
|----------|-----------|-----------|
| /list-dokumen | Mendapatkan daftar tipe dokumen | tahun |
| /dokumen/{type} | Mengambil detail dokumen | tahun, opd |

Sistem ini menjamin smart-merge yang efisien tanpa mengganggu data yang sudah ada. Setiap kali flag is_n_minus_1 digunakan, sistem akan melakukan validasi ganda terhadap predecessor_opd_id jika tahun dokumen jatuh sebelum tahun_mulai_berlaku.

Penanganan OPD baru dilakukan secara transparan oleh sistem. Admin hanya perlu memastikan mapping predecessor_opd_id dan tahun_mulai_berlaku sudah sesuai dengan data kepegawaian dan struktur organisasi yang berlaku.

---
Pembaruan Terakhir: 24 Mei 2026
Tim Pengembang KKE-SAKIP


---

## ⚠️ Edge Cases yang Di-handle Sistem

### 1. Dokumen Tidak Ditemukan di E-SAKIP
- Status: `no_document`
- Penilaian tidak dibuat
- Tercatat di `riwayat_sinkron` dengan status `no_document`

### 2. Upload Manual Sudah Ada (source = 'upload')
- Sistem SKIP sinkronisasi untuk penilaian tersebut
- Data manual tidak pernah ditimpa oleh sync otomatis
- Log: "Skipped - Manual upload"

### 3. Re-Sync (Sudah Pernah Sync, source = 'esakip')
- Sistem melakukan Smart Merge
- Dokumen baru dari API ditambahkan ke link_file yang ada
- Dokumen lama yang masih ada di API dipertahankan
- Dedup berdasarkan URL (primary) dan timestamp filename (secondary)

### 4. OPD Tanpa esakip_opd_id
- OPD yang tidak memiliki `esakip_opd_id` di-skip otomatis
- Pesan error: "Tidak ada OPD dengan mapping esakip_opd_id"

### 5. API E-SAKIP Error/Timeout
- Retry otomatis hingga 3x (konfigurasi: `ESAKIP_SYNC_RETRY`)
- Status: `failed` jika tetap gagal setelah retry
- Error dicatat di `riwayat_sinkron` dan Laravel Log

### 6. Dokumen Bersama Pemkab (Shared Documents)
- Dokumen seperti RPJMD yang berlaku untuk semua OPD
- Diambil dari `esakip_opd_id = 1` (Pemkab Trenggalek)
- Didistribusikan ke semua OPD yang di-filter
- Tidak terpengaruh oleh `predecessor_opd_id` OPD individual

## 📊 Logging & Riwayat Sinkronisasi

Setiap proses sinkronisasi dicatat di tabel `riwayat_sinkron`:

| Kolom | Keterangan |
|-------|-----------|
| `opd_id` | OPD yang di-sync |
| `tahun_id` | Tahun evaluasi |
| `document_type` | Jenis dokumen (renja, iku, dll) |
| `document_name` | Nama dokumen spesifik |
| `status` | `success`, `failed`, `partial`, `no_document` |
| `affected_count` | Jumlah penilaian yang terisi |
| `auto_verified_count` | Jumlah yang auto-verified |
| `synced_at` | Timestamp sinkronisasi |

### Melihat Riwayat
Riwayat sinkronisasi dapat dilihat di halaman `/sinkron-data` bagian bawah.
Admin dapat menghapus riwayat lama via tombol "Hapus Riwayat" (truncate tabel).

### Laravel Log
Detail teknis tersedia di `storage/logs/laravel.log`:
- Info: setiap dokumen yang berhasil di-fetch dan di-merge
- Warning: dokumen tidak ditemukan di API
- Error: kegagalan koneksi API atau exception

## ⚙️ Konfigurasi Lengkap

### config/esakip.php
`php
return [
    'api_base_url' => env('ESAKIP_API_URL', 'https://e-sakip.trenggalekkab.go.id/api'),
    'sync' => [
        'timeout'     => env('ESAKIP_SYNC_TIMEOUT', 60),
        'retry_count' => env('ESAKIP_SYNC_RETRY', 3),
    ],
    'document_types' => [
        'rpjmd'                    => 'RPJMD',
        'proses-bisnis'            => 'Proses Bisnis',
        'cascading'                => 'Pohon Kinerja (Cascading)',
        'renstra'                  => 'Renstra',
        'iku'                      => 'IKU',
        'iki'                      => 'IKI',
        'renja'                    => 'Renja',
        'perjanjian-kinerja'       => 'Perjanjian Kinerja',
        'rencana-aksi'             => 'Rencana Aksi',
        'lppd'                     => 'LPPD',
        'lkjip'                    => 'LKJIP',
        'lapkin'                   => 'Laporan Kinerja',
        'capaian'                  => 'Capaian',
        'mekanisme-pengumpulan-data' => 'Mekanisme Pengumpulan Data',
        'berita-acara-evaluasi'    => 'Berita Acara Evaluasi',
        'lhe-inspektorat'          => 'LHE Inspektorat',
        'lhe-kemenpan'             => 'LHE Kemenpan',
        'paparan-sakip'            => 'Paparan SAKIP',
        'rkpd'                     => 'RKPD',
        'lainnya'                  => 'Lainnya',
    ],
];
`

### .env Variables
`env
ESAKIP_API_URL=https://e-sakip.trenggalekkab.go.id/api
ESAKIP_SYNC_TIMEOUT=60
ESAKIP_SYNC_RETRY=3
`

### Menambah Jenis Dokumen Baru
Tambahkan entry baru di `config/esakip.php` bagian `document_types`:
`php
'nama-slug' => 'Label Tampilan',
`
Kemudian set `esakip_document_type = 'nama-slug'` pada `bukti_dukung` yang relevan via menu Mapping.

## 🔗 Referensi Teknis

| Komponen | Lokasi | Keterangan |
|----------|--------|-----------|
| Service utama | `app/Services/EsakipSyncService.php` | 1336 baris, semua logic sync |
| Livewire UI | `app/Livewire/Dashboard/SinkronData.php` | Entry point UI di `/sinkron-data` |
| Konfigurasi | `config/esakip.php` | API URL, timeout, document types |
| Model OPD | `app/Models/Opd.php` | `tahun_mulai_berlaku`, `predecessor_opd_id` |
| Model BuktiDukung | `app/Models/BuktiDukung.php` | `is_n_minus_1`, `esakip_document_type` |
| Model Penilaian | `app/Models/Penilaian.php` | `source`, `link_file`, `esakip_synced_at` |
| Riwayat | `app/Models/RiwayatSinkron.php` | Log per sync run |
| Analisis Flow | `.sisyphus/docs/SYNC_FLOW_ANALYSIS.md` | Issues & rekomendasi teknis |
| Troubleshooting | `TROUBLESHOOTING_SINKRONISASI.md` | Panduan diagnosis masalah |
