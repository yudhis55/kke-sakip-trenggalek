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

## 2. Logika Sync Tanpa Mode

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
           ├─ source = 'upload' → SKIP (biarkan data manual user)
           │                      Log: "Skipped - Manual upload"
           │
           └─ source = 'esakip' → SMART SYNC
                                  ↓
                                  1. Ambil existing link_file array
                                  2. Ambil dokumen dari API
                                  3. Compare & Merge:

                                     For each dokumen dari API:
                                       ├─ Cek URL ada di existing?
                                       │  ├─ YES → Compare metadata
                                       │  │        ├─ Same → SKIP (no change)
                                       │  │        └─ Different → UPDATE
                                       │  │
                                       │  └─ NO → ADD (dokumen baru)

                                     For each existing file:
                                       └─ Cek URL masih ada di API?
                                          ├─ YES → Keep
                                          └─ NO → REMOVE (opsional)

                                  4. Update link_file dengan hasil merge
END
```

---

## 3. Deteksi Perubahan Dokumen

### Strategi 1: By URL Only (Simplest)
```php
// Dokumen sama jika URL sama
$isSameDocument = ($existingFile['url'] === $apiDocument['file']);

// KELEBIHAN:
// - Simple, reliable
// - URL file dari E-SAKIP unik (include timestamp)

// KEKURANGAN:
// - Jika file diupdate dengan URL baru, dianggap dokumen baru
// - Bisa ada duplikasi file yang sama dengan URL berbeda
```

### Strategi 2: By Timestamp in Filename (Recommended)
```php
// Extract timestamp dari nama file
// Contoh: Renstra_Induk_OPD_2025-2029_1761182369.pdf
//                                      ^^^^^^^^^^
preg_match('/_(\d{10})\.pdf$/', $filename, $matches);
$timestamp = $matches[1]; // 1761182369

// Compare timestamp
$isSameDocument = ($existingTimestamp === $apiTimestamp);

// KELEBIHAN:
// - Lebih akurat, timestamp unik per file
// - Bisa deteksi update file (timestamp berubah)

// KEKURANGAN:
// - Bergantung pada format nama file konsisten
```

### Strategi 3: By Publish Date + Kategori (Most Accurate)
```php
// Combine multiple fields
$documentSignature = md5(
    $doc['tanggal_publish'] .
    $doc['kategori'] .
    $doc['periode']
);

// KELEBIHAN:
// - Lebih semantic, tidak bergantung pada URL
// - Bisa deteksi "dokumen yang sama" meski URL berubah

// KEKURANGAN:
// - Lebih complex
// - Jika ada 2 dokumen dengan tanggal sama, bisa collision
```

---

## 4. Implementasi Code Structure

### Method Baru yang Dibutuhkan:

```php
// 1. Extract unique identifier dari dokumen API
protected function getDocumentIdentifier($document)
{
    // Extract timestamp dari filename
    $filename = basename($document['file'] ?? '');
    preg_match('/_(\d{10})\.pdf$/', $filename, $matches);
    $timestamp = $matches[1] ?? null;

    return [
        'url' => $document['file'],
        'timestamp' => $timestamp,
        'hash' => md5($document['file']), // fallback
    ];
}

// 2. Check apakah dokumen sudah ada di link_file
protected function documentExists($linkFileArray, $documentIdentifier)
{
    foreach ($linkFileArray as $file) {
        // Compare by URL (primary)
        if (isset($file['url']) && $file['url'] === $documentIdentifier['url']) {
            return true;
        }

        // Compare by timestamp (secondary, jika URL berubah)
        if (isset($file['timestamp']) &&
            $file['timestamp'] === $documentIdentifier['timestamp']) {
            return true;
        }
    }

    return false;
}

// 3. Smart merge: tambahkan hanya dokumen baru
protected function smartMergeDocuments($existingFiles, $apiDocuments)
{
    $mergedFiles = $existingFiles; // Start with existing

    foreach ($apiDocuments as $doc) {
        $identifier = $this->getDocumentIdentifier($doc);

        if (!$this->documentExists($mergedFiles, $identifier)) {
            // Dokumen baru, tambahkan
            $mergedFiles[] = $this->buildFileObject($doc);
        }
        // Jika sudah ada, skip (no update)
    }

    return $mergedFiles;
}

// 4. Bersihkan dokumen yang sudah tidak ada di API (opsional)
protected function pruneRemovedDocuments($existingFiles, $apiDocuments)
{
    $apiUrls = array_map(fn($doc) => $doc['file'], $apiDocuments);

    return array_filter($existingFiles, function($file) use ($apiUrls) {
        return in_array($file['url'], $apiUrls);
    });
}
```

---

## 5. Simplified Sync Flow

### Method syncPenilaian() - New Logic:

```php
protected function syncPenilaian($buktiDukung, $opd, $documents, $syncMode)
{
    $opdRole = Role::where('jenis', 'opd')->first();

    // Cek penilaian OPD
    $penilaian = Penilaian::where('opd_id', $opd->id)
        ->where('bukti_dukung_id', $buktiDukung->id)
        ->where('role_id', $opdRole->id)
        ->first();

    if (!$penilaian) {
        // === CASE 1: First Sync - Create New ===
        $allFiles = [];
        foreach ($documents as $doc) {
            $allFiles[] = $this->buildFileObject($doc);
        }

        $penilaian = Penilaian::create([
            'opd_id' => $opd->id,
            'bukti_dukung_id' => $buktiDukung->id,
            'role_id' => $opdRole->id,
            'link_file' => $allFiles,
            'source' => 'esakip',
            'tingkatan_nilai_id' => $this->getTingkatanNilai($buktiDukung),
        ]);

        return ['status' => 'created', 'files_added' => count($allFiles)];
    }

    if ($penilaian->source === 'upload') {
        // === CASE 2: Manual Upload - SKIP ===
        return ['status' => 'skipped', 'reason' => 'manual_upload'];
    }

    // === CASE 3: Re-sync E-SAKIP - Smart Update ===
    $existingFiles = $penilaian->link_file ?? [];
    $mergedFiles = $this->smartMergeDocuments($existingFiles, $documents);

    $filesAdded = count($mergedFiles) - count($existingFiles);

    if ($filesAdded > 0) {
        $penilaian->update([
            'link_file' => $mergedFiles,
            'esakip_synced_at' => now(),
        ]);

        return ['status' => 'updated', 'files_added' => $filesAdded];
    }

    return ['status' => 'no_change', 'files_added' => 0];
}
```

---

## 6. Handling Edge Cases

### A. Penilaian Role Lain (Penjamin, dll)

```php
// Di method syncDocument(), hanya process penilaian OPD:

foreach ($buktiDukungList as $buktiDukung) {
    // Hanya sync penilaian OPD
    $result = $this->syncPenilaian($buktiDukung, $opd, $documents);

    // Penilaian role lain (verifikator, penjamin) TIDAK DISENTUH
    // Mereka tetap dengan data mereka sendiri
}
```

### B. Deteksi Dokumen Dihapus dari E-SAKIP

```php
// Opsional: Hapus dokumen yang sudah tidak ada di API
if ($config['prune_removed_documents'] === true) {
    $prunedFiles = $this->pruneRemovedDocuments($existingFiles, $apiDocuments);

    if (count($prunedFiles) < count($existingFiles)) {
        // Ada dokumen yang dihapus
        Log::info("Removed documents", [
            'removed_count' => count($existingFiles) - count($prunedFiles)
        ]);
    }
}
```

### C. Dokumen Duplikat dengan URL Berbeda

```php
// Deteksi by content similarity atau metadata
protected function findDuplicates($files)
{
    $groups = [];

    foreach ($files as $file) {
        // Group by kategori + periode
        $key = ($file['kategori'] ?? 'induk') . '_' .
               ($file['periode'] ?? 'unknown');

        $groups[$key][] = $file;
    }

    // Jika group punya > 1 file dengan kategori sama,
    // pilih yang terbaru (by timestamp)
    foreach ($groups as &$group) {
        if (count($group) > 1) {
            usort($group, function($a, $b) {
                return ($b['timestamp'] ?? 0) <=> ($a['timestamp'] ?? 0);
            });

            // Keep only latest
            $group = [$group[0]];
        }
    }

    return array_merge(...array_values($groups));
}
```

---

## 7. Rekomendasi Final

### Strategi Terbaik:

**STRATEGI 2: By Timestamp in Filename + URL Fallback**

```php
protected function getDocumentIdentifier($document)
{
    $url = $document['file'];
    $filename = basename($url);

    // Extract timestamp dari filename
    preg_match('/_(\d{10})\.pdf$/', $filename, $matches);
    $timestamp = $matches[1] ?? null;

    return [
        'url' => $url,
        'timestamp' => $timestamp,
        'kategori' => $document['kategori'] ?? null,
        'tanggal_publish' => $document['tanggal_publish'] ?? null,
    ];
}

protected function isSameDocument($existing, $apiDoc)
{
    $existingId = $this->getDocumentIdentifier(['file' => $existing['url']]);
    $apiId = $this->getDocumentIdentifier($apiDoc);

    // Primary: Compare by URL (paling reliable)
    if ($existingId['url'] === $apiId['url']) {
        return true;
    }

    // Secondary: Compare by timestamp (jika URL berubah tapi file sama)
    if ($existingId['timestamp'] && $apiId['timestamp'] &&
        $existingId['timestamp'] === $apiId['timestamp']) {
        return true;
    }

    return false;
}
```

### Alasan:
1. ✅ **URL unik** dari E-SAKIP (include timestamp)
2. ✅ **Timestamp reliable** untuk deteksi perubahan
3. ✅ **Simple** dan tidak over-engineering
4. ✅ **Backward compatible** dengan data existing

---

## 8. Migration Path

Untuk implement strategi ini:

1. **Tambah field di link_file object**:
```json
{
  "url": "...",
  "timestamp": 1761182369,
  "kategori": "induk",
  "is_perubahan": false,
  "synced_at": "2026-01-17 12:00:00"
}
```

2. **Update existing data**: Run migration script untuk add timestamp ke existing files

3. **Deploy new sync logic**: Replace mode-based sync dengan smart sync

4. **Test scenarios**:
   - First sync
   - Re-sync (no change)
   - Re-sync (new documents)
   - Re-sync (after manual upload)
   - Re-sync (with role lain penilaian)

---

## Summary

**TANPA MODE, logika otomatis:**
- First sync → Create all
- Re-sync source='upload' → Skip
- Re-sync source='esakip' → Smart merge (only new files)
- Role lain → Never touched

**Deteksi dokumen:**
- Primary: URL
- Secondary: Timestamp from filename
- Fallback: Hash

**No duplikasi:** Check by URL before add
**Preserve data:** Skip manual uploads, skip other roles
**Smart update:** Only add new documents, don't replace
