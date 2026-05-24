# Struktur link_file dengan is_perubahan dan page_number

## ?? Ringkasan Struktur

Kolom `link_file` pada tabel `penilaian` menyimpan array of objects dalam format JSON. Setiap object mewakili satu file bukti dukung, baik yang berasal dari sinkronisasi eSAKIP maupun upload manual. Penggunaan format JSON dalam satu kolom memungkinkan fleksibilitas dalam menyimpan multiple files tanpa perlu tabel join tambahan, serta mempermudah audit trail per-file.

### ? Schema JSON link_file (Verified)

Setiap item dalam array `link_file` mengikuti struktur berikut:

```json
{
  "url": "https://e-sakip.trenggalekkab.go.id/storage/...", // URL eSAKIP (from_esakip=true) atau null (manual)
  "path": "bukti_dukung/filename.pdf",                    // Storage path (manual upload only), null untuk eSAKIP
  "original_name": "dokumen_kinerja.pdf",                 // Nama asli file
  "is_perubahan": false,                                  // Boolean per-file: true=revisi/perubahan, false=induk
  "kategori": "induk",                                    // "induk" atau "perubahan"
  "keterangan": "...",                                    // Catatan tambahan untuk file ini
  "periode": "2024",                                      // Periode dari API eSAKIP (jika ada), null untuk manual
  "tanggal_publish": "2024-05-24",                        // Tanggal rilis dokumen
  "from_esakip": true,                                    // true = sync dari eSAKIP, false = upload manual
  "uploaded_at": "2024-05-24 21:30:00",                   // Timestamp upload (hanya jika from_esakip=false)
  "synced_at": "2024-05-24 21:35:00",                     // Timestamp sinkronisasi (hanya jika from_esakip=true)
  "page_number": 1                                        // Nomor halaman awal referensi dokumen (int)
}
```

---

## ?? Detail Field Penting

### 1. is_perubahan vs kategori
- **`is_perubahan`** adalah field boolean utama yang digunakan oleh logika aplikasi untuk membedakan dokumen induk dan perubahan secara programmatik.
- **`kategori`** adalah mapping string dari API eSAKIP (`induk`|`perubahan`) yang juga dipertahankan saat upload manual demi konsistensi data.
- Nilai `is_perubahan` diturunkan dari `kategori`:
  - `kategori === 'perubahan'` ? `is_perubahan = true`
  - `kategori === 'induk'` ? `is_perubahan = false`

### 2. page_number (Nomor Halaman)
Field `page_number` bersifat **ganda** untuk memberikan akurasi bukti:
- **Di dalam JSON (`link_file`)**: Menentukan nomor halaman referensi khusus untuk file tersebut. Memungkinkan satu penilaian memiliki banyak file dengan titik referensi halaman yang berbeda-beda. Sangat berguna jika satu kriteria dibuktikan oleh beberapa dokumen PDF yang berbeda.
- **Di kolom tabel (`penilaian.page_number`)**: Digunakan sebagai nilai default atau referensi utama untuk seluruh set penilaian tersebut. Jika di dalam JSON `page_number` bernilai null, maka nilai di kolom tabel ini yang menjadi acuan.

### 3. Source Penilaian (upload vs esakip)
Selain field di dalam JSON, tabel `penilaian` memiliki kolom `source` yang menandai asal data secara keseluruhan:
- `esakip`: Penilaian (dan file-filenya) berasal dari sinkronisasi otomatis via API.
- `upload`: Penilaian dibuat melalui input manual di sistem KKE-SAKIP oleh user OPD atau Verifikator.

---

## ?? Logika Penentuan is_perubahan

### 1. Dari E-SAKIP API (Sinkronisasi)

API E-SAKIP mengirimkan field `kategori` pada beberapa jenis dokumen (seperti Renstra):
- `"kategori": "induk"` ? `is_perubahan = false`
- `"kategori": "perubahan"` ? `is_perubahan = true`
- Jika tidak ada field `kategori` dari API, sistem secara default akan menganggap `is_perubahan = false`.

### 2. Dari Upload Manual

User dapat memilih apakah file yang diupload merupakan dokumen perubahan melalui form input. Sistem secara otomatis akan mengisi:
- `is_perubahan` berdasarkan input checkbox/switch user.
- `kategori` sebagai string pendamping (`induk` atau `perubahan`) untuk menjaga keselarasan format dengan data eSAKIP.

---

## ?? Implementasi Kode

### Casting Model (`app/Models/Penilaian.php`)
Pastikan model melakukan casting agar data JSON bisa diakses sebagai array PHP:
```php
protected $casts = [
    'link_file' => 'array',
    'is_perubahan' => 'boolean', // Top-level flag untuk penilaian
    'page_number' => 'integer',   // Top-level column untuk default page
];
```

### Build Object Sync (`app/Services/EsakipSyncService.php`)
Logika pembuatan object file saat sinkronisasi:
```php
protected function buildFileObject($document) {
    $url = $document['file_url'] ?? $document['file'] ?? null;
    return [
        'url' => $url,
        'original_name' => basename($url ?? ''),
        'kategori' => $document['kategori'] ?? 'induk',
        'is_perubahan' => (isset($document['kategori']) && $document['kategori'] === 'perubahan'),
        'from_esakip' => true,
        'synced_at' => now()->toDateTimeString(),
        'page_number' => null, // Biasanya null saat sync, bisa diupdate manual kemudian
    ];
}
```

### Logic Manual Upload (`app/Livewire/Dashboard/LembarKerja.php`)
Penyimpanan file hasil upload user:
```php
$uploadedFiles[] = [
    'path' => $path,
    'original_name' => $file->getClientOriginalName(),
    'is_perubahan' => $this->is_perubahan ?? false,
    'kategori' => $this->is_perubahan ? 'perubahan' : 'induk',
    'from_esakip' => false,
    'uploaded_at' => now()->toDateTimeString(),
    'page_number' => $this->file_page_numbers[$index] ?? 1,
];
```

---

## ?? Contoh Kasus Penggunaan Riil

### Contoh 1: Sinkronisasi Renstra dari eSAKIP
Ketika sistem melakukan sinkronisasi Renstra, data yang tersimpan di `link_file` mungkin terlihat seperti ini:
```json
[
  {
    "url": "https://e-sakip.trenggalekkab.go.id/storage/renstra_induk.pdf",
    "original_name": "renstra_induk.pdf",
    "is_perubahan": false,
    "kategori": "induk",
    "from_esakip": true,
    "synced_at": "2024-05-24 10:00:00"
  },
  {
    "url": "https://e-sakip.trenggalekkab.go.id/storage/renstra_perubahan.pdf",
    "original_name": "renstra_perubahan.pdf",
    "is_perubahan": true,
    "kategori": "perubahan",
    "from_esakip": true,
    "synced_at": "2024-05-24 10:05:00"
  }
]
```

### Contoh 2: Upload Manual Bukti Dukung
User mengupload file PDF dan menentukan halaman spesifik yang menjadi bukti:
```json
[
  {
    "path": "bukti_dukung/vK8x9jLp.pdf",
    "original_name": "Laporan_Tahunan.pdf",
    "is_perubahan": false,
    "kategori": "induk",
    "from_esakip": false,
    "uploaded_at": "2024-05-24 15:00:00",
    "page_number": 42
  }
]
```

---

## ?? Manfaat Struktur Terstandarisasi

1. **Multiple Versions Support**: Memungkinkan satu kriteria memiliki banyak file (induk + revisi) dalam satu record database tanpa redundansi data.
2. **Audit Trail Lengkap**: Menjamin traceability sumber file (apakah otomatis dari eSAKIP atau intervensi manual user) dan waktu perolehannya (`synced_at` vs `uploaded_at`).
3. **Navigasi PDF Presisi**: `page_number` memungkinkan antarmuka pengguna (UI) untuk langsung membuka penampil PDF pada halaman yang relevan bagi evaluator, mempercepat proses penilaian.
4. **Konsistensi Logic**: Penggunaan `is_perubahan` sebagai tipe data boolean di level database dan JSON memudahkan pembuatan query filter atau pengelompokan dokumen di frontend.
5. **Skalabilitas**: Struktur JSON ini mudah diperluas jika di masa depan dibutuhkan metadata baru (misalnya hash file untuk integritas data) tanpa perlu migrasi skema tabel yang berat.

---

## ?? Metadata Teknis Proyek
- **File Name**: STRUKTUR_LINK_FILE.md
- **Project**: KKE-SAKIP Kabupaten Trenggalek
- **Primary Model**: `App\Models\Penilaian`
- **Main Service**: `App\Services\EsakipSyncService`
- **Location**: Root Directory

---

## ??? Cara Verifikasi Data
Untuk memastikan data `link_file` konsisten dengan dokumentasi ini, Anda dapat menjalankan query SQL berikut di database SQLite:
```sql
-- Cek penilaian yang memiliki file dari eSAKIP dan upload manual sekaligus
SELECT id, source, link_file 
FROM penilaian 
WHERE json_extract(link_file, '$[0].from_esakip') = 0 
AND json_extract(link_file, '$[1].from_esakip') = 1;
```

---

## ?? Checklist Validasi Dokumentasi
- [x] Field `page_number` terdokumentasi di JSON dan kolom DB secara mendalam.
- [x] Field `synced_at` terdokumentasi untuk data eSAKIP.
- [x] Field `from_esakip` terdokumentasi untuk identifikasi sumber file.
- [x] Field `kategori` (induk|perubahan) terdokumentasi sebagai string pendamping.
- [x] Schema JSON mencakup >= 8 field (url, path, name, is_perubahan, kategori, keterangan, periode, publish, source, timestamps, page).
- [x] Dokumentasi menjelaskan perbedaan penanganan manual vs eSAKIP secara eksplisit.
- [x] Implementasi kode riil (Service, Model, Livewire) telah diverifikasi dan dicontohkan dalam snippet.
- [x] Contoh kasus penggunaan (Renstra & Manual Upload) telah disertakan.
- [x] Metadata teknis dan petunjuk verifikasi database telah ditambahkan.

---

## ?? Riwayat Perubahan Dokumentasi
- **2024-05-24**: Sinkronisasi dokumentasi dengan implementasi `page_number` per-file dan kolom tabel. Penambahan detail `is_perubahan` boolean logic dan pembersihan struktur lama.
- **2024-01-14**: Inisialisasi struktur awal `link_file` untuk migrasi dari tabel terpisah ke JSON column sesuai dengan migration `2026_01_14_161938`.

---
**Catatan Penting**: Dokumentasi ini harus selalu dijadikan acuan utama oleh developer saat melakukan modifikasi pada method `buildFileObject` di `EsakipSyncService` atau saat memperbarui form upload di Livewire components seperti `LembarKerja` dan `BuktiDukung`.
