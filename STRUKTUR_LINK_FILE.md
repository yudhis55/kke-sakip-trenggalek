# Struktur link_file dengan is_perubahan

## 📝 Perubahan Struktur

### ❌ Struktur Lama (Sebelum Update)

```json
[
    {
        "url": "https://e-sakip.trenggalekkab.go.id/storage/file.pdf",
        "synced_at": "2026-01-17 12:55:32",
        "from_esakip": true,
        "original_name": "file.pdf"
    }
]
```

**Masalah**: Tidak ada field `is_perubahan` untuk menandai apakah dokumen adalah induk atau perubahan/revisi.

### ✅ Struktur Baru (Setelah Update)

```json
[
    {
        "url": "https://e-sakip.trenggalekkab.go.id/storage/file.pdf",
        "synced_at": "2026-01-17 12:55:32",
        "from_esakip": true,
        "original_name": "file.pdf",
        "is_perubahan": false
    }
]
```

**Field baru**: `is_perubahan` (boolean)

-   `false` = Dokumen Induk
-   `true` = Dokumen Perubahan/Revisi

---

## 🔍 Logika Penentuan is_perubahan

### 1. Dari E-SAKIP API (Sinkronisasi)

API E-SAKIP mengirimkan field `kategori` pada beberapa jenis dokumen (seperti Renstra):

-   `"kategori": "induk"` → `is_perubahan = false`
-   `"kategori": "perubahan"` → `is_perubahan = true`
-   Tidak ada field `kategori` → `is_perubahan = false` (default dianggap induk)

**Contoh Response API Renstra:**

```json
{
    "data": {
        "OPD Name": [
            {
                "file": "https://...",
                "kategori": "induk"
            },
            {
                "file": "https://...",
                "kategori": "perubahan"
            }
        ]
    }
}
```

**Contoh Response API IKU (tanpa kategori):**

```json
{
    "data": {
        "OPD Name": [
            {
                "file": "https://...",
                "keterangan": "REVISI IKU 2024"
            }
        ]
    }
}
```

_Karena tidak ada field `kategori`, maka default `is_perubahan = false`_

### 2. Dari Upload Manual (Future)

Form upload akan memiliki switch/checkbox untuk menandai dokumen sebagai perubahan:

```php
// Ketika user upload file
$file = [
    'url' => $uploadedUrl,
    'uploaded_at' => now(),
    'from_esakip' => false,
    'original_name' => $fileName,
    'is_perubahan' => $request->is_perubahan // dari form
];
```

---

## 💻 Implementasi Kode

### File yang Diubah: `app/Services/EsakipSyncService.php`

#### 1. Method `normalizeDocument()` (Line ~715)

Menambahkan field `kategori` dari API:

```php
protected function normalizeDocument($doc)
{
    return [
        // ... field lain
        'kategori' => $doc['kategori'] ?? null, // induk atau perubahan
        // ...
    ];
}
```

#### 2. Method `syncPenilaian()` (Line ~350)

Menambahkan logika penentuan `is_perubahan`:

```php
// Tentukan is_perubahan berdasarkan kategori dari E-SAKIP
// Jika ada field kategori: 'perubahan' = true, 'induk' = false
// Jika tidak ada field kategori: default = false (dianggap induk)
$isPerubahan = isset($document['kategori']) && $document['kategori'] === 'perubahan';

$newFile = [
    'url' => $document['file_url'] ?? null,
    'original_name' => basename($document['file'] ?? ''),
    'from_esakip' => true,
    'synced_at' => now()->toDateTimeString(),
    'is_perubahan' => $isPerubahan,
];
```

---

## 🧪 Testing

### Script Validasi: `check-linkfile-structure.php`

Jalankan script untuk memeriksa struktur link_file:

```bash
php check-linkfile-structure.php
```

Output yang diharapkan:

```
=== VALIDASI STRUKTUR LINK_FILE ===

Found 10 penilaian from E-SAKIP

─────────────────────────────────────
Penilaian ID: 123
Bukti: Renstra
Source: esakip
Jumlah files: 1

  File #0:
    ✓ URL: ✓
    ✓ from_esakip: ✓
    ✓ is_perubahan: ✓ (value: FALSE - INDUK)
    Structure: {"url":"...","is_perubahan":false,...}

─────────────────────────────────────
SUMMARY:
  ✓ Files dengan is_perubahan: 10
  ✗ Files tanpa is_perubahan: 0

✅ STRUKTUR LINK_FILE SUDAH SESUAI!
```

---

## 📋 Checklist

### Sinkronisasi dari E-SAKIP

-   ✅ Tambah field `kategori` di `normalizeDocument()`
-   ✅ Implementasi logika `is_perubahan` berdasarkan `kategori`
-   ✅ Default `is_perubahan = false` jika tidak ada `kategori`
-   ⏳ Testing dengan sinkronisasi Renstra (ada kategori)
-   ⏳ Testing dengan sinkronisasi IKU (tanpa kategori)

### Upload Manual (TODO - Nanti)

-   ⏳ Tambah field switch/checkbox `is_perubahan` di form upload
-   ⏳ Update logika controller untuk handle `is_perubahan` dari form
-   ⏳ Validasi struktur link_file saat upload

---

## 🔮 Rencana Ke Depan

Seperti yang disebutkan, struktur ini adalah fondasi untuk perombakan cara sinkronisasi dokumen di masa depan, yang akan memungkinkan:

1. **Multiple versions** - Menyimpan dokumen induk + semua perubahannya
2. **Version tracking** - Melihat history revisi dokumen
3. **Selective sync** - Memilih dokumen mana yang mau di-sync (induk saja atau dengan perubahan)
4. **Better UI** - Menampilkan badge "INDUK" atau "PERUBAHAN" di list dokumen
