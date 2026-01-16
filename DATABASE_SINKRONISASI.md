# Struktur Database untuk Sinkronisasi esakip

## 📊 Perubahan Database

### 1. Tabel `bukti_dukung`

**Kolom Baru untuk Mapping esakip:**

| Kolom                  | Type      | Nullable | Default      | Keterangan                                       |
| ---------------------- | --------- | -------- | ------------ | ------------------------------------------------ |
| `esakip_document_type` | string    | YES      | NULL         | Jenis dokumen di esakip (renja, iku, lkjip, dll) |
| `esakip_document_code` | string    | YES      | NULL         | Kode/ID dokumen di esakip (opsional)             |
| `sync_status`          | enum      | NO       | 'not_synced' | Status sinkronisasi: not_synced, synced, failed  |
| `last_synced_at`       | timestamp | YES      | NULL         | Waktu terakhir disinkronkan                      |

**Contoh Data:**

```php
[
    'nama' => 'Renja',
    'esakip_document_type' => 'renja',
    'esakip_document_code' => null,
    'sync_status' => 'synced',
    'last_synced_at' => '2026-01-13 12:30:00',
    'is_auto_verified' => true, // Sudah ada sebelumnya
]
```

---

### 2. Tabel `penilaian`

**Kolom Baru untuk Source Tracking:**

| Kolom                | Type      | Nullable | Default  | Keterangan                                       |
| -------------------- | --------- | -------- | -------- | ------------------------------------------------ |
| `source`             | enum      | NO       | 'upload' | Sumber file: upload (manual), esakip (dari sync) |
| `esakip_document_id` | string    | YES      | NULL     | ID dokumen di esakip untuk reference             |
| `esakip_synced_at`   | timestamp | YES      | NULL     | Waktu sinkronisasi dari esakip                   |

**Struktur `link_file` (JSON):**

```json
[
    {
        "path": "bukti-dukung/2024/1/document.pdf",
        "url": "https://esakip.example.com/storage/documents/123.pdf",
        "original_name": "Renja 2024.pdf",
        "size": 1024000
    }
]
```

**Contoh Data:**

```php
[
    'opd_id' => 1,
    'bukti_dukung_id' => 5,
    'role_id' => 2,
    'link_file' => [
        [
            'url' => 'http://localhost:8000/storage/dokumen/renja/renja-2024.pdf',
            'original_name' => 'Renja 2024.pdf',
            'from_esakip' => true
        ]
    ],
    'source' => 'esakip',
    'esakip_document_id' => '123',
    'esakip_synced_at' => '2026-01-13 12:30:00',
    'is_verified' => true, // Jika is_auto_verified = true
]
```

---

### 3. Tabel `riwayat_sinkron`

**Struktur Lengkap:**

| Kolom                 | Type      | Nullable | Default   | Keterangan                           |
| --------------------- | --------- | -------- | --------- | ------------------------------------ |
| `id`                  | bigint    | NO       | -         | Primary key                          |
| `opd_id`              | bigint    | NO       | -         | Foreign key ke tabel opd             |
| `tahun_id`            | bigint    | NO       | -         | Foreign key ke tabel tahun           |
| `document_type`       | string    | NO       | -         | Jenis dokumen (renja, iku, dll)      |
| `document_name`       | string    | YES      | NULL      | Nama dokumen spesifik                |
| `tahun_value`         | string    | NO       | -         | Nilai tahun (2024, 2025, dll)        |
| `file_url`            | text      | YES      | NULL      | URL file dokumen dari esakip         |
| `penilaian_ids`       | json      | YES      | NULL      | Array ID penilaian yang ter-affected |
| `affected_count`      | integer   | NO       | 0         | Jumlah penilaian yang terisi         |
| `auto_verified_count` | integer   | NO       | 0         | Jumlah yang auto-verified            |
| `status`              | enum      | NO       | 'success' | Status: success, failed, partial     |
| `synced_at`           | timestamp | YES      | NULL      | Waktu sinkronisasi                   |

**Contoh Data:**

```php
[
    'opd_id' => 1,
    'tahun_id' => 3,
    'document_type' => 'renja',
    'document_name' => 'Renja Dinas Kesehatan 2024',
    'tahun_value' => '2024',
    'file_url' => 'http://localhost:8000/storage/dokumen/renja/renja-2024.pdf',
    'penilaian_ids' => [45, 46, 47], // JSON array
    'affected_count' => 3,
    'auto_verified_count' => 2,
    'status' => 'success',
    'synced_at' => '2026-01-13 12:30:00',
]
```

---

## 🔄 Flow Sinkronisasi

### Step 1: Setup Mapping di Menu Mapping

```php
// Admin input di form Bukti Dukung
BuktiDukung::create([
    'nama' => 'Renja',
    'esakip_document_type' => 'renja', // Select dari config
    'is_auto_verified' => true, // Checkbox
    // ... kolom lainnya
]);
```

### Step 2: Proses Sinkronisasi

```php
// 1. Call API esakip
$response = Http::get("http://localhost:8000/api/dokumen/renja", [
    'tahun' => 2024,
    'opd_id' => 1,
    'published_only' => true,
]);

// 2. Loop dokumen dari API
foreach ($response['data'] as $document) {
    // 3. Cari bukti_dukung yang match
    $buktiDukungList = BuktiDukung::where('tahun_id', $tahunId)
        ->where('esakip_document_type', 'renja')
        ->where(function($q) use ($document) {
            $q->where('nama', 'LIKE', "%{$document['keterangan']}%")
              ->orWhere('nama', 'LIKE', '%Renja%');
        })
        ->get();

    // 4. Update/Create penilaian untuk setiap bukti dukung
    $penilaianIds = [];
    foreach ($buktiDukungList as $buktiDukung) {
        $penilaian = Penilaian::updateOrCreate(
            [
                'opd_id' => $document['opd']['id'],
                'bukti_dukung_id' => $buktiDukung->id,
                'role_id' => $buktiDukung->role_id,
            ],
            [
                'link_file' => [[
                    'url' => $document['file_url'],
                    'original_name' => basename($document['file']),
                    'from_esakip' => true,
                ]],
                'source' => 'esakip',
                'esakip_document_id' => $document['id'],
                'esakip_synced_at' => now(),
                'is_verified' => $buktiDukung->is_auto_verified,
            ]
        );

        $penilaianIds[] = $penilaian->id;

        // 5. Update status di bukti_dukung
        $buktiDukung->update([
            'sync_status' => 'synced',
            'last_synced_at' => now(),
        ]);
    }

    // 6. Log ke riwayat_sinkron
    RiwayatSinkron::create([
        'opd_id' => $document['opd']['id'],
        'tahun_id' => $tahunId,
        'document_type' => 'renja',
        'document_name' => $document['keterangan'],
        'tahun_value' => $document['tahun']['tahun'],
        'file_url' => $document['file_url'],
        'penilaian_ids' => $penilaianIds,
        'affected_count' => count($penilaianIds),
        'auto_verified_count' => $buktiDukungList->where('is_auto_verified', true)->count(),
        'status' => 'success',
        'synced_at' => now(),
    ]);
}
```

---

## 📋 Query Examples

### 1. Get Bukti Dukung yang Belum Di-sync

```php
$unsyncedBuktiDukung = BuktiDukung::where('sync_status', 'not_synced')
    ->whereNotNull('esakip_document_type')
    ->where('tahun_id', $tahunId)
    ->get();
```

### 2. Get Penilaian dari esakip untuk OPD Tertentu

```php
$esakipPenilaian = Penilaian::where('source', 'esakip')
    ->where('opd_id', $opdId)
    ->where('tahun_id', $tahunId)
    ->with(['bukti_dukung', 'role'])
    ->get();
```

### 3. Get Riwayat Sinkronisasi Terbaru

```php
$recentSync = RiwayatSinkron::with(['opd', 'tahun'])
    ->where('status', 'success')
    ->orderBy('synced_at', 'desc')
    ->limit(10)
    ->get();
```

### 4. Get Bukti Dukung dengan Auto-Verification

```php
$autoVerifiedBuktiDukung = BuktiDukung::where('is_auto_verified', true)
    ->where('sync_status', 'synced')
    ->whereNotNull('esakip_document_type')
    ->with(['penilaian' => function($q) {
        $q->where('source', 'esakip')
          ->where('is_verified', true);
    }])
    ->get();
```

### 5. Check Dokumen Sudah Di-sync atau Belum

```php
$isSynced = BuktiDukung::where('id', $buktiDukungId)
    ->where('sync_status', 'synced')
    ->exists();
```

---

## ⚠️ Important Notes

1. **File Storage**: File TIDAK didownload, hanya URL yang disimpan di `link_file` JSON
2. **Duplikasi Nama**: Bukti dukung dengan nama sama akan ter-sync semua (shared file)
3. **Auto-Verification**: Hanya terjadi jika `is_auto_verified = true` di bukti_dukung
4. **Status Tracking**: Gunakan `sync_status` untuk monitoring
5. **History**: Semua sinkronisasi tercatat di `riwayat_sinkron`

---

## 🎯 Next Steps

1. ✅ **Database siap** - Semua kolom sudah ditambahkan
2. ✅ **Models updated** - Cast dan relationships sudah ada
3. ✅ **Config ready** - File config/esakip.php sudah dibuat
4. ⏳ **Service Class** - Perlu dibuat `EsakipSyncService`
5. ⏳ **Livewire Component** - Perlu dibuat `SinkronDokumen.php`
6. ⏳ **UI Form** - Update Mapping.php untuk input esakip_document_type

---

## 📞 API Integration Ready

Struktur database sudah siap untuk integrasi dengan esakip API sesuai dokumentasi:

-   ✅ Support semua jenis dokumen (Renja, IKU, LKJIP, dll)
-   ✅ Tracking source (upload vs esakip)
-   ✅ Auto-verification support
-   ✅ Complete history logging
-   ✅ Fuzzy matching untuk nama dokumen
