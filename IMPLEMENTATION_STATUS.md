# Smart Sync Implementation - Perubahan yang Diperlukan

## Status: IN PROGRESS ✅

### Perubahan yang Sudah Dilakukan:

1. ✅ **Helper Methods Ditambahkan** (lines ~780-900):

    - `extractTimestamp()` - Extract timestamp dari filename
    - `buildFileObject()` - Build file object dengan metadata lengkap
    - `documentExists()` - Check duplikasi by URL dan timestamp
    - `smartMergeDocuments()` - Merge existing + new documents

2. ✅ **Update Loop Structure** (lines ~272-305):

    - Changed dari `foreach documents -> foreach buktiDukung`
    - Menjadi `foreach buktiDukung` (pass all documents)
    - Updated logging dan return structure

3. ⚠️ **PENDING: Update syncPenilaian Method** (lines ~340-470):
    - Perlu di-rewrite completely
    - Remove parameter `$syncMode`
    - Change parameter `$document` (single) menjadi `$documents` (array)
    - Implement 3 cases: CREATE, SKIP (upload), SMART MERGE (esakip)

---

## Pendekatan Lebih Aman

Karena method `syncPenilaian()` terlalu besar untuk single replace, saya rekomendasikan:

### Option 1: Manual Edit (Lebih Aman)

Buka file `app/Services/EsakipSyncService.php` dan:

1. **Cari method syncPenilaian (line ~340)**
2. **Replace signature dari:**

    ```php
    protected function syncPenilaian($buktiDukung, $opd, $document, $syncMode)
    ```

    **Menjadi:**

    ```php
    protected function syncPenilaian($buktiDukung, $opd, $documents)
    ```

3. **Replace isi method** dengan kode yang ada di `SMART_SYNC_STRATEGY.md` section 5

### Option 2: Delete & Recreate Method

1. Hapus lines 340-470 (entire syncPenilaian method)
2. Insert method baru dari template

### Option 3: Continue with Automated Approach (Risky)

Saya lanjutkan dengan multiple small replacements

---

## Testing Plan Setelah Update:

```bash
# 1. Cleanup data lama
echo yes | php test-sync.php

# 2. Test sync pertama kali (CREATE)
# Via UI: Sync OPD Dinas Komunikasi

# 3. Validate hasilnya
php validate-multiple-files.php
php check-linkfile-structure.php

# 4. Test re-sync (SMART MERGE - no change)
# Via UI: Sync OPD yang sama lagi

# 5. Test dengan manual upload (SKIP)
# Upload file manual via UI, lalu sync lagi

# 6. Validate timestamp extraction
php -r "require 'vendor/autoload.php'; \$service = new \App\Services\EsakipSyncService(); echo \$service->extractTimestamp('Renstra_Induk_2025_1761182369.pdf');"
```

---

## Apa yang harus dilakukan sekarang?

Tolong pilih:

**A)** Lanjutkan dengan automated approach (saya akan pakai multiple small replacements)

**B)** Saya buatkan file template lengkap, Anda copy-paste manual ke editor

**C)** Pause dulu, mau review strategi lebih detail

Beritahu pilihan Anda!
