# SYNC_FLOW_ANALYSIS — Analisis Mendalam Alur Sinkronisasi eSAKIP

> **TUJUAN**: Dokumentasi lengkap alur sinkronisasi data dari API E-SAKIP ke aplikasi KKE-SAKIP, termasuk method map, feature handling, edge cases, issues, dan rekomendasi.

> **File Utama**: `app/Services/EsakipSyncService.php` (1336 baris)

---

## Section 1: Method Map

| # | Method | Lines | Visibility | Purpose |
|---|--------|-------|------------|---------|
| 1 | `__construct` | 20-25 | public | Inisialisasi config: apiBaseUrl, timeout, retryCount |
| 2 | `previewSync` | 35-200 | public | Preview dokumen yang akan di-sync tanpa eksekusi. Fetch dari API + shared docs, hitung estimasi |
| 3 | `processSync` | 211-329 | public | Entry point utama sinkronisasi. Orchestrate shared docs + OPD-specific docs |
| 4 | `syncDocumentForOpd` | 331-496 | protected | Sync satu document_type untuk satu OPD. Handle is_n_minus_1, predecessor, fetch + merge |
| 5 | `syncSharedDocument` | 507-611 | protected | Sync dokumen bersama (Pemkab opd_id=1) ke semua OPD yang di-filter |
| 6 | `syncPenilaian` | 623-751 | protected | Smart sync: CREATE baru / SKIP upload manual / SMART MERGE existing esakip data |
| 7 | `createAutoVerifiedPenilaian` | 763-804 | protected | Buat penilaian auto-verified untuk role verifikator jika bukti_dukung.is_auto_verified |
| 8 | `fetchSharedDocumentsFromEsakip` | 823-905 | protected | Fetch dokumen bersama dari Pemkab (hardcode opd=1) via API |
| 9 | `fetchDocumentsFromEsakipByOpdId` | 916-986 | protected | Fetch dokumen dari API dengan esakip_opd_id langsung (tanpa lookup) |
| 10 | `fetchDocumentsFromEsakip` | 997-1097 | protected | Wrapper: lookup OPD ID aplikasi -> esakip_opd_id, lalu fetch |
| 11 | `isPeriodeMatchYear` | 1107-1118 | protected | Cek apakah range periode (e.g. "2021 - 2026") mencakup tahun tertentu |
| 12 | `normalizeDocument` | 1126-1142 | protected | Normalize struktur dokumen dari API ke format internal |
| 13 | `filterLainnyaDocuments` | 1151-1190 | protected | Filter dokumen 'lainnya' yang keterangannya match dengan document_type |
| 14 | `buildFileObject` | 1240-1261 | protected | Build file object dengan metadata lengkap (url, timestamp, kategori, dll) |
| 15 | `documentExists` | 1271-1296 | protected | Dedup check: URL (primary) + timestamp (secondary) |
| 16 | `smartMergeDocuments` | 1306-1336 | protected | Gabungkan existing files dengan dokumen baru, skip duplikat |
| 17 | `getBuktiDukungForSync` | ~1195-1202 | protected | Get bukti dukung yang di-mapping ke document_type tertentu untuk tahun_id |
| 18 | `logSync` | 1210-1213 | protected | Log riwayat sinkronisasi ke tabel riwayat_sinkron |
| 19 | `extractTimestamp` | 1222-1232 | protected | Extract unix timestamp (10 digit) dari nama file E-SAKIP |

---

## Section 2: Feature Handling

### 2A. `is_n_minus_1` — Dokumen Tahun Sebelumnya

**Lokasi**: `syncDocumentForOpd` (line 360), `syncSharedDocument` (line 536)

**Mekanisme**:
- Setiap `BuktiDukung` punya flag `is_n_minus_1` (boolean)
- Jika `true`: `sourceYear = tahun->tahun - 1`
- Artinya: dokumen yang dibutuhkan adalah dari tahun SEBELUM tahun evaluasi

**Di `syncDocumentForOpd` (line 360-361)**:
```php
if ($buktiDukung->is_n_minus_1) {
    $sourceYear = $sourceYear - 1;
    // Lalu cek predecessor_opd_id...
}
```

**Di `syncSharedDocument` (line 536-537)**:
```php
if ($buktiDukung->is_n_minus_1) {
    $sourceYear = $sourceYear - 1;
    // sourceEsakipOpdId tetap 1 (Pemkab) — TIDAK pakai predecessor
}
```

**Perbedaan kunci**: Shared docs TIDAK pernah pakai `predecessor_opd_id` karena sumbernya selalu Pemkab (opd=1). Hanya `sourceYear` yang berubah.

---

### 2B. `predecessor_opd_id` — Reorganisasi OPD

**Lokasi**: `syncDocumentForOpd` (line 363-391)

**Kondisi trigger**:
```php
if (
    $opd->tahun_mulai_berlaku &&
    $sourceYear < $opd->tahun_mulai_berlaku &&
    $opd->predecessor_opd_id
) {
    $sourceEsakipOpdId = $opd->predecessor_opd_id;
}
```

**Semantik**:
- `predecessor_opd_id` adalah `esakip_opd_id` dari OPD pendahulu di sistem eSAKIP (BUKAN FK ke tabel `opd` lokal)
- Contoh dari OpdSeeder: Dinas Pendidikan (esakip_opd_id=43) punya predecessor_opd_id=5 (Disdikpora)
- Jika OPD belum eksis di `sourceYear`, fetch data dari OPD pendahulunya

**Dua jalur trigger** (line 363-391):
1. **is_n_minus_1 = true**: sourceYear sudah dikurangi 1, LALU cek predecessor
2. **is_n_minus_1 = false** (line 378-391): sourceYear = tahun evaluasi, tapi tetap cek apakah OPD sudah eksis di tahun itu

**Fallback**: Jika `predecessor_opd_id` tidak di-set → gunakan `$opd->esakip_opd_id` (OPD itu sendiri)

---

### 2C. `tahun_mulai_berlaku` — Threshold Eksistensi OPD

**Lokasi**: Bersamaan dengan `predecessor_opd_id` (line 363-391)

**Mekanisme**:
- Kolom `tahun_mulai_berlaku` di tabel `opd` (integer, tahun)
- Menentukan kapan OPD mulai eksis secara resmi
- Jika `sourceYear < tahun_mulai_berlaku` → OPD belum ada di tahun itu → pakai predecessor

**Operator**: Strict less-than (`<`)
- `sourceYear == tahun_mulai_berlaku` → OPD dianggap SUDAH eksis (pakai OPD sendiri)
- `sourceYear < tahun_mulai_berlaku` → OPD BELUM eksis (pakai predecessor)

**Contoh konkret** (dari OpdSeeder):
- Dinas Pendidikan: `tahun_mulai_berlaku = 2026`, `predecessor_opd_id = 5`
- Evaluasi tahun 2026, is_n_minus_1=true → sourceYear=2025 → 2025 < 2026 → fetch dari Disdikpora (opd=5)
- Evaluasi tahun 2026, is_n_minus_1=false → sourceYear=2026 → 2026 == 2026 → fetch dari Dinas Pendidikan sendiri (opd=43)

---

## Section 3: Edge Cases yang Sudah Di-handle

| # | Edge Case | Handling | Lokasi |
|---|-----------|----------|--------|
| 1 | OPD tanpa `esakip_opd_id` | Di-filter keluar dari opdList (`whereNotNull('esakip_opd_id')`) | `processSync:217`, `previewSync:41` |
| 2 | Tidak ada OPD valid | Throw exception dengan pesan jelas | `processSync:223`, `previewSync:47` |
| 3 | Penilaian source='upload' (manual) | SKIP — preserve user data, tidak di-overwrite | `syncPenilaian:692-706` |
| 4 | Dokumen duplikat (URL sama) | `documentExists()` cek URL primary | `documentExists:1285` |
| 5 | Dokumen duplikat (timestamp sama, URL beda) | `documentExists()` cek timestamp secondary | `documentExists:1290` |
| 6 | API return empty data | Return array kosong, log warning | `fetchDocumentsFromEsakipByOpdId:946-950` |
| 7 | API gagal (non-200) | Return array kosong, log warning | `fetchDocumentsFromEsakipByOpdId:980` |
| 8 | API timeout/exception | Catch exception, return array kosong | `fetchDocumentsFromEsakipByOpdId:982-984` |
| 9 | Dokumen 'lainnya' yang match type | Merge ke dokumen utama via `filterLainnyaDocuments` | `syncDocumentForOpd:413-426` |
| 10 | Shared docs duplikat dengan OPD docs | Dedup di preview via file URL comparison | `previewSync:133-146` |
| 11 | BuktiDukung tanpa mapping | Skip dengan status 'no_document' | `syncDocumentForOpd:336-345` |
| 12 | Periode-type documents | Filter by `isPeriodeMatchYear` — hanya include jika tahun dalam range | `fetchDocumentsFromEsakip:1068-1071` |
| 13 | Periode format tidak bisa di-parse | Return true (include document) — safe fallback | `isPeriodeMatchYear:1117` |
| 14 | BuktiDukung tanpa role_id untuk auto-verify | Log warning dan return tanpa create | `createAutoVerifiedPenilaian:769-774` |
| 15 | Penilaian verifikator sudah ada | Skip create (cek existing dulu) | `createAutoVerifiedPenilaian:778-783` |
| 16 | Sync gagal mid-process | DB transaction rollback per OPD/document_type | `syncDocumentForOpd:492-495`, `syncSharedDocument:606-610` |
| 17 | No changes detected pada smart merge | Return 'no_change' status | `syncPenilaian:739-750` |
| 18 | File URL null | `documentExists` return false (skip dedup) | `documentExists:1276-1278` |

---

## Section 4: Issues Found

### SYNC-001: previewSync tidak trace OPD reorganisasi flow yang sama dengan processSync

**Gejala**: Preview menampilkan data yang berbeda dari hasil aktual sync untuk OPD yang punya `predecessor_opd_id`.

**Lokasi**: `EsakipSyncService.php:87-88`

**Akar Penyebab**: `previewSync` (line 87) memanggil `fetchDocumentsFromEsakip($type, $tahun->tahun, $opd->id)` yang menggunakan `$opd->id` (ID aplikasi) dan lookup internal. Sedangkan `processSync` → `syncDocumentForOpd` (line 356-398) menghitung `sourceYear` dan `sourceEsakipOpdId` berdasarkan `is_n_minus_1` + `tahun_mulai_berlaku` + `predecessor_opd_id`. Preview TIDAK melakukan kalkulasi ini — selalu fetch dengan tahun evaluasi dan OPD sendiri.

**Dampak**: HIGH — User melihat preview yang tidak akurat. Bisa menampilkan 0 dokumen padahal sync aktual akan menemukan dokumen dari predecessor.

**Rekomendasi**: Refactor `previewSync` agar menggunakan logic sourceYear/sourceEsakipOpdId yang sama dengan `syncDocumentForOpd`.

---

### SYNC-002: predecessor_opd_id semantik-nya adalah esakip_opd_id, bukan FK ke tabel opd

**Gejala**: Nama kolom `predecessor_opd_id` menyiratkan foreign key ke tabel `opd`, padahal isinya adalah ID di sistem eSAKIP eksternal.

**Lokasi**: `EsakipSyncService.php:368` — `$sourceEsakipOpdId = $opd->predecessor_opd_id;`

**Akar Penyebab**: Kolom `predecessor_opd_id` di tabel `opd` (type: unsignedBigInteger) menyimpan `esakip_opd_id` dari OPD pendahulu. Contoh: Dinas Pendidikan punya `predecessor_opd_id = 5` yang merujuk ke esakip_opd_id Disdikpora, BUKAN ke `opd.id = 5`.

**Dampak**: MEDIUM — Menyesatkan developer baru. Bisa salah pakai sebagai FK dalam query JOIN.

**Rekomendasi**: Rename kolom ke `predecessor_esakip_opd_id` atau tambahkan comment di migration/model yang menjelaskan semantik.

---

### SYNC-003: Tidak ada validasi predecessor_opd_id yang invalid

**Gejala**: Jika `predecessor_opd_id` berisi esakip_opd_id yang tidak ada di sistem eSAKIP, API call akan return empty tanpa error yang jelas.

**Lokasi**: `EsakipSyncService.php:363-368`

**Akar Penyebab**: Tidak ada validasi apakah `predecessor_opd_id` valid sebelum digunakan sebagai parameter API. Jika eSAKIP tidak mengenal ID tersebut, response akan empty data (line 946-950) dan sync dianggap "tidak ada dokumen" — bukan error.

**Dampak**: MEDIUM — Silent data loss. OPD baru yang predecessor-nya salah di-set tidak akan pernah mendapat dokumen N-1, tanpa warning ke user.

**Rekomendasi**: Tambahkan validasi di `processSync` atau `syncDocumentForOpd`: jika fetch dengan predecessor return empty tapi fetch dengan OPD sendiri return data, log warning "predecessor mungkin invalid".

---

### SYNC-004: tahun_mulai_berlaku strict less-than — edge case tahun sama

**Gejala**: Untuk tahun YANG SAMA dengan `tahun_mulai_berlaku`, OPD dianggap sudah eksis dan TIDAK pakai predecessor.

**Lokasi**: `EsakipSyncService.php:365` — `$sourceYear < $opd->tahun_mulai_berlaku`

**Akar Penyebab**: Operator `<` (bukan `<=`). Jika evaluasi tahun 2026 dan OPD mulai berlaku 2026, maka sourceYear=2026 >= 2026 → pakai OPD sendiri. Ini BENAR untuk dokumen non-N-1. Tapi untuk is_n_minus_1=false di tahun pertama OPD eksis, OPD mungkin belum punya dokumen lengkap di eSAKIP.

**Dampak**: LOW — Secara logika benar (OPD sudah eksis di tahun itu), tapi bisa return empty jika OPD baru belum upload dokumen ke eSAKIP di tahun pertamanya.

**Rekomendasi**: Dokumentasikan behavior ini. Opsional: tambahkan fallback ke predecessor jika fetch dari OPD sendiri return empty di tahun pertama.

---

### SYNC-005: is_n_minus_1 tanpa batas bawah tahun

**Gejala**: `sourceYear = tahun->tahun - 1` tanpa minimum cap. Jika tahun evaluasi = 2020, sourceYear = 2019. Tidak ada pengecekan apakah eSAKIP punya data setua itu.

**Lokasi**: `EsakipSyncService.php:361` dan `EsakipSyncService.php:537`

**Akar Penyebab**: Tidak ada validasi minimum year. API eSAKIP mungkin tidak punya data untuk tahun yang sangat lama, tapi ini hanya menghasilkan empty response (bukan error).

**Dampak**: LOW — Tidak crash, hanya fetch sia-sia. Tapi bisa membingungkan user jika preview/sync selalu "0 dokumen" untuk tahun lama.

**Rekomendasi**: Tambahkan config `esakip.sync.min_year` dan skip fetch jika sourceYear < min_year.

---

### SYNC-006: syncSharedDocument hardcode sourceEsakipOpdId = 1 untuk Pemkab

**Gejala**: Dokumen bersama SELALU di-fetch dari opd=1 (Pemkab Trenggalek). Tidak ada fallback jika Pemkab reorganisasi atau ID berubah.

**Lokasi**: `EsakipSyncService.php:534` — `$sourceEsakipOpdId = 1;`

**Akar Penyebab**: Hardcoded value. Juga di `fetchSharedDocumentsFromEsakip` (line 833, 843). Asumsi bahwa Pemkab selalu opd=1 di eSAKIP.

**Dampak**: LOW — Dalam praktik, Pemkab (level kabupaten) sangat jarang reorganisasi. Tapi jika eSAKIP mengubah skema ID, semua shared doc sync akan gagal silently.

**Rekomendasi**: Pindahkan ke config: `config('esakip.pemkab_opd_id', 1)`.

---

### SYNC-007: Hubungan dengan clearRiwayat() di SinkronData — truncate tanpa konfirmasi

**Gejala**: Riwayat sinkronisasi bisa di-truncate dari UI tanpa konfirmasi yang memadai.

**Lokasi**: Cross-reference dengan `app/Livewire/Dashboard/SinkronData.php` (lihat KNOWN_BUGS.md BUG-007)

**Akar Penyebab**: `logSync()` di `EsakipSyncService.php:1210-1213` menyimpan riwayat ke `RiwayatSinkron`. Tapi di Livewire component `SinkronData`, ada method `clearRiwayat()` yang melakukan `RiwayatSinkron::truncate()` — menghapus SEMUA audit trail tanpa soft-delete atau backup.

**Dampak**: MEDIUM — Kehilangan audit trail sinkronisasi. Tidak bisa trace kapan dan apa yang di-sync.

**Rekomendasi**: Ganti truncate dengan soft-delete atau arsip ke tabel terpisah. Tambahkan konfirmasi modal di UI.

---

### SYNC-008: Sync berjalan synchronous dalam Livewire action — timeout risk

**Gejala**: Untuk multi-OPD (40+ OPD) × multi-document_type (10+ types), sync bisa memakan waktu sangat lama dan timeout.

**Lokasi**: `EsakipSyncService.php:211` (`processSync`) dipanggil dari Livewire action di `SinkronData.php`

**Akar Penyebab**: `processSync` melakukan nested loop: `foreach $opdList` × `foreach $documentTypes` × API calls (dengan retry 5x, delay 200ms). Untuk 40 OPD × 10 types = 400 API calls minimum. Dengan timeout 30s per call dan retry, worst case bisa puluhan menit.

**Dampak**: HIGH — PHP max_execution_time (biasanya 60-120s) akan kill proses. Livewire connection timeout. User melihat error tanpa tahu progress sebenarnya.

**Rekomendasi**: Implementasi queue-based sync. Dispatch job per OPD atau per document_type. Gunakan Livewire polling untuk progress update.

---

### SYNC-009: Race condition — dua admin trigger sync simultan

**Gejala**: Jika dua admin trigger sync untuk OPD/tahun yang sama secara bersamaan, bisa terjadi duplikasi penilaian atau data inconsistency.

**Lokasi**: `EsakipSyncService.php:353` (DB::beginTransaction) dan `syncPenilaian:633-636`

**Akar Penyebab**: `syncPenilaian` cek existing penilaian (line 633-636) lalu create jika tidak ada. Antara cek dan create, admin lain bisa sudah create. Tidak ada row-level lock (`lockForUpdate`) atau unique constraint yang mencegah duplikasi.

**Dampak**: MEDIUM — Duplikasi penilaian untuk OPD + bukti_dukung + role yang sama. Bisa menyebabkan double-counting di laporan.

**Rekomendasi**: Tambahkan `lockForUpdate()` pada query existing penilaian, atau tambahkan unique composite index pada `(opd_id, bukti_dukung_id, role_id)` di tabel penilaian.

---

### SYNC-010: documentExists() dedup bisa miss — URL baru + timestamp baru = dokumen baru

**Gejala**: Jika eSAKIP re-upload file dengan URL baru DAN timestamp baru (misal: file di-replace), `documentExists()` menganggapnya dokumen baru → duplikasi di `link_file`.

**Lokasi**: `EsakipSyncService.php:1271-1296`

**Akar Penyebab**: Dedup hanya berdasarkan URL (primary) dan timestamp (secondary). Tidak ada dedup berdasarkan konten semantik (misal: `keterangan` + `kategori` + `periode`). Jika admin eSAKIP delete file lama dan upload ulang, URL dan timestamp keduanya berubah.

**Dampak**: MEDIUM — `link_file` JSON array membengkak dengan duplikat semantik. UI menampilkan file yang sama berkali-kali.

**Rekomendasi**: Tambahkan tertiary dedup: jika `keterangan` + `kategori` + `periode` sama, anggap sebagai update (replace) bukan append.

---

### SYNC-011: fetchDocumentsFromEsakip menggunakan $opd->id (app ID) bukan esakip_opd_id secara langsung

**Gejala**: Method `fetchDocumentsFromEsakip` (line 997) menerima `$opdId` sebagai parameter, lalu lookup `Opd::find($opdId)` untuk mendapatkan `esakip_opd_id`. Ini menambah 1 query DB per call.

**Lokasi**: `EsakipSyncService.php:997-1003`

**Akar Penyebab**: Method ini adalah wrapper lama. `processSync` sudah punya `$opd` object dengan `esakip_opd_id`, tapi `previewSync` (line 87) masih memanggil wrapper ini dengan `$opd->id`.

**Dampak**: LOW — Performance: extra DB query per API call. Tidak ada bug fungsional.

**Rekomendasi**: Refactor `previewSync` untuk langsung pakai `fetchDocumentsFromEsakipByOpdId` dengan `$opd->esakip_opd_id`.

---

### SYNC-012: SSL verification disabled (`withoutVerifying()`) di semua API calls

**Gejala**: Semua HTTP calls ke eSAKIP API disable SSL verification.

**Lokasi**: `EsakipSyncService.php:839`, `EsakipSyncService.php:929`, `EsakipSyncService.php:1020`

**Akar Penyebab**: Comment di line 1020: "Disable SSL verification for development". Tapi ini ada di production code tanpa environment check.

**Dampak**: MEDIUM — Man-in-the-middle attack possible. Data dokumen pemerintah bisa di-intercept.

**Rekomendasi**: Kondisikan berdasarkan environment: `when(app()->environment('local'), fn($r) => $r->withoutVerifying())`. Atau fix SSL certificate di server eSAKIP.

---

### SYNC-013: Retry 5x dengan delay 200ms — tidak exponential backoff

**Gejala**: API calls retry 5 kali dengan fixed delay 200ms. Jika server eSAKIP overloaded, fixed retry bisa memperburuk situasi.

**Lokasi**: `EsakipSyncService.php:840`, `EsakipSyncService.php:932`, `EsakipSyncService.php:1021`

**Akar Penyebab**: Laravel HTTP `retry(5, 200)` menggunakan fixed delay. Tidak ada exponential backoff atau jitter.

**Dampak**: LOW — Bisa memperburuk load di server eSAKIP saat overloaded. Tapi dalam praktik, 5 retry × 200ms = 1 detik total delay, relatif ringan.

**Rekomendasi**: Gunakan exponential backoff: `retry(5, fn($attempt) => $attempt * 500)` atau `retry(5, 200, throw: false)` dengan custom backoff.

---

## Section 5: Recommendations

| # | Issue | Severity | Effort | Impact | Rekomendasi |
|---|-------|----------|--------|--------|-------------|
| 1 | SYNC-001 | HIGH | Medium | HIGH | Refactor `previewSync` agar pakai logic sourceYear/sourceEsakipOpdId yang sama dengan `syncDocumentForOpd` |
| 2 | SYNC-008 | HIGH | High | HIGH | Implementasi queue-based sync dengan job per OPD. Livewire polling untuk progress |
| 3 | SYNC-009 | MEDIUM | Low | HIGH | Tambahkan unique composite index `(opd_id, bukti_dukung_id, role_id)` + `lockForUpdate()` |
| 4 | SYNC-010 | MEDIUM | Medium | MEDIUM | Tambahkan tertiary dedup berdasarkan `keterangan` + `kategori` + `periode` |
| 5 | SYNC-012 | MEDIUM | Low | MEDIUM | Kondisikan `withoutVerifying()` hanya untuk environment local/testing |
| 6 | SYNC-002 | MEDIUM | Low | LOW | Rename kolom ke `predecessor_esakip_opd_id` atau tambahkan docblock |
| 7 | SYNC-003 | MEDIUM | Medium | MEDIUM | Validasi predecessor_opd_id: log warning jika fetch return empty |
| 8 | SYNC-007 | MEDIUM | Low | MEDIUM | Ganti truncate dengan soft-delete. Tambahkan konfirmasi UI |
| 9 | SYNC-006 | LOW | Low | LOW | Pindahkan Pemkab opd_id ke config |
| 10 | SYNC-011 | LOW | Low | LOW | Refactor previewSync untuk langsung pakai `fetchDocumentsFromEsakipByOpdId` |

**Prioritas implementasi yang disarankan**:
1. SYNC-009 (unique index) — effort rendah, mencegah data corruption
2. SYNC-001 (preview accuracy) — user-facing, bisa menyebabkan kebingungan
3. SYNC-012 (SSL) — security concern untuk data pemerintah
4. SYNC-008 (queue) — effort tinggi tapi menyelesaikan timeout fundamental

---

## Section 6: References

| # | File | Deskripsi |
|---|------|-----------|
| 1 | `app/Services/EsakipSyncService.php` | Service utama sinkronisasi (1336 baris) |
| 2 | `app/Livewire/Dashboard/SinkronData.php` | Livewire component yang memanggil sync |
| 3 | `config/esakip.php` | Konfigurasi API base URL, endpoints, document_types |
| 4 | `database/seeders/OpdSeeder.php` | Data OPD termasuk reorganisasi 2026 (predecessor mapping) |
| 5 | `app/Models/Penilaian.php` | Model penilaian — target sync |
| 6 | `app/Models/BuktiDukung.php` | Model bukti dukung — source mapping untuk sync |
| 7 | `app/Models/RiwayatSinkron.php` | Model audit trail sinkronisasi |
| 8 | `app/Models/Opd.php` | Model OPD — kolom esakip_opd_id, tahun_mulai_berlaku, predecessor_opd_id |
| 9 | `app/Models/Tahun.php` | Model tahun evaluasi |
| 10 | `.sisyphus/docs/KNOWN_BUGS.md` | Dokumentasi bug yang sudah diketahui (cross-ref BUG-007) |

---

> **Catatan**: Analisis ini berdasarkan pembacaan statis kode. Beberapa issue (terutama SYNC-008 timeout dan SYNC-009 race condition) memerlukan testing di environment dengan data real untuk konfirmasi dampak aktual.
