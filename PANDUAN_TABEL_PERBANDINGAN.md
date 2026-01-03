# 📊 Panduan Tabel Perbandingan Tahun (BAB III)

## Struktur Tabel di Word

### Tabel Perbandingan Nilai (Tahun Sebelumnya vs Tahun Saat Ini)

Di template Word, buat tabel seperti ini:

```
+-----+------------------------------------+--------+-----------------------------------+
| NO. | KOMPONEN / SUB KOMPONEN / KRITERIA | BOBOT  | NILAI AKUNTABILITAS KINERJA       |
|     |                                    |        +------------------+----------------+
|     |                                    |        | TAHUN ${tahun_sebelumnya} | TAHUN ${tahun_saat_ini} |
+-----+------------------------------------+--------+------------------+----------------+
| 1.  | ${perbandingan_komponen}           | ${perbandingan_bobot} | ${perbandingan_nilai_tahun_lalu} | ${perbandingan_nilai_tahun_ini} |
+-----+------------------------------------+--------+------------------+----------------+
|     | Nilai Akuntabilitas Kinerja        | ${total_bobot_100} | ${total_nilai_tahun_lalu} | ${total_nilai_tahun_ini} |
+-----+------------------------------------+--------+------------------+----------------+
|     | Predikat Nilai Akuntabilitas Kinerja |      | ${predikat_tahun_lalu} | ${predikat_tahun_ini} |
+-----+------------------------------------+--------+------------------+----------------+
```

---

## Variabel yang Tersedia

### Header Tahun (di kolom header tabel):

-   `${tahun_sebelumnya}` → Tahun sebelumnya (contoh: 2024)
-   `${tahun_saat_ini}` → Tahun saat ini (contoh: 2025)

### Data Row (akan di-clone):

-   `${perbandingan_komponen}` → Nama komponen (Perencanaan Kinerja, dll)
-   `${perbandingan_bobot}` → Bobot komponen (30, 30, 15, 25)
-   `${perbandingan_nilai_tahun_lalu}` → Nilai tahun sebelumnya (30,00, 25,93, dll)
-   `${perbandingan_nilai_tahun_ini}` → Nilai tahun saat ini (26,20, 28,33, dll)

### Row Total Nilai (manual):

-   `${total_bobot_100}` → Total bobot = "100"
-   `${total_nilai_tahun_lalu}` → Total nilai tahun sebelumnya (89,49)
-   `${total_nilai_tahun_ini}` → Total nilai tahun saat ini (89,65)

### Row Predikat (manual):

-   `${predikat_tahun_lalu}` → Predikat tahun sebelumnya: "(A) MEMUASKAN"
-   `${predikat_tahun_ini}` → Predikat tahun saat ini: "(A) MEMUASKAN"

---

## Langkah-Langkah Membuat Tabel di Word

### 1. Insert Table

-   Buat tabel dengan 4 kolom
-   Minimal 4 baris:
    -   Baris 1: Header (NO, KOMPONEN, BOBOT, NILAI AKUNTABILITAS)
    -   Baris 2: Sub-header (kosong, kosong, kosong, TAHUN 2024 | TAHUN 2025)
    -   Baris 3: Data row (akan di-clone)
    -   Baris 4: Total nilai
    -   Baris 5: Predikat

### 2. Merge Cells untuk Header

-   Merge kolom "NILAI AKUNTABILITAS KINERJA" jadi 2 sub-kolom
-   Sub-kolom 1: "TAHUN ${tahun_sebelumnya}" → akan otomatis jadi "TAHUN 2024"
-   Sub-kolom 2: "TAHUN ${tahun_saat_ini}" → akan otomatis jadi "TAHUN 2025"

**PENTING:** Gunakan variabel di header agar tahun otomatis update!

### 3. Data Row (Baris ke-3)

Copy-paste variabel ini:

```
| 1. | ${perbandingan_komponen} | ${perbandingan_bobot} | ${perbandingan_nilai_tahun_lalu} | ${perbandingan_nilai_tahun_ini} |
```

**PENTING:** Nomor urut "1." akan otomatis jadi 1, 2, 3, 4 saat di-clone oleh PHPWord.

### 4. Row Total (Baris ke-4)

```
| | Nilai Akuntabilitas Kinerja | ${total_bobot_100} | ${total_nilai_tahun_lalu} | ${total_nilai_tahun_ini} |
```

### 5. Row Predikat (Baris ke-5)

```
| | Predikat Nilai Akuntabilitas Kinerja | | ${predikat_tahun_lalu} | ${predikat_tahun_ini} |
```

---

## Bagian Tanggal

Di bagian bawah laporan (signature):

```
    ${tanggal_ekspor}
    Plt. INSPEKTUR
    KABUPATEN TRENGGALEK




    Ir. WIJIONO, S.T., M.MKes.
    Pembina
    NIP. 19730805 199703 1 007
```

### Variabel:

-   `${tanggal_ekspor}` → Format: "Trenggalek, 3 Januari 2026"

**User bisa edit tanggal di form sebelum ekspor!**

---

## Contoh Hasil Tabel

Setelah di-ekspor, tabel akan jadi seperti ini:

```
+-----+--------------------------------------------+--------+------------------+----------------+
| NO. | KOMPONEN / SUB KOMPONEN / KRITERIA         | BOBOT  | NILAI AKUNTABILITAS KINERJA      |
|     |                                            |        +------------------+----------------+
|     |                                            |        | TAHUN 2024       | TAHUN 2025     |
+-----+--------------------------------------------+--------+------------------+----------------+
| 1.  | Perencanaan Kinerja                        | 30     | 30,00            | 26,20          |
+-----+--------------------------------------------+--------+------------------+----------------+
| 2.  | Pengukuran Kinerja                         | 30     | 25,93            | 28,33          |
+-----+--------------------------------------------+--------+------------------+----------------+
| 3.  | Pelaporan Kinerja                          | 15     | 14,46            | 13,93          |
+-----+--------------------------------------------+--------+------------------+----------------+
| 4.  | Evaluasi Akuntabilitas Kinerja Internal    | 25     | 19,09            | 21,19          |
+-----+--------------------------------------------+--------+------------------+----------------+
|     | Nilai Akuntabilitas Kinerja                | 100    | 89,49            | 89,65          |
+-----+--------------------------------------------+--------+------------------+----------------+
|     | Predikat Nilai Akuntabilitas Kinerja       |        | (A) MEMUASKAN    | (A) MEMUASKAN  |
+-----+--------------------------------------------+--------+------------------+----------------+
```

**Catatan:** Tahun di header (2024, 2025) otomatis dari variabel `${tahun_sebelumnya}` dan `${tahun_saat_ini}`

---

## Format Angka

### Nilai Komponen:

-   Format: `XX,XX` (2 desimal dengan koma)
-   Contoh: `26,20`, `28,33`

### Total:

-   Format: `XX,XX` (2 desimal dengan koma)
-   Contoh: `89,49`, `89,65`

### Predikat:

-   Format: `(HURUF) KATA`
-   Contoh: `(A) MEMUASKAN`, `(AA) MEMUASKAN`, `(B) CUKUP`

---

## Tips Styling

### Header Row:

-   Background: **Biru terang**
-   Font: **Bold, Center**
-   Border: **Thick**

### Data Rows:

-   Background: **Putih**
-   Font: Normal
-   Alignment:
    -   NO: Center
    -   Komponen: Left
    -   Bobot: Center
    -   Nilai: Center

### Total Row:

-   Background: **Kuning terang / Abu-abu**
-   Font: **Bold**
-   Alignment: Center

### Predikat Row:

-   Background: **Hijau terang (jika A/AA) atau Kuning (jika B/BB)**
-   Font: **Bold, Center**

---

## Cara Input di Form

### 1. Pilih OPD

Dropdown untuk memilih OPD yang akan diekspor

### 2. Edit Tanggal

Input text untuk tanggal laporan. Default: hari ini dalam format Indonesia.
Contoh: `Trenggalek, 3 Januari 2026`

User bisa edit manual jika perlu tanggal berbeda.

---

## Logic Perhitungan

### Nilai Tahun 2025 (Tahun Aktif):

```php
$nilaiKomponen2025 = $this->calculateNilaiKomponen($komponen, $opd->id, $roleId);
```

Menggunakan data dari tahun yang dipilih di session (tahun_id).

### Nilai Tahun 2024 (Tahun Sebelumnya):

```php
$tahunSebelumnya = $this->tahun_id - 1;
$komponensTahunLalu = Komponen::where('tahun_id', $tahunSebelumnya)->get();
```

Otomatis cari data tahun sebelumnya (tahun_id - 1).

**CATATAN:** Jika tidak ada data tahun lalu, nilai akan 0,00.

---

## Troubleshooting

### Q: Nilai tahun 2024 semua 0,00

**A:** Pastikan ada data komponen untuk tahun sebelumnya (tahun_id - 1) di database.

### Q: Predikat tidak muncul

**A:** Pastikan variabel `${predikat_2024}` dan `${predikat_2025}` ada di Word.

### Q: Tabel hanya punya 1 baris data

**A:** Pastikan sudah pakai `cloneRow()` dengan variabel `perbandingan_komponen`.

### Q: Format tanggal salah

**A:** Edit manual di input form sebelum ekspor. Format: "Kota, tanggal bulan tahun".

### Q: Merge cells di data row error

**A:** Jangan merge cells di data row (baris yang di-clone). Merge hanya di header dan total row.

---

## Checklist

Sebelum ekspor, pastikan:

-   [ ] Tabel punya minimal 5 baris (header, sub-header, data, total, predikat)
-   [ ] Data row punya variabel `${perbandingan_komponen}`, dll
-   [ ] Total row punya variabel `${total_nilai_2024}`, `${total_nilai_2025}`
-   [ ] Predikat row punya variabel `${predikat_2024}`, `${predikat_2025}`
-   [ ] Bagian tanggal punya variabel `${tanggal_ekspor}`
-   [ ] Tanggal sudah disesuaikan di form
-   [ ] Ada data tahun sebelumnya di database
-   [ ] Test ekspor dengan 1 OPD dulu

---

## Contoh Lengkap Template Word

```
BAB III
PENUTUP

A. SIMPULAN

Berdasarkan hasil evaluasi terhadap Akuntabilitas Kinerja Instansi Pemerintah pada
${nama_opd} memperoleh nilai sebesar ${nilai_opd} atau menunjukkan kriteria
${kategori_nilai}. Nilai tersebut merupakan penjumlahan penilaian ${jumlah_komponen}
komponen kinerja dengan rincian sebagai berikut:

[TABEL PERBANDINGAN - Buat sesuai struktur di atas]

B. DORONGAN TERHADAP IMPLEMENTASI SAKIP YANG LEBIH BAIK

Demikian disampaikan hasil evaluasi Akuntabilitas Instansi Pemerintah (AKIP) ${nama_opd}
sebagai penerapan manajemen kinerja...

    ${tanggal_ekspor}
    Plt. INSPEKTUR
    KABUPATEN TRENGGALEK




    Ir. WIJIONO, S.T., M.MKes.
    Pembina
    NIP. 19730805 199703 1 007
```

---

Selamat mencoba! 🎉
