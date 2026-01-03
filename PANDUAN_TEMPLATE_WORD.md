# Panduan Template Word untuk Ekspor Laporan

## Lokasi File

`public/assets/template/lap2025.docx`

## Variabel Simple (Tanpa Loop)

```
${nama_opd}              → Nama OPD
${jumlah_komponen}       → Jumlah komponen (angka)
${nilai_opd}             → Persentase total nilai (format: 89,65 %)
${kategori_nilai}        → Kategori nilai (AA, A, BB, B, CC, C)
```

## TABEL KOMPONEN (Menggunakan cloneRow)

Buat tabel di Word dengan struktur berikut:

| No  | Komponen         | Bobot             | Persentase Nilai Capaian | Keterangan             |
| --- | ---------------- | ----------------- | ------------------------ | ---------------------- |
| a.  | ${komponen_nama} | ${komponen_bobot} | ${komponen_nilai}        | ${komponen_keterangan} |

**Cara Membuat:**

1. Buat tabel dengan 2 baris: 1 header, 1 data
2. Di baris data (baris ke-2), masukkan variabel di atas
3. PHPWord akan otomatis clone baris tersebut sesuai jumlah komponen
4. Variabel akan otomatis diberi index: `${komponen_nama#1}`, `${komponen_nama#2}`, dst

**Contoh hasil:**
| No | Komponen | Bobot | Persentase Nilai Capaian | Keterangan |
|----|----------|-------|--------------------------|------------|
| a. | Perencanaan Kinerja | 30% | 26,20 % | |
| b. | Pengukuran Kinerja | 30% | 28,33 % | |
| c. | Pelaporan Kinerja | 15% | 13,93 % | |
| d. | Evaluasi Akuntabilitas Kinerja Internal | 25% | 21,19 % | |

Row terakhir untuk total bisa dibuat manual (tidak di-clone):
| | **Jumlah** | **100%** | **89,65 %** | |

---

## DETAIL KOMPONEN (Menggunakan cloneBlock - Optional)

Jika ingin detail text per komponen:

```
${block_komponen}
1. Evaluasi atas ${nama_komponen}
Hasil evaluasi atas ${nama_komponen} pada ${nama_opd} dengan hasil evaluasi
sebesar ${nilai_komponen} % dari nilai maksimal sebesar ${bobot_komponen} %
dengan rincian masing-masing sub komponen sebagai berikut:

${block_sub_komponen}
a. ${nama_sub_komponen}
Sub komponen ${nama_sub_komponen} memperoleh nilai sebesar ${nilai_sub_komponen}
dari nilai maksimal sebesar ${bobot_sub_komponen}
${/block_sub_komponen}

${/block_komponen}
```

---

## CATATAN PER KOMPONEN

```
1. CATATAN

${block_catatan_komponen}
a. ${catatan_komponen_nama}
${block_catatan_item}
${catatan_item_no}) ${catatan_item}
${/block_catatan_item}
${/block_catatan_komponen}
```

**Penjelasan:**

-   `block_catatan_komponen`: Loop untuk setiap komponen
-   `catatan_komponen_nama`: Nama komponen (Perencanaan Kinerja, dll)
-   `block_catatan_item`: Loop untuk setiap catatan dalam komponen tersebut
-   `catatan_item`: Text catatan

**Contoh hasil:**

```
1. CATATAN

a. Perencanaan Kinerja
1) Belum ada penyusunan rencana strategis yang komprehensif
2) Dokumen Renstra belum mengacu pada RPJMD

b. Pengukuran Kinerja
1) Belum ada sistem monitoring kinerja yang terintegrasi

c. Pelaporan Kinerja
1) Laporan kinerja belum tepat waktu
2) Format laporan belum sesuai standar
```

---

## REKOMENDASI PER KOMPONEN

```
2. REKOMENDASI

${block_rekomendasi_komponen}
a. ${rekomendasi_komponen_nama}
${block_rekomendasi_item}
${rekomendasi_item_no}) ${rekomendasi_item}
${/block_rekomendasi_item}
${/block_rekomendasi_komponen}
```

**Struktur sama dengan catatan, hanya nama variabel berbeda**

**Contoh hasil:**

```
2. REKOMENDASI

a. Perencanaan Kinerja
1) Segera menyusun Renstra yang komprehensif
2) Melakukan revisi dokumen Renstra sesuai RPJMD

b. Pengukuran Kinerja
1) Membangun sistem monitoring kinerja terintegrasi
```

---

## CARA INPUT DATA CATATAN & REKOMENDASI

### Di Livewire Component (EksporLaporan.php):

```php
private function initializeCatatanRekomendasi()
{
    // Contoh: komponen ID = 1 (Perencanaan Kinerja)
    $this->catatan = [
        1 => [
            'Belum ada penyusunan rencana strategis yang komprehensif',
            'Dokumen Renstra belum mengacu pada RPJMD',
        ],
        2 => [
            'Belum ada sistem monitoring kinerja yang terintegrasi',
        ],
        // dst...
    ];

    $this->rekomendasi = [
        1 => [
            'Segera menyusun Renstra yang komprehensif',
            'Melakukan revisi dokumen Renstra sesuai RPJMD',
        ],
        2 => [
            'Membangun sistem monitoring kinerja terintegrasi',
        ],
        // dst...
    ];
}
```

### Nanti bisa dari Database:

Buat tabel `komponen_catatan` dan `komponen_rekomendasi`:

```sql
CREATE TABLE komponen_catatan (
    id BIGINT PRIMARY KEY,
    komponen_id BIGINT,
    catatan TEXT,
    urutan INT
);

CREATE TABLE komponen_rekomendasi (
    id BIGINT PRIMARY KEY,
    komponen_id BIGINT,
    rekomendasi TEXT,
    urutan INT
);
```

---

## TIPS PENTING

### 1. Untuk Table (cloneRow):

-   Buat tabel minimal 2 baris (header + 1 data row)
-   Masukkan variabel di data row
-   PHPWord otomatis tambah index: `#1`, `#2`, dst

### 2. Untuk Block (cloneBlock):

-   Harus ada pembuka `${block_name}` dan penutup `${/block_name}`
-   Bisa nested (block dalam block)
-   PHPWord otomatis tambah index: `#1`, `#2`, dst

### 3. Formatting di Word:

-   Bold, italic, warna tetap dipertahankan
-   Gunakan styles untuk konsistensi
-   Jangan gunakan text box atau objek kompleks
-   Tabel harus simple (tanpa merged cells di data row)

### 4. Testing:

-   Selalu test dengan 1 komponen dulu
-   Lihat preview di browser untuk validasi data
-   Download dan buka Word untuk cek format

---

## URUTAN KERJA

1. **Buat template Word** dengan struktur di atas
2. **Simpan di** `public/assets/template/lap2025.docx`
3. **Isi data catatan/rekomendasi** di `initializeCatatanRekomendasi()`
4. **Test di browser** - pilih OPD, lihat preview
5. **Klik Ekspor** - download dan cek hasil Word
6. **Sesuaikan** template jika ada yang kurang pas
7. **Ulangi** sampai perfect!

---

## TROUBLESHOOTING

**Q: Error "Template not found"**

-   A: Pastikan file ada di `public/assets/template/lap2025.docx`

**Q: Variabel tidak ter-replace (masih ${...})**

-   A: Cek nama variabel, harus exact match (case-sensitive)

**Q: Table tidak ke-clone**

-   A: Pastikan menggunakan `cloneRow()` dengan nama variabel yang ada di table

**Q: Block tidak muncul**

-   A: Pastikan ada pembuka `${block_name}` dan penutup `${/block_name}`

**Q: Catatan/rekomendasi kosong**

-   A: Cek `initializeCatatanRekomendasi()`, pastikan komponen ID sesuai dengan database
