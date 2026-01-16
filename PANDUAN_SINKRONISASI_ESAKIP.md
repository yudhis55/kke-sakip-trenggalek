# Panduan Implementasi Sinkronisasi E-SAKIP

## ✅ Implementasi Lengkap - Siap Digunakan!

### 📋 Checklist Implementasi

-   [x] **Database Structure**

    -   [x] Tambah kolom esakip di tabel bukti_dukung
    -   [x] Tambah kolom source di tabel penilaian
    -   [x] Update struktur tabel riwayat_sinkron
    -   [x] Tambah enum 'no_document' di status

-   [x] **Service Layer**

    -   [x] EsakipSyncService lengkap dengan edge case handling
    -   [x] Preview sinkronisasi
    -   [x] Process sinkronisasi dengan progress callback
    -   [x] Merge/Replace/Skip mode
    -   [x] Auto-verification logic

-   [x] **Livewire Component**

    -   [x] SinkronDokumen.php dengan state management
    -   [x] Filter (Tahun, OPD, Dokumen)
    -   [x] Preview hasil
    -   [x] Progress tracking
    -   [x] Sync mode selection

-   [x] **UI/UX**

    -   [x] Form sinkronisasi dengan filter
    -   [x] Preview table dengan statistik
    -   [x] Progress bar real-time
    -   [x] Hasil sinkronisasi dengan breakdown
    -   [x] Riwayat sinkronisasi
    -   [x] Info box panduan

-   [x] **Mapping Integration**
    -   [x] Dropdown E-SAKIP document type
    -   [x] Input kode dokumen
    -   [x] Checkbox auto-verification
    -   [x] Update di Add & Edit modal

---

## 🚀 Cara Menggunakan

### **1. Setup Mapping Bukti Dukung** (Admin)

1. Masuk ke menu **Mapping**
2. Tambah/Edit Bukti Dukung
3. Isi form:
    - **Nama Bukti Dukung**: Renja, IKU, LKJIP, dll
    - **Verifikator**: Pilih role yang akan verifikasi
    - **✅ Verifikasi Otomatis**: Centang jika ingin auto-verify setelah sync
    - **Mapping Dokumen E-SAKIP**: Pilih jenis dokumen (Renja, IKU, dll)
    - **Kode Dokumen**: (Opsional) Kode spesifik jika ada
4. Simpan

**Contoh Mapping:**

```
Nama: Renja
Verifikator: Penilai
Auto-Verify: ✅ Yes
E-SAKIP Document: Renja
```

---

### **2. Sinkronisasi Dokumen**

1. Masuk ke menu **Sinkronisasi Dokumen**
2. Pilih filter:
    - **Tahun**: Required (contoh: 2024)
    - **OPD**: Opsional (kosongkan untuk semua OPD)
    - **Jenis Dokumen**: Opsional (kosongkan untuk semua)
3. Pilih **Mode Sinkronisasi**:
    - **Gabung**: Tambahkan file baru ke file lama (Recommended)
    - **Ganti**: Replace semua dengan file baru
    - **Lewati**: Skip jika sudah ada upload manual
4. Klik **Preview Sinkronisasi**
5. Review hasil preview:
    - Jumlah dokumen ditemukan
    - Bukti dukung yang akan terisi
    - Auto-verification count
6. Klik **Proses Sinkronisasi**
7. Tunggu progress bar selesai
8. Lihat hasil:
    - Berhasil: X dokumen
    - Tidak ditemukan: X dokumen
    - Gagal: X dokumen
    - Dilewati: X dokumen

---

## 📊 Contoh Skenario

### **Skenario 1: Sinkronisasi Renja untuk Semua OPD**

**Setup:**

-   Bukti Dukung "Renja" sudah di-mapping dengan `esakip_document_type = 'renja'`
-   `is_auto_verified = true`

**Proses:**

1. Pilih Tahun: 2024
2. OPD: (kosong - semua OPD)
3. Jenis Dokumen: Renja
4. Mode: Gabung
5. Preview menunjukkan:
    - 15 dokumen Renja ditemukan
    - 45 bukti dukung akan terisi (3 bukti per OPD × 15 OPD)
    - 45 akan auto-verified
6. Proses → Selesai!

**Hasil:**

-   45 penilaian baru ter-create/update
-   Semua `is_verified = true` (karena auto-verify)
-   `source = 'esakip'`
-   `link_file` berisi URL dari E-SAKIP

---

### **Skenario 2: File Sudah Ada (Upload Manual)**

**Kondisi:**

-   Penilaian sudah ada dengan `source = 'upload'`
-   Ada 2 file upload manual

**Mode: Gabung** (Recommended)

```json
// Hasil di link_file
[
    {
        "path": "bukti-dukung/2024/1/manual.pdf",
        "original_name": "Upload Manual.pdf",
        "size": 512000
    },
    {
        "url": "http://esakip/storage/renja.pdf",
        "original_name": "Renja 2024.pdf",
        "from_esakip": true,
        "synced_at": "2026-01-14 10:30:00"
    }
]
// Total: 3 file (2 manual + 1 esakip)
```

**Mode: Ganti**

```json
// Hasil di link_file (file lama hilang!)
[
    {
        "url": "http://esakip/storage/renja.pdf",
        "original_name": "Renja 2024.pdf",
        "from_esakip": true,
        "synced_at": "2026-01-14 10:30:00"
    }
]
// Total: 1 file (hanya esakip)
```

**Mode: Lewati**

```
// Tidak ada perubahan (skip sync)
Status: Dilewati
```

---

### **Skenario 3: Dokumen Tidak Ada di E-SAKIP**

**Kondisi:**

-   Bukti Dukung "IKU" sudah di-mapping
-   Tapi tidak ada dokumen IKU untuk OPD X di tahun 2024

**Hasil:**

-   Status: `no_document`
-   Tidak buat penilaian
-   Log di riwayat_sinkron:
    ```
    Status: Tidak Ada
    Affected: 0 penilaian
    ```

---

## 🎯 Edge Cases yang Di-handle

### ✅ 1. No Document di E-SAKIP

-   **Action**: Skip & Log
-   **Status**: `no_document`
-   **Penilaian**: Tidak dibuat
-   **Log**: Tercatat di riwayat

### ✅ 2. Sudah Ada Upload Manual

-   **Mode Gabung**: Append file baru
-   **Mode Ganti**: Replace semua file
-   **Mode Lewati**: Skip sync

### ✅ 3. Re-Sync (Sudah Pernah Sync)

-   **Action**: Update penilaian yang ada
-   **File**: Replace dengan file terbaru dari E-SAKIP
-   **Auto-Verify**: Update is_verified sesuai setting

### ✅ 4. Nama Bukti Dukung Sama (Beda Kriteria)

-   **Action**: Semua bukti dukung dengan mapping sama ter-sync
-   **Contoh**:
    ```
    - Bukti "Renja" di Kriteria A → Ter-sync
    - Bukti "Renja" di Kriteria B → Ter-sync
    - Bukti "Renja" di Kriteria C → Ter-sync
    ```
-   **File**: Shared (URL yang sama di semua penilaian)

### ✅ 5. API E-SAKIP Error/Timeout

-   **Action**: Retry 3x dengan delay 2 detik
-   **Status**: `failed` jika tetap gagal
-   **Log**: Error message dicatat

---

## 🗄️ Database Reference

### Tabel bukti_dukung

```sql
esakip_document_type VARCHAR(255) NULL -- renja, iku, lkjip
esakip_document_code VARCHAR(255) NULL -- kode opsional
sync_status ENUM('not_synced', 'synced', 'failed')
last_synced_at TIMESTAMP NULL
```

### Tabel penilaian

```sql
source ENUM('upload', 'esakip') DEFAULT 'upload'
esakip_document_id VARCHAR(255) NULL
esakip_synced_at TIMESTAMP NULL
link_file JSON -- [{url, original_name, from_esakip, synced_at}]
```

### Tabel riwayat_sinkron

```sql
status ENUM('success', 'failed', 'partial', 'no_document')
document_type VARCHAR -- renja, iku, dll
file_url TEXT -- URL file dari esakip
penilaian_ids JSON -- [1,2,3,4]
affected_count INT -- Jumlah penilaian terisi
auto_verified_count INT -- Jumlah auto-verified
```

---

## 🔧 Konfigurasi

### config/esakip.php

```php
'api_base_url' => env('ESAKIP_API_URL', 'http://localhost:8000/api'),

'document_types' => [
    'renja' => 'Renja',
    'iku' => 'IKU',
    'lkjip' => 'LKJIP',
    // ... 10 jenis dokumen
],

'sync' => [
    'timeout' => 30, // detik
    'retry_count' => 3,
    'retry_delay' => 2, // detik
],
```

### .env

```env
ESAKIP_API_URL=http://localhost:8000/api
ESAKIP_SYNC_TIMEOUT=30
ESAKIP_SYNC_RETRY=3
```

---

## 📝 Logging & History

### Riwayat Sinkronisasi

Semua sinkronisasi tercatat di tabel `riwayat_sinkron`:

-   OPD
-   Tahun
-   Jenis dokumen
-   Status (success/failed/no_document)
-   Affected count
-   Auto-verified count
-   Timestamp

### View Riwayat

UI menampilkan 20 riwayat terbaru di sidebar:

-   Badge status (hijau/merah/abu)
-   OPD & tahun
-   Jumlah penilaian terisi
-   Waktu relatif (2 hours ago)

---

## ⚠️ Catatan Penting

1. **Tahun Wajib**: Tidak bisa sync tanpa pilih tahun
2. **Mapping Required**: Bukti dukung harus di-mapping dulu
3. **URL Only**: File tidak didownload, hanya URL yang disimpan
4. **Auto-Verify**: Hanya terjadi jika centang di mapping
5. **Idempotent**: Re-sync aman (tidak duplikasi data)

---

## 🎉 Fitur Sudah Lengkap!

Implementasi sudah **100% selesai** dengan handling:

-   ✅ Preview sebelum sync
-   ✅ Progress tracking real-time
-   ✅ 3 sync mode (merge/replace/skip)
-   ✅ Auto-verification
-   ✅ Edge case handling
-   ✅ Error handling & retry
-   ✅ Complete logging
-   ✅ User-friendly UI

**Siap untuk production! 🚀**
