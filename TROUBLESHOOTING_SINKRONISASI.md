# 🔧 Troubleshooting Sinkronisasi E-SAKIP

## ❌ Masalah: Dokumen Tidak Tampil Setelah Sinkronisasi

### Diagnosis Checklist:

#### 1. Cek Apakah Penilaian Ter-create

```sql
SELECT
    p.id,
    p.opd_id,
    p.bukti_dukung_id,
    p.role_id,
    p.kriteria_komponen_id,
    p.link_file,
    p.source,
    p.is_verified,
    p.tingkatan_nilai_id,
    p.page_number,
    p.created_at,
    o.nama as opd_nama,
    bd.nama as bukti_dukung_nama,
    r.jenis as role_jenis
FROM penilaian p
JOIN opd o ON p.opd_id = o.id
JOIN bukti_dukung bd ON p.bukti_dukung_id = bd.id
JOIN role r ON p.role_id = r.id
WHERE o.nama LIKE '%Komunikasi%Informatika%'
ORDER BY p.created_at DESC
LIMIT 10;
```

**Expected Result:**

-   `role_jenis` harus = `'opd'`
-   `link_file` harus berisi JSON array
-   `source` = `'esakip'`
-   `is_verified` = `1` jika auto_verified = true

---

#### 2. Cek Role ID yang Digunakan

```sql
SELECT * FROM role WHERE jenis = 'opd';
```

**Expected:** Harus ada 1 row dengan jenis = 'opd'

---

#### 3. Cek Bukti Dukung Mapping

```sql
SELECT
    bd.id,
    bd.nama,
    bd.esakip_document_type,
    bd.esakip_document_code,
    bd.is_auto_verified,
    bd.sync_status,
    bd.last_synced_at,
    kk.nama as kriteria_nama
FROM bukti_dukung bd
JOIN kriteria_komponen kk ON bd.kriteria_komponen_id = kk.id
WHERE bd.esakip_document_type IS NOT NULL
ORDER BY bd.id;
```

**Expected:**

-   Setiap dokumen harus punya `esakip_document_type` (contoh: 'renja', 'iku')
-   `sync_status` = 'synced' setelah sinkronisasi
-   `last_synced_at` terisi

---

#### 4. Cek OPD Mapping

```sql
SELECT
    id,
    nama,
    esakip_opd_id
FROM opd
WHERE nama LIKE '%Komunikasi%Informatika%';
```

**Expected:**

-   `esakip_opd_id` harus terisi dengan ID OPD di E-SAKIP
-   Jika NULL, system akan fallback ke ID lokal

**ACTION REQUIRED:**
Update esakip_opd_id untuk setiap OPD:

```sql
-- Contoh:
UPDATE opd
SET esakip_opd_id = '12345'
WHERE nama = 'Dinas Komunikasi dan Informatika';
```

---

#### 5. Cek Riwayat Sinkronisasi

```sql
SELECT
    rs.id,
    rs.opd_id,
    rs.document_type,
    rs.document_name,
    rs.tahun_value,
    rs.affected_count,
    rs.auto_verified_count,
    rs.status,
    rs.synced_at,
    o.nama as opd_nama
FROM riwayat_sinkron rs
JOIN opd o ON rs.opd_id = o.id
WHERE o.nama LIKE '%Komunikasi%Informatika%'
ORDER BY rs.synced_at DESC
LIMIT 10;
```

**Expected:**

-   `status` = 'success'
-   `affected_count` > 0
-   `auto_verified_count` sesuai dengan bukti_dukung.is_auto_verified

---

#### 6. Debug Link File Content

```sql
SELECT
    p.id,
    p.link_file,
    JSON_LENGTH(p.link_file) as file_count,
    JSON_EXTRACT(p.link_file, '$[0].url') as first_file_url,
    JSON_EXTRACT(p.link_file, '$[0].from_esakip') as is_from_esakip
FROM penilaian p
WHERE p.opd_id = (SELECT id FROM opd WHERE nama LIKE '%Komunikasi%Informatika%' LIMIT 1)
AND p.link_file IS NOT NULL
ORDER BY p.created_at DESC
LIMIT 5;
```

**Expected JSON Structure:**

```json
[
    {
        "url": "https://esakip.example.com/uploads/dokumen.pdf",
        "original_name": "Renja_2025.pdf",
        "from_esakip": true,
        "synced_at": "2026-01-14 18:30:00"
    }
]
```

---

### 🔍 Kemungkinan Penyebab & Solusi

#### A. Role ID Salah

**Gejala:** Penilaian ter-create tapi dengan `role_id` bukan OPD

**Solusi:**

```php
// Di EsakipSyncService.php sudah diperbaiki dengan:
$opdRole = \App\Models\Role::where('jenis', 'opd')->first();
'role_id' => $opdRole?->id ?? $buktiDukung->role_id,
```

#### B. OPD Mapping Tidak Ada

**Gejala:** API E-SAKIP tidak return dokumen karena opd_id salah

**Solusi:**

1. Ambil daftar OPD dari E-SAKIP API
2. Match dengan OPD lokal berdasarkan nama (fuzzy match)
3. Update kolom `esakip_opd_id`:

```sql
UPDATE opd SET esakip_opd_id = 'esakip_id' WHERE id = local_id;
```

#### C. Link File Format Salah

**Gejala:** `link_file` tidak berisi array valid

**Solusi:** Check format di `syncPenilaian()`:

```php
$newFile = [
    'url' => $document['file_url'] ?? null,
    'original_name' => basename($document['file'] ?? ''),
    'from_esakip' => true,
    'synced_at' => now()->toDateTimeString(),
];
```

#### D. Query Filter Terlalu Ketat

**Gejala:** Query di `selectedFileBuktiDukung()` tidak match karena kriteria tidak lengkap

**Debug Query:**

```php
// Di LembarKerja.php, tambah log:
Log::info('Debug Penilaian Query', [
    'kriteria_komponen_id' => $this->kriteria_komponen_session,
    'bukti_dukung_id' => $this->bukti_dukung_id,
    'opd_id' => $this->opd_session,
    'role_id' => $opdRoleId,
    'result' => $penilaian ? 'found' : 'not found'
]);
```

---

### 🧪 Test Scenario

#### Test 1: Cek Preview

```bash
# Akses: /sinkron-data
# Pilih: Tahun, OPD, Dokumen
# Klik: Preview Sinkronisasi
# Expected: Muncul preview dengan jumlah dokumen > 0
```

#### Test 2: Process Sync

```bash
# Klik: Proses Sinkronisasi
# Expected:
# - Progress bar muncul
# - Result: success_count > 0
# - Riwayat muncul di sidebar
```

#### Test 3: View Dokumen

```bash
# Akses: /lembar-kerja
# Pilih: OPD yang baru di-sync
# Pilih: Komponen > Sub Komponen > Kriteria
# Klik: Tab Bukti Dukung
# Expected: Dokumen muncul di menu "Dokumen"
```

---

### 📋 SQL Script untuk Setup OPD Mapping

```sql
-- Backup dulu
CREATE TABLE opd_backup AS SELECT * FROM opd;

-- Update esakip_opd_id berdasarkan mapping manual
-- SESUAIKAN dengan ID di E-SAKIP

UPDATE opd SET esakip_opd_id = '1' WHERE nama = 'Sekretariat Daerah';
UPDATE opd SET esakip_opd_id = '2' WHERE nama = 'Dinas Pendidikan';
UPDATE opd SET esakip_opd_id = '3' WHERE nama = 'Dinas Kesehatan';
UPDATE opd SET esakip_opd_id = '4' WHERE nama = 'Dinas Pekerjaan Umum';
UPDATE opd SET esakip_opd_id = '5' WHERE nama = 'Dinas Komunikasi dan Informatika';
-- ... dst

-- Verifikasi
SELECT id, nama, esakip_opd_id FROM opd ORDER BY id;
```

---

### 🚨 Common Errors

#### Error 1: "No Document Found"

```
status: 'no_document'
message: 'Tidak ada dokumen di esakip'
```

**Cause:**

-   OPD ID mapping salah
-   Dokumen belum published di E-SAKIP
-   Tahun tidak sesuai

**Fix:**

-   Cek esakip_opd_id di database
-   Cek dokumen di E-SAKIP portal
-   Verify tahun parameter

#### Error 2: "No Bukti Dukung Mapping"

```
status: 'no_document'
message: 'Tidak ada bukti dukung yang di-mapping'
```

**Cause:** Belum ada bukti_dukung yang punya `esakip_document_type`

**Fix:**

1. Buka menu **Mapping** di aplikasi
2. Edit setiap Bukti Dukung
3. Set `Jenis Dokumen E-SAKIP` (contoh: RENJA, IKU)
4. Set `Kode Dokumen` jika perlu
5. Centang `Auto Verified` jika mau otomatis

#### Error 3: Dokumen Tidak Tampil

**Debug Steps:**

1. Run query diagnosis di atas
2. Check `role_id` = role OPD
3. Check `link_file` berisi array valid
4. Check `kriteria_komponen_id` match
5. Clear browser cache / hard refresh

---

### 📞 Support

Jika masih bermasalah setelah troubleshooting:

1. **Export Debug Info:**

```sql
-- Export ke CSV untuk analisa
SELECT
    p.*,
    o.nama as opd_nama,
    bd.nama as bukti_nama,
    r.jenis as role
FROM penilaian p
JOIN opd o ON p.opd_id = o.id
JOIN bukti_dukung bd ON p.bukti_dukung_id = bd.id
JOIN role r ON p.role_id = r.id
WHERE p.source = 'esakip'
INTO OUTFILE '/tmp/debug_penilaian.csv'
FIELDS TERMINATED BY ','
ENCLOSED BY '"'
LINES TERMINATED BY '\n';
```

2. **Check Laravel Logs:**

```bash
tail -f storage/logs/laravel.log | grep -i "esakip\|sync"
```

3. **Enable Query Log:**

```php
// Di SinkronData.php, tambah:
\DB::enableQueryLog();
$this->esakipService->processSync(...);
dd(\DB::getQueryLog());
```

---

**Last Updated:** 2026-01-14
**Version:** 1.0
