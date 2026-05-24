# DEAD_CODE — Inventory Kode/File/Tabel/Kolom Yang Tidak Digunakan

> **TUJUAN**: Mencegah developer baru ikut-ikutan extend kode yang sudah deprecated. Jangan revive yang sudah di sini, jangan duplikat fungsionalitasnya.

> Setiap entry punya: **lokasi**, **status**, **alasan deprecated**, **mengapa belum dihapus**, **rekomendasi**.

---

## 1. Models — File yang TIDAK lagi dipakai aktif

### 1.1 `FileBuktiDukung.php` — DEPRECATED ✗

**Lokasi**: [app/Models/FileBuktiDukung.php](file:///C:/laragon/www/kke-sakip/app/Models/FileBuktiDukung.php)
**Tabel**: `file_bukti_dukung` (masih ada di DB, dibuat oleh [migration 2025_12_01_033155](file:///C:/laragon/www/kke-sakip/database/migrations/2025_12_01_033155_create_file_bukti_dukungs_table.php))

**Status**: model + tabel masih ada, tapi pemakaian sudah dimatikan secara **bertahap**:

- [Penilaian.php:25-29](file:///C:/laragon/www/kke-sakip/app/Models/Penilaian.php#L25-L29) — relasi sudah dikomentari: `// Relasi ke FileBuktiDukung sudah tidak digunakan`
- [BuktiDukung.php:51-55](file:///C:/laragon/www/kke-sakip/app/Models/BuktiDukung.php#L51-L55) — relasi sudah dikomentari: `// Deprecated: File storage now in penilaian table`
- [Migration 2025_12_21_131315](file:///C:/laragon/www/kke-sakip/database/migrations/2025_12_21_131315_add_file_columns_to_penilaian_table.php) — DROP foreign key `file_bukti_dukung_id` di tabel `penilaian`, GANTI dengan kolom JSON `link_file`

**Alasan deprecated**: file storage dimigrasi dari "satu row per file" (di tabel `file_bukti_dukung`) menjadi **JSON array `penilaian.link_file`**. Format baru lebih sesuai untuk multi-file per bukti dukung dan integrasi dengan eSAKIP API yang return array of documents.

**Yang masih hidup (jangan kaget)**:
- [Opd.php:13-16](file:///C:/laragon/www/kke-sakip/app/Models/Opd.php#L13-L16) — relasi `file_bukti_dukung()` masih didefine. **Tidak pernah dipanggil.**
- [PenilaianVerifikator.php:13-16](file:///C:/laragon/www/kke-sakip/app/Models/PenilaianVerifikator.php#L13-L16) — relasi ke FileBuktiDukung masih ada (tidak relevan, model parent juga deprecated)
- [PenilaianHistory.php:55-58](file:///C:/laragon/www/kke-sakip/app/Models/PenilaianHistory.php#L55-L58) — relasi `file_perbaikan()` masih point ke FileBuktiDukung. **Kolom `file_perbaikan_id` selalu di-set NULL** di code path baru ([LembarKerja.php:1128](file:///C:/laragon/www/kke-sakip/app/Livewire/Dashboard/LembarKerja.php#L1128)).
- Method `LembarKerja::selectedFileBuktiDukung()` ([LembarKerja.php:641](file:///C:/laragon/www/kke-sakip/app/Livewire/Dashboard/LembarKerja.php#L641)) — namanya menyesatkan; sekarang return data dari `penilaian.link_file` JSON, BUKAN dari tabel `file_bukti_dukung`.
- Method `LembarKerja::deleteFileBuktiDukung()` ([LembarKerja.php:1371](file:///C:/laragon/www/kke-sakip/app/Livewire/Dashboard/LembarKerja.php#L1371)) — namanya menyesatkan; sekarang hapus entry dari `link_file` array, BUKAN row di `file_bukti_dukung`.
- Property `LembarKerja::$file_bukti_dukung` ([LembarKerja.php:53](file:///C:/laragon/www/kke-sakip/app/Livewire/Dashboard/LembarKerja.php#L53)) — Livewire FilePond binding (nama property aja, bukan terkait model).

**Mengapa belum dihapus**: kolom FK di tabel `penilaian_history` (`file_perbaikan_id`) masih di-define dengan FK constraint ke `file_bukti_dukung`. Migration drop akan butuh:
1. Drop FK constraint di `penilaian_history`
2. Drop kolom `file_perbaikan_id` di `penilaian_history`
3. Drop relasi di model
4. Drop tabel `file_bukti_dukung`
5. Hapus model `FileBuktiDukung`

**Rekomendasi**: kalau development berikutnya menyentuh `penilaian_history`, **manfaatkan kesempatan** untuk drop FK + kolom + tabel. Sampai itu terjadi: **JANGAN extend FileBuktiDukung. JANGAN tulis CRUD baru ke tabel `file_bukti_dukung`. JANGAN tulis history dengan `file_perbaikan_id` non-null.**

---

### 1.2 `PenilaianMandiri.php` — DEPRECATED ✗

**Lokasi**: [app/Models/PenilaianMandiri.php](file:///C:/laragon/www/kke-sakip/app/Models/PenilaianMandiri.php)
**Tabel**: `penilaian_mandiri` (dibuat oleh [migration 2025_12_01_033156](file:///C:/laragon/www/kke-sakip/database/migrations/2025_12_01_033156_create_penilaian_mandiris_table.php))

**Status**: tabel masih ada, tapi **TIDAK ada code path baru yang menulis ke sini**. Sudah digantikan oleh tabel UNIFIED `penilaian` (dibuat [migration 2025_12_20_141342](file:///C:/laragon/www/kke-sakip/database/migrations/2025_12_20_141342_create_penilaians_table.php)) dengan kolom `role_id` yang membedakan tier scoring (mandiri/verifikator/penjamin/penilai).

**Yang masih hidup (zombie relations)**:
- [Opd.php:18-22](file:///C:/laragon/www/kke-sakip/app/Models/Opd.php#L18-L22) — `penilaian_mandiri()` relasi
- [KriteriaKomponen.php:151](file:///C:/laragon/www/kke-sakip/app/Models/KriteriaKomponen.php#L151) — `hasMany(PenilaianMandiri::class, 'kriteria_komponen_id')`
- [TingkatanNilai.php:17-20](file:///C:/laragon/www/kke-sakip/app/Models/TingkatanNilai.php#L17-L20) — `penilaian_mandiri()` relasi

**Tidak ada code yang **memanggil** relasi ini.** Relasi-relasi tersebut adalah artifak peninggalan.

**Rekomendasi**: jangan write baru. Jangan extend model.

---

### 1.3 `PenilaianVerifikator.php` — DEPRECATED ✗

**Lokasi**: [app/Models/PenilaianVerifikator.php](file:///C:/laragon/www/kke-sakip/app/Models/PenilaianVerifikator.php)
**Tabel**: `penilaian_verifikator` (dibuat oleh [migration 2025_12_01_033157](file:///C:/laragon/www/kke-sakip/database/migrations/2025_12_01_033157_create_penilaian_verifikators_table.php))

**Status**: sama dengan PenilaianMandiri — superseded oleh tabel UNIFIED `penilaian`.

**Zombie relations**:
- [Opd.php:23-26](file:///C:/laragon/www/kke-sakip/app/Models/Opd.php#L23-L26) — `penilaian_verifikator()`
- [Role.php:23-26](file:///C:/laragon/www/kke-sakip/app/Models/Role.php#L23-L26) — `penilaian_verifikator()`
- [PenilaianMandiri.php:28-31](file:///C:/laragon/www/kke-sakip/app/Models/PenilaianMandiri.php#L28-L31) — `hasMany(PenilaianVerifikator::class, 'penilaian_mandiri_id')`
- [FileBuktiDukung.php:27-30](file:///C:/laragon/www/kke-sakip/app/Models/FileBuktiDukung.php#L27-L30) — `belongsTo(PenilaianVerifikator::class, ...)`

**Tidak ada call site aktif.**

---

### 1.4 `PenilaianPenjamin.php` — DEPRECATED ✗

**Lokasi**: [app/Models/PenilaianPenjamin.php](file:///C:/laragon/www/kke-sakip/app/Models/PenilaianPenjamin.php)
**Tabel**: `penilaian_penjamin` (dibuat oleh [migration 2025_12_16_123358](file:///C:/laragon/www/kke-sakip/database/migrations/2025_12_16_123358_create_penilaian_penjamins_table.php))

**Status**: superseded oleh tabel UNIFIED `penilaian`. **Tidak ada relasi balik dari model lain. Tidak ada call site.**

---

### 1.5 `PenilaianPenilai.php` — DEPRECATED ✗

**Lokasi**: [app/Models/PenilaianPenilai.php](file:///C:/laragon/www/kke-sakip/app/Models/PenilaianPenilai.php)
**Tabel**: `penilaian_penilai` (dibuat oleh [migration 2025_12_16_123407](file:///C:/laragon/www/kke-sakip/database/migrations/2025_12_16_123407_create_penilaian_penilais_table.php))

**Status**: superseded oleh tabel UNIFIED `penilaian`. **Tidak ada relasi balik. Tidak ada call site.**

---

### Ringkasan: yang menggantikan keempat tabel di atas

```
SEBELUM (4 tabel terpisah):
   penilaian_mandiri      (untuk OPD)
   penilaian_verifikator  (untuk verifikator, link ke penilaian_mandiri_id)
   penilaian_penjamin     (untuk penjamin)
   penilaian_penilai      (untuk penilai)

SESUDAH (1 tabel UNIFIED, sejak 2025-12-20):
   penilaian
     (kriteria_komponen_id, opd_id, role_id, bukti_dukung_id?, tingkatan_nilai_id?,
      is_verified?, keterangan?, link_file json?, is_perubahan, source enum,
      esakip_document_id?, esakip_synced_at?, page_number?)
```

Setiap tier scoring sekarang hanyalah **row dengan `role_id` berbeda** di tabel `penilaian`. Lihat [Penilaian.php](file:///C:/laragon/www/kke-sakip/app/Models/Penilaian.php) sebagai sumber kebenaran.

---

## 2. Livewire Components — Komponen yang TIDAK terpakai

### 2.1 `Dashboard\SinkronDokumen.php` — DEAD ✗

**Lokasi**: [app/Livewire/Dashboard/SinkronDokumen.php](file:///C:/laragon/www/kke-sakip/app/Livewire/Dashboard/SinkronDokumen.php)
**View**: [resources/views/livewire/dashboard/sinkron-dokumen.blade.php](file:///C:/laragon/www/kke-sakip/resources/views/livewire/dashboard/sinkron-dokumen.blade.php)

**Status**: komponen + view ada, **tetapi tidak terdaftar di [routes/web.php](file:///C:/laragon/www/kke-sakip/routes/web.php)**. Tidak ada cara untuk mengaksesnya dari UI. Sidebar di [components/layouts/app.blade.php](file:///C:/laragon/www/kke-sakip/resources/views/components/layouts/app.blade.php) tidak punya link ke sini.

**Yang aktif menggantikan**: `SinkronData.php` (route `/sinkron-data`). Keduanya melakukan hal serupa (preview/process sync ke EsakipSyncService).

**Perbedaan**: `SinkronDokumen` punya `sync_mode` (merge/replace/skip), `SinkronData` tidak (sudah pakai smart-merge sebagai default). Lihat [PANDUAN_SINKRONISASI_ESAKIP.md](file:///C:/laragon/www/kke-sakip/PANDUAN_SINKRONISASI_ESAKIP.md) — dokumentasi panduan dibuat ketika `SinkronDokumen` masih relevan, sekarang sudah kadaluarsa.

**Rekomendasi**: hapus saat ada cleanup berikut — file PHP, view, dan referensi import jika ada.

---

## 3. Routes — Komentar Mati

### 3.1 Auth-only middleware group (dikomentari)

**Lokasi**: [routes/web.php:30-37](file:///C:/laragon/www/kke-sakip/routes/web.php#L30-L37)

```php
// Route::middleware(['auth'])->group(function () {
//     Route::get('/dashboard', Dashboard::class)->name('dashboard');
//     Route::get('/mapping', Mapping::class)->name('mapping');
//     Route::get('/lembar-kerja', LembarKerja::class)->name('lembar-kerja');
//     Route::get('/lembar-kerja/sub-komponen/{sub_komponen_id}/kriteria-komponen/{kriteria_komponen_id}/bukti-dukung', BuktiDukung::class)->name('lembar-kerja.kriteria-komponen.bukti-dukung');
//     Route::get('/lembar-kerja/sub-komponen/{sub_komponen_id}/kriteria-komponen', KriteriaKomponen::class)->name('lembar-kerja.kriteria-komponen');
//     Route::get('/pengaturan', Pengaturan::class)->name('pengaturan');
// });
```

**Status**: dikomentari. Inilah versi route lama (sebelum role-based middleware diterapkan). Termasuk route `/lembar-kerja/sub-komponen/.../kriteria-komponen/.../bukti-dukung` yang **tidak ada lagi** (sekarang Lembar Kerja punya semua state internal lewat session, tidak via URL params).

**Rekomendasi**: hapus blok komentar saat cleanup. Tidak ada nilai historis yang penting.

### 3.2 Welcome route (dikomentari)

**Lokasi**: [routes/web.php:19-21](file:///C:/laragon/www/kke-sakip/routes/web.php#L19-L21)

```php
// Route::get('/', function () {
//     return view('welcome');
// });
```

**Status**: GET `/` sekarang redirect ke `/login` (di group `'guest'` middleware). View `welcome.blade.php` masih ada di `resources/views/welcome.blade.php` tetapi tidak pernah dirender.

**Rekomendasi**: hapus baris komentar + view `welcome.blade.php`.

### 3.3 Sidebar admin section (dikomentari)

**Lokasi**: [resources/views/components/layouts/app.blade.php:367-379](file:///C:/laragon/www/kke-sakip/resources/views/components/layouts/app.blade.php#L367-L379)

Block menu dikomentari. Lihat di blade file langsung untuk konteks.

**Rekomendasi**: hapus saat lewat.

---

## 4. Seeders — File yang Tidak Dipanggil

### 4.1 `JenisNilai.php` (TANPA suffix `Seeder`)

**Lokasi**: [database/seeders/JenisNilai.php](file:///C:/laragon/www/kke-sakip/database/seeders/JenisNilai.php)

**Status**: kelas seeder kosong (method `run()` body cuma `//`). NAMANYA salah convention (harusnya `JenisNilaiSeeder`). **TIDAK dipanggil dari [DatabaseSeeder.php](file:///C:/laragon/www/kke-sakip/database/seeders/DatabaseSeeder.php)** (yang dipanggil adalah `JenisNilaiSeeder.php` yang benar).

**Alasan masih ada**: artifact peninggalan dari restructuring. Tidak ada efek karena tidak dipanggil.

**Rekomendasi**: hapus file ini (`JenisNilai.php`, bukan `JenisNilaiSeeder.php`).

---

## 5. Migrations — No-op (kosong tapi tetap ada)

### 5.1 `2026_01_13_121635_add_esakip_columns_to_bukti_dukung_table.php`

**Lokasi**: [database/migrations/2026_01_13_121635_add_esakip_columns_to_bukti_dukung_table.php](file:///C:/laragon/www/kke-sakip/database/migrations/2026_01_13_121635_add_esakip_columns_to_bukti_dukung_table.php)

**Status**: `up()` body kosong. **NO-OP.**

**Alasan**: migration kembar dengan timestamp `_121648`. Yang `_121635` ini dibikin lebih dulu lalu dihapus isinya, yang benar di-apply adalah `_121648`. Hapus akan break `migrate:fresh` ordering pada DB existing.

**Rekomendasi**: biarkan, tapi label "no-op" di file kalau perlu.

### 5.2 `2026_02_01_114203_add_status_perbaikan_to_penilaian_history_table.php`

**Lokasi**: [database/migrations/2026_02_01_114203_add_status_perbaikan_to_penilaian_history_table.php](file:///C:/laragon/www/kke-sakip/database/migrations/2026_02_01_114203_add_status_perbaikan_to_penilaian_history_table.php)

**Status**: `up()` body kosong. **NO-OP.** Sama pola dengan #5.1: kembar dengan `_114223` yang punya isi sebenarnya.

### 5.3 `2026_05_10_120200_fix_predecessor_opd_id_to_esakip_id.php`

**Lokasi**: [database/migrations/2026_05_10_120200_fix_predecessor_opd_id_to_esakip_id.php](file:///C:/laragon/www/kke-sakip/database/migrations/2026_05_10_120200_fix_predecessor_opd_id_to_esakip_id.php)

**Status**: SECARA EKSPLISIT mengaku no-op. Komentar di file: `// No operation needed - predecessor_opd_id sudah di-setup dengan benar di migration 120000`.

**Alasan**: ada upaya men-"fix" di migration `_120200` yang ternyata sudah ditangani oleh `_120000`. Tetap dibiarkan untuk consistency commit history.

---

## 6. Kolom Tabel — Tidak Dipakai / Setengah Aktif

### 6.1 `penilaian.file_bukti_dukung_id` — DROPPED ✗

**Lokasi**: tabel `penilaian`. Original di [migration 2025_12_20_141342](file:///C:/laragon/www/kke-sakip/database/migrations/2025_12_20_141342_create_penilaians_table.php), DROPPED di [migration 2025_12_21_131315](file:///C:/laragon/www/kke-sakip/database/migrations/2025_12_21_131315_add_file_columns_to_penilaian_table.php).

**Status**: kolom **TIDAK ADA** di skema sekarang. Tetapi relasi di [Penilaian.php](file:///C:/laragon/www/kke-sakip/app/Models/Penilaian.php#L25-L29) yang dikomentari masih point ke kolom ini.

### 6.2 `penilaian_history.file_perbaikan_id` — UNUSED (tapi masih ada)

**Lokasi**: tabel `penilaian_history`, ditambah oleh [migration 2026_02_01_114223](file:///C:/laragon/www/kke-sakip/database/migrations/2026_02_01_114223_add_status_perbaikan_to_penilaian_history_table.php).

**Status**: kolom + FK constraint masih ada, tapi **selalu di-set NULL** oleh code path baru ([LembarKerja.php:1128](file:///C:/laragon/www/kke-sakip/app/Livewire/Dashboard/LembarKerja.php#L1128)). Komentar: `'file_perbaikan_id' => null, // File tersimpan di link_file array, tidak perlu record terpisah`.

**Rekomendasi**: drop kolom + FK saat menyentuh schema `penilaian_history` (digabung ke task drop FileBuktiDukung).

### 6.3 `opd.active_until_year` — TIDAK PERNAH DI-SET / DIBACA

**Lokasi**: tabel `opd`, kolom asli di [migration 0000_12_01_032452](file:///C:/laragon/www/kke-sakip/database/migrations/0000_12_01_032452_create_opds_table.php) line 17.

**Status**: kolom ada, **tidak ada satu pun query** yang baca atau tulis. Konsep ini digantikan oleh `tahun_mulai_berlaku` + `predecessor_opd_id` yang ditambah [migration 2026_05_10_120000](file:///C:/laragon/www/kke-sakip/database/migrations/2026_05_10_120000_add_opd_mapping_columns_to_opd_table.php).

**Rekomendasi**: drop kolom kalau ada cleanup.

### 6.4 `bukti_dukung.sync_status` (enum)

**Lokasi**: tabel `bukti_dukung`, ditambah [migration 2026_01_13_121648](file:///C:/laragon/www/kke-sakip/database/migrations/2026_01_13_121648_add_esakip_columns_to_bukti_dukung_table.php).

**Status**: kolom ada (`'not_synced' | 'synced' | 'failed'`), tapi **tidak ada code yang update dari `'not_synced'` ke status lain**. EsakipSyncService tidak update kolom ini per bukti_dukung — sync status dilacak di tabel `riwayat_sinkron` per (opd, document_type) instead.

**Rekomendasi**: drop kolom `sync_status` + `last_synced_at` kalau memang tidak dipakai. Atau implementasikan jika butuh.

---

## 7. Root-Level Scratch PHP Scripts

> File-file di project root yang **bukan** part dari boot-path Laravel, tapi script maintenance/debug standalone yang dijalankan manual (`php <file>.php`).

### 7.1 Daftar 14 file scratch

```
test-sync.php                       — test sync flow eSAKIP, hapus data lama
test-sync-shared.php                — test khusus shared documents (Pemkab)
test-preview-shared.php             — test preview shared documents
test-validate-rpjmd.php             — test validate RPJMD documents
test-skip-upload.php                — test skip-on-upload behavior
test-is-perubahan.php               — test is_perubahan kategori dari API
validate-sync.php                   — validate result sync
validate-multiple-files.php         — validate multi-file upload result
validate-is-perubahan.php           — validate is_perubahan handling
check-kriteria.php                  — check kriteria_komponen state
check-linkfile-structure.php        — verify struktur link_file array
check_schema.php                    — introspect SQLite schema
debug_rpjmd.php                     — debug RPJMD-specific issue
syncPenilaian_NEW_TEMPLATE.php      — TEMPLATE (komentar besar) untuk re-write method syncPenilaian, NOT executable
```

**Status**: tidak ada satupun yang di-autoload. Tidak masuk ke composer autoload. Tidak ada test runner yang menjalankan ini. **Hanya dipanggil manual.**

**Alasan masih ada**: development debugging, kalau diperlukan ulang. Tapi mereka **mengotori root** dan menyulitkan navigasi.

**Rekomendasi**: **PINDAHKAN** semua ke `tmp/` (gitignore) atau `.sisyphus/scratch/` saat ada cleanup. JANGAN tambah file scratch baru di root.

---

## 8. Documentation Files (root) — Status

> 13 file `.md` di root. Yang masih relevan vs. yang sudah outdated:

| File | Status | Catatan |
|------|--------|---------|
| `README.md` | KEEP | Stock Laravel; bisa diganti project-specific README |
| `IMPLEMENTATION_STATUS.md` | OUTDATED | Berisi status sync implementation yang sudah selesai |
| `SMART_SYNC_STRATEGY.md` | KEEP | Dokumentasi strategi smart-sync; akurat |
| `PANDUAN_SINKRONISASI_ESAKIP.md` | OUTDATED-PARTIAL | Mention `SinkronDokumen` dengan `sync_mode`. Yang aktif `SinkronData` tanpa sync_mode. |
| `DATABASE_SINKRONISASI.md` | KEEP | Dokumentasi struktur DB untuk sync; akurat |
| `STRUKTUR_LINK_FILE.md` | KEEP | Dokumentasi struktur JSON `link_file`; akurat |
| `TROUBLESHOOTING_SINKRONISASI.md` | KEEP | Troubleshooting guide |
| `API_DOCUMENTATION.md` | OUTDATED | Mention REST API `/api/master/*` dan `/api/dokumen/*`. **Tidak ada `routes/api.php`** di project ini. Endpoint-endpoint itu **bukan** milik aplikasi ini, melainkan API eSAKIP eksternal. |
| `PANDUAN_TEMPLATE_WORD.md` | KEEP | Panduan PhpWord template (relevan untuk Ekspor Laporan) |
| `CARA_BUAT_TABEL_WORD.md` | KEEP | Panduan tambahan |
| `PANDUAN_TABEL_PERBANDINGAN.md` | KEEP | Panduan tambahan |
| `PANDUAN_PERBAIKI_TABEL_BAB2.md` | KEEP | Panduan tambahan |
| `CARA_MEMPERBAIKI_TEMPLATE.md` | KEEP | Panduan tambahan |

**Rekomendasi**:
- `IMPLEMENTATION_STATUS.md` — hapus (status check-in sudah selesai)
- `API_DOCUMENTATION.md` — hapus atau rename ke `EXTERNAL_ESAKIP_API.md` dan jelaskan ini API eksternal, bukan internal
- `PANDUAN_SINKRONISASI_ESAKIP.md` — update untuk reflect SinkronData (tanpa sync_mode)

---

## 9. Public Folder — Vendor Bundles

**Lokasi**: [public/assets/](file:///C:/laragon/www/kke-sakip/public/assets) — 4109 file

**Status**: Bundle vendor (icons, fonts, JS libs) yang di-include via `<link>` / `<script>` tags langsung di [components/layouts/app.blade.php](file:///C:/laragon/www/kke-sakip/resources/views/components/layouts/app.blade.php). **Bukan** Vite output. Build template (Velzon/AdminLTE-style) yang dipertahankan apa-adanya.

**Yang DIPAKAI (cek dengan grep `asset('assets/...')`):**
- `assets/css/app.min.css`
- `assets/css/bootstrap.min.css`
- `assets/css/icons.min.css`
- `assets/libs/jquery/jquery.min.js`
- `assets/libs/preline/preline.js`
- `assets/libs/bootstrap/js/bootstrap.bundle.min.js`
- `assets/libs/feather-icons/feather.min.js`
- `assets/libs/list.js/list.min.js`
- `assets/libs/list.pagination.js/list.pagination.min.js`
- `assets/libs/lord-icon-element/lord-icon-element.js`
- `assets/js/plugins.js`
- `assets/js/app.js`

**Tidak terpakai langsung (tapi tergabung di bundle vendor)**: kemungkinan ratusan file di subfolder seperti `apexcharts/`, `jsvectormap/`, `flatpickr/`, dll yang tidak direferens dari blade. Tapi karena ini adalah pre-built theme, **JANGAN cabut piecemeal**. Resiko break style.

**Rekomendasi**: anggap `public/assets/` sebagai build output. Tidak hand-edit. Jika bermasalah dengan size, ganti ke approach Vite-only di kemudian hari (refactor besar).

---

## 10. Empty/Placeholder Test Files

**Lokasi**: 
- [tests/Feature/ExampleTest.php](file:///C:/laragon/www/kke-sakip/tests/Feature/ExampleTest.php) — stock Laravel example
- [tests/Unit/ExampleTest.php](file:///C:/laragon/www/kke-sakip/tests/Unit/ExampleTest.php) — stock Laravel example

**Status**: hanya assert `true === true`. **Tidak ada test sungguhan di project ini.** PHPUnit terkonfigurasi (`phpunit.xml` ada), tapi tidak ada test domain yang ditulis.

**Rekomendasi**: kalau pernah menulis test, hapus stub dan ganti dengan test asli. Sampai itu terjadi, biarkan.

---

## TL;DR — Cleanup Recommendations (in priority order)

| # | Aksi | Effort | Risiko | Manfaat |
|---|------|--------|--------|---------|
| 1 | Hapus 14 root-level scratch PHP scripts (pindah ke `tmp/`) | rendah | rendah | navigasi root jadi bersih |
| 2 | Hapus `database/seeders/JenisNilai.php` (yang tanpa Seeder suffix) | rendah | rendah | hilangkan duplikasi |
| 3 | Hapus `app/Livewire/Dashboard/SinkronDokumen.php` + view | rendah | rendah | hilangkan dead component |
| 4 | Update `PANDUAN_SINKRONISASI_ESAKIP.md` agar ke `SinkronData` saja | rendah | rendah | dokumentasi konsisten |
| 5 | Drop kolom `opd.active_until_year`, `bukti_dukung.sync_status`, `bukti_dukung.last_synced_at` | sedang | sedang | reduce schema noise |
| 6 | Drop FileBuktiDukung tier (model + tabel + FK + relasi sisa) | tinggi | sedang-tinggi | perlu migration besar; lakukan saat menyentuh penilaian_history |
| 7 | Drop 4 model deprecated (PenilaianMandiri/Verifikator/Penjamin/Penilai) + 4 tabel terkait | tinggi | rendah-sedang | major cleanup; pastikan tidak ada query masuk |
| 8 | Hapus blok komentar di `routes/web.php` (auth-only group, welcome) | rendah | rendah | trivial |
| 9 | Hapus `welcome.blade.php` view | rendah | rendah | trivial |
| 10 | Hapus `IMPLEMENTATION_STATUS.md` dan `API_DOCUMENTATION.md` | rendah | rendah | reduce confusion |

**Aturan operasi cleanup**:
- LAKUKAN dalam commit terpisah per item.
- TEST `migrate:fresh --seed` setiap kali drop migration/kolom.
- JANGAN drop tabel atau kolom di `migrate:fresh` development saja — buat migration drop yang explicit.
