# STRATEGI SINKRONISASI SMART SYNC

## 1. Unique Identifier Dokumen

Setiap dokumen dari E-SAKIP diidentifikasi dengan:

```php
$documentIdentifier = [
    'url' => $doc['file'], // Primary key
    'timestamp' => extractTimestamp($doc['file']), // dari nama file _1761182369.pdf
    'publish_date' => $doc['tanggal_publish'],
    'kategori' => $doc['kategori'] ?? 'induk',
];
```

### Contoh:
```
URL: .../Renstra_Induk_DinasKomunikasi_2025-2029_1761182369.pdf
Timestamp: 1761182369
Publish: "16 Oktober 2025"
Kategori: "induk"

→ Unique ID: SHA256(URL) atau langsung URL sebagai key
```

---

## 2. Logika Smart Merge (No-Mode)

Sistem tidak lagi menggunakan parameter `$syncMode`. Strategi merge bersifat otomatis dan cerdas untuk mencegah duplikasi data sambil memastikan dokumen terbaru masuk.

### Flowchart:

```
START
  ↓
Cek Penilaian OPD exists?
  ↓
  ├─ NO → CREATE penilaian baru dengan semua dokumen dari API
  │         - Buat link_file array dengan semua dokumen
  │         - Set source = 'esakip'
  │
  └─ YES → Cek source penilaian
           ↓
           ├─ source = 'upload' → SKIP (Proteksi input manual)
           │                      Log: "Skipped - Manual upload"
           │
           └─ source = 'esakip' → SMART MERGE (Default)
                                  ↓
                                  1. Ambil existing link_file array
                                  2. Ambil dokumen dari API
                                  3. Compare & Merge:
                                     For each dokumen dari API:
                                       ├─ Cek URL atau Timestamp ada di existing?
                                       │  ├─ YES → SKIP (no duplicate)
                                       │  └─ NO → ADD (dokumen baru)
                                  4. Update link_file dengan hasil merge
END
```

---

## 3. Source-Based Skip Protection

Untuk menjaga integritas data yang diinput manual oleh user, sistem menggunakan kolom `source` pada tabel `penilaian`.

- **`source = 'upload'`**: Penilaian dibuat atau diupdate secara manual oleh user melalui UI. Sinkronisasi otomatis akan **melewati (SKIP)** record ini sepenuhnya untuk mencegah tertimpanya bukti dukung lokal.
- **`source = 'esakip'`**: Penilaian dibuat oleh sistem sinkronisasi. Sistem akan melakukan **Smart Merge** jika ada dokumen baru dari API.
- **Penilaian Baru**: Setiap record penilaian yang dibuat otomatis saat sinkronisasi akan ditandai dengan `source = 'esakip'`.

*Citation: `EsakipSyncService.php:650-710`*

---

## 4. OPD Reorganisasi & Dokumen N-1

Sistem menangani perubahan struktur organisasi (OPD pecah/gabung/ganti nama) dan kebutuhan dokumen tahun sebelumnya (N-1) secara otomatis.

### Logika Penentuan Source:
1. **Pengecekan N-1**: Jika bukti dukung ditandai `is_n_minus_1 = true`, maka `sourceYear = currentYear - 1`.
2. **Pengecekan Reorganisasi**: 
   - Jika `sourceYear` < `opd.tahun_mulai_berlaku` DAN terdapat `opd.predecessor_opd_id`.
   - Maka `sourceEsakipOpdId` akan dialihkan ke ID milik predecessor (organisasi lama).
3. **Shared Documents**: Untuk dokumen tingkat Pemkab (Bersama), `sourceEsakipOpdId` selalu diset ke `1` (mengacu pada root/Pemkab di API E-SAKIP).

### Contoh Skenario:
OPD Baru dibentuk 2026 (`tahun_mulai_berlaku = 2026`) membutuhkan dokumen Renstra 2025 (N-1).
- `sourceYear` = 2025.
- Karena 2025 < 2026, sistem mengambil `predecessor_opd_id` (misal: ID 12) untuk query ke API E-SAKIP.

*Citation: `EsakipSyncService.php:355-410`*

---

## 5. Deteksi Perubahan Dokumen

### Strategi 1: By URL Only (Reliable)
```php
// Dokumen sama jika URL sama
$isSameDocument = ($existingFile['url'] === $apiDocument['file']);
```

### Strategi 2: By Timestamp in Filename (Recommended)
Sistem mengekstrak unix timestamp dari nama file (misal: `..._1761182369.pdf`) untuk mengenali apakah itu file yang sama meskipun URL-nya sedikit berubah.

---

## 6. Implementation Detail: smartMergeDocuments

Signature fungsi utama:
`protected function smartMergeDocuments($existingFiles, $apiDocuments)`

Logika inti:
1. Mulai dengan menyalin `$existingFiles` ke `$mergedFiles`.
2. Lakukan iterasi pada `$apiDocuments`.
3. Gunakan `documentExists()` untuk mengecek apakah dokumen API sudah ada di `$mergedFiles` (berdasarkan URL atau Timestamp).
4. Jika belum ada, push ke `$mergedFiles` dan naikkan counter `added_count`.
5. Kembalikan array berisi `$mergedFiles` dan `$added_count`.

*Citation: `EsakipSyncService.php:1306`*
