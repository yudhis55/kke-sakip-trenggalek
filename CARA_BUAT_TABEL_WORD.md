# 📋 Cara Membuat Tabel di Template Word

## ⚠️ PENTING: Row Total TIDAK Menggunakan Variabel!

Row total di tabel **TIDAK OTOMATIS** dari PHPWord. Anda harus menulisnya **MANUAL** atau menggunakan **RUMUS WORD**.

---

## 📊 Struktur Tabel yang Benar

### Di Template Word (`lap2025.docx`):

```
+-----+---------------------------+--------+---------------------------+-------------+
| No  | Komponen                  | Bobot  | Persentase Nilai Capaian  | Keterangan  |
+-----+---------------------------+--------+---------------------------+-------------+
| a.  | ${komponen_nama}          | ${komponen_bobot} | ${komponen_nilai} | ${komponen_keterangan} |
+-----+---------------------------+--------+---------------------------+-------------+
|     | Jumlah                    | 100%   | ${nilai_opd}              |             |
+-----+---------------------------+--------+---------------------------+-------------+
```

### Penjelasan:

#### Baris 1 (Header) - MANUAL

Tulis manual header tabel: `No`, `Komponen`, `Bobot`, `Persentase Nilai Capaian`, `Keterangan`

#### Baris 2 (Data Row) - AKAN DI-CLONE

**INI BARIS YANG AKAN DI-CLONE oleh PHPWord!**

-   `${komponen_nama}` → Nama komponen (Perencanaan Kinerja, dll)
-   `${komponen_bobot}` → Bobot dalam % (contoh: 30%)
-   `${komponen_nilai}` → Persentase capaian (contoh: 26,20 %)
-   `${komponen_keterangan}` → Keterangan (bisa kosong atau manual)

PHPWord akan menggunakan `cloneRow()` untuk membuat baris baru sesuai jumlah komponen.

#### Baris 3 (Total) - MANUAL atau RUMUS

**Row total TIDAK otomatis!** Ada 3 cara:

---

## 🎯 3 Cara Mengisi Row Total

### **Opsi 1: Manual (Paling Mudah)** ✅ RECOMMENDED

Tulis manual di Word sebelum ekspor:

```
| | Jumlah | 100% | 89,65 % | |
```

**Kelebihan:** Simple, cepat
**Kekurangan:** Harus update manual kalau nilai berubah

---

### **Opsi 2: Gunakan Variabel PHPWord** ✨ BEST PRACTICE

Di template Word, row total tulis:

```
| | Jumlah | ${total_bobot} | ${nilai_opd} | ${kategori_nilai} |
```

Di code PHP sudah saya sediakan:

```php
$template->setValue('nilai_opd', number_format($persentaseTotal, 2, ',', '.'));
$template->setValue('kategori_nilai', $this->getKategoriNilai($persentaseTotal));
```

Tambahkan juga:

```php
$template->setValue('total_bobot', '100%');
```

**Kelebihan:** Dinamis, otomatis update
**Kekurangan:** Perlu set variabel di code

---

### **Opsi 3: Rumus Word (Advanced)**

Di Word, gunakan formula field:

Untuk kolom "Persentase Nilai Capaian":

```
{ = SUM(ABOVE) }
```

**Cara membuat:**

1. Di Word, tekan `Ctrl + F9` → muncul `{ }`
2. Ketik: `= SUM(ABOVE)`
3. Tekan `F9` untuk update
4. Atau klik kanan → Update Field

**Kelebihan:** Otomatis hitung di Word
**Kekurangan:** Formula bisa rusak saat clone row

---

## ✅ Contoh Lengkap Template Word

```
BAB II
GAMBARAN HASIL EVALUASI

A. GAMBARAN HASIL EVALUASI AKUNTABILITAS KINERJA

Berdasarkan hasil evaluasi yang dilaksanakan pada ${nama_opd}...

[TABEL]
+-----+------------------------------------------+--------+---------------------------+-------------+
| No  | Komponen                                 | Bobot  | Persentase Nilai Capaian  | Keterangan  |
+-----+------------------------------------------+--------+---------------------------+-------------+
| a.  | ${komponen_nama}                         | ${komponen_bobot} | ${komponen_nilai}    | ${komponen_keterangan} |
+-----+------------------------------------------+--------+---------------------------+-------------+
|     | Jumlah                                   | ${total_bobot} | ${nilai_opd}         | ${kategori_nilai} |
+-----+------------------------------------------+--------+---------------------------+-------------+

Nilai keseluruhan sebesar ${nilai_opd} ${kategori_nilai} sebagaimana tersebut di bawah ini...
```

---

## 🎨 Styling Recommendations

### Row Header:

-   Background: Biru terang / Abu-abu
-   Font: **Bold**
-   Alignment: Center

### Row Data (yang di-clone):

-   Background: Putih
-   Font: Normal
-   Alignment: Left (nama), Center (bobot & nilai)

### Row Total:

-   Background: Kuning terang / Abu-abu
-   Font: **Bold**
-   Alignment: Right (label "Jumlah"), Center (angka)

---

## 💡 Tips Penting

1. **Jangan Merge Cells di Data Row**

    - Merged cells akan menyebabkan cloneRow() error
    - Row total boleh di-merge (karena tidak di-clone)

2. **Format Angka**

    - Di PHP sudah diformat: `number_format($nilai, 2, ',', '.')`
    - Hasil: `26,20 %` (dengan koma sebagai desimal)

3. **Urutan Huruf (a, b, c, ...)**

    - Tidak perlu variabel!
    - PHPWord otomatis ganti `a.` jadi `a.`, `b.`, `c.`, dst saat clone
    - Atau tulis manual: `a.`, `b.`, `c.`, `d.` di row total tidak perlu

4. **Testing**
    - Buat tabel dengan 1 komponen dulu
    - Test ekspor → buka Word
    - Jika berhasil, baru test dengan semua komponen

---

## 📝 Variabel yang Tersedia untuk Tabel

### Simple Values (di luar tabel):

-   `${nama_opd}` → Nama OPD
-   `${jumlah_komponen}` → Jumlah komponen (angka)
-   `${nilai_opd}` → Persentase total (89,65 %)
-   `${kategori_nilai}` → Kategori (AA, A, BB, dll)

### Table Row (akan di-clone):

-   `${komponen_nama}` → Nama komponen
-   `${komponen_bobot}` → Bobot (30%)
-   `${komponen_nilai}` → Persentase capaian (26,20 %)
-   `${komponen_keterangan}` → Keterangan (opsional)

### Total Row (manual atau variabel):

-   `${total_bobot}` → Total bobot (100%)
-   `${nilai_opd}` → Total persentase (89,65 %)
-   `${kategori_nilai}` → Kategori nilai (AA, A, BB, dll)

---

## 🚀 Quick Start

1. **Buka Word** → Buat file baru
2. **Insert Table** → 5 kolom × 3 baris
3. **Baris 1** (Header): Tulis manual header
4. **Baris 2** (Data): Copy variabel di atas
5. **Baris 3** (Total): Tulis "Jumlah" dan variabel `${nilai_opd}`
6. **Save as** → `public/assets/template/lap2025.docx`
7. **Test** → Pilih OPD → Ekspor

---

## ❓ FAQ

**Q: Kenapa row total tidak bisa pakai cloneRow?**
A: Karena cloneRow hanya untuk data yang berulang. Total hanya 1 baris, jadi tulis manual atau pakai setValue().

**Q: Bolehkah row total di atas data rows?**
A: Boleh! Tapi praktis di bawah karena sum/total biasanya di bawah.

**Q: Bisa buat sub-total per kategori?**
A: Bisa, tapi perlu logic tambahan di PHP. Row sub-total juga tulis manual atau pakai setValue().

**Q: Format tabel rusak setelah ekspor?**
A: Cek merged cells di data row. Hapus merge, pisahkan ke cell terpisah.

---

## ✅ Checklist Final

Sebelum ekspor, pastikan:

-   [ ] Tabel minimal 2 baris (header + 1 data)
-   [ ] Variabel di data row: `${komponen_nama}`, `${komponen_bobot}`, dll
-   [ ] Row total punya variabel `${nilai_opd}` atau tulis manual
-   [ ] Tidak ada merged cells di data row
-   [ ] File disimpan di `public/assets/template/lap2025.docx`
-   [ ] Form catatan & rekomendasi sudah diisi (opsional)
-   [ ] Test dengan 1 OPD dulu sebelum production

---

Selamat mencoba! 🎉
