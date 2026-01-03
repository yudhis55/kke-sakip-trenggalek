# 📋 Panduan Memperbaiki Tabel BAB II

## 🎯 Tujuan
Membuat tabel komponen di BAB II yang bisa di-clone otomatis oleh PHPWord tanpa menyebabkan BAB III muncul berulang.

---

## ⚠️ Masalah yang Harus Dihindari

**JANGAN:**
- ❌ Menulis "a.", "b.", "c.", "d." secara manual di kolom No
- ❌ Membuat 4 baris data dengan nama komponen hardcoded
- ❌ Meletakkan baris "Jumlah" sebelum atau di tengah baris data

**LAKUKAN:**
- ✅ Buat HANYA 1 baris template data dengan variabel `${komponen_nama}`
- ✅ Biarkan kolom No kosong atau gunakan numbering Word otomatis
- ✅ Letakkan baris "Jumlah" SETELAH baris template (terpisah)

---

## 📐 Struktur Tabel yang Benar

```
+-----+---------------------------+----------+---------------------------+-------------+
| No  | Komponen                  | Bobot    | Persentase Nilai Capaian  | Keterangan  |
+-----+---------------------------+----------+---------------------------+-------------+
|     | ${komponen_nama}          | ${komponen_bobot} | ${komponen_nilai}  |             |  ← Baris ini akan di-clone 4x
+-----+---------------------------+----------+---------------------------+-------------+
|     | Jumlah                    | ${total_bobot}    | ${nilai_opd}       |             |  ← Baris manual (tidak di-clone)
+-----+---------------------------+----------+---------------------------+-------------+
```

---

## 🔧 Langkah-Langkah Perbaikan

### **Langkah 1: Hapus Tabel Lama**

1. Buka file Word: `public/assets/template/lap2025.docx`
2. Scroll ke BAB II bagian tabel komponen
3. **Klik di dalam tabel** → klik icon tabel di pojok kiri atas
4. **Tekan Delete** untuk menghapus seluruh tabel

### **Langkah 2: Buat Tabel Baru**

1. **Insert → Table**
2. Pilih **5 kolom x 3 baris**:
   - Baris 1: Header
   - Baris 2: Template data (akan di-clone)
   - Baris 3: Total/Jumlah

### **Langkah 3: Isi Header (Baris 1)**

| No | Komponen | Bobot | Persentase Nilai Capaian | Keterangan |
|----|----------|-------|-------------------------|------------|

**Tips:** 
- Bold semua text header
- Background warna (optional): klik kanan → Shading → pilih warna terang
- Align center untuk header

### **Langkah 4: Isi Baris Data/Template (Baris 2)**

⚠️ **INI BARIS YANG AKAN DI-CLONE!**

| Kolom | Isi |
|-------|-----|
| **No** | **(KOSONG)** - Jangan tulis apapun! |
| **Komponen** | `${komponen_nama}` |
| **Bobot** | `${komponen_bobot}` |
| **Persentase Nilai Capaian** | `${komponen_nilai}` |
| **Keterangan** | **(KOSONG)** - Kolom ini tidak digunakan |

**PENTING:**
- Kolom No harus **100% KOSONG** (tidak ada spasi, tidak ada karakter apapun)
- Setiap variabel `${...}` harus di **sel terpisah**
- Jangan gabungkan text dengan variabel: ❌ `a. ${komponen_nama}` 
- **Kolom Keterangan dikosongkan** - keterangan akan diletakkan di luar tabel 

### **Langkah 5: Isi Baris Total (Baris 3)**

| Kolom | Isi |
|-------|-----|
| **No** | **(KOSONG)** |
| **Komponen** | **Jumlah** (text manual, bisa di-bold) |
| **Bobot** | `${total_bobot}` |
| **Persentase Nilai Capaian** | `${nilai_opd}` |
| **Keterangan** | **(KOSONG)** |

### **Langkah 6 (Optional): Tambah Numbering Otomatis**

Jika Anda ingin kolom No otomatis jadi a, b, c, d:

1. **Klik sel No di baris data** (baris 2)
2. **Home → Numbering** → pilih format **a, b, c**
3. **Adjust:**
   - Klik kanan nomor → **Adjust List Indents**
   - Text indent: 0 cm
   - Number position: 0 cm
   - Follow number with: Nothing

Saat PHPWord clone row, numbering akan otomatis menjadi a, b, c, d! 

### **Langkah 7: Tambah Keterangan Setelah Tabel**

Setelah tabel, tambahkan paragraf untuk keterangan:

**Cara 1: Gunakan Variabel (Otomatis)**
```
${keterangan_tabel}
```
Akan terisi otomatis: "Nilai keseluruhan sebesar 89,65% (A) MEMUASKAN"

**Cara 2: Tulis Manual dengan Variabel**
```
Nilai keseluruhan sebesar ${nilai_opd} ${kategori_nilai} sebagaimana tersebut di bawah ini, merupakan akumulasi penilaian terhadap seluruh komponen kinerja yang dievaluasi di lingkungan ${nama_opd}, dengan rincian sebagai berikut:
```

**Rekomendasi:** Gunakan Cara 2 untuk text yang lebih lengkap dan deskriptif. 

---

## ✅ Checklist Akhir

Sebelum save, pastikan:

- [ ] Tabel punya **PERSIS 3 baris**: 1 header + 1 data + 1 total
- [ ] Kolom No di baris data **100% KOSONG** (tidak ada spasi!)
- [ ] Semua variabel `${...}` ada di **sel terpisah**
- [ ] Baris "Jumlah" ada di **baris ke-3** (setelah baris data)
- [ ] Tidak ada Section Break atau Page Break **DALAM** tabel
- [ ] Variabel ditulis **persis** seperti ini:
  - `${komponen_nama}` (bukan `${komponen_name}` atau `${nama_komponen}`)
  - `${komponen_bobot}` (bukan `${bobot_komponen}`)
  - `${komponen_nilai}` (bukan `${nilai_komponen}`)
  - `${total_bobot}`
  - `${nilai_opd}`
- [ ] Keterangan diletakkan **SETELAH** tabel (bukan di dalam tabel)
- [ ] Paragraf keterangan menggunakan variabel `${keterangan_tabel}` atau `${nilai_opd}` + `${kategori_nilai}`

---

## 🎨 Format Tabel (Styling Optional)

Untuk tampilan lebih rapi:

1. **Header:**
   - Bold
   - Background: warna biru muda atau hijau muda
   - Text align: Center
   - Vertical align: Center

2. **Baris Data:**
   - Normal text
   - Align: Left (kecuali angka → Right/Center)

3. **Baris Total:**
   - Bold untuk text "Jumlah"
   - Background: warna abu-abu muda (optional)

4. **Borders:**
   - Table Design → Borders → All Borders
   - Line style: Single solid line

---

## 🧪 Testing

Setelah selesai:

1. **Save** template Word
2. **Refresh** halaman ekspor di browser
3. **Pilih OPD** dan klik **Ekspor**
4. **Buka hasil ekspor** dan cek:
   - ✅ Tabel punya **4 baris data** + 1 baris total (total 6 baris: header + 4 data + total)
   - ✅ Kolom No ada numbering a, b, c, d (jika pakai numbering otomatis)
   - ✅ Semua nama komponen terisi
   - ✅ Semua nilai dan bobot terisi dengan format Indonesia (koma desimal)
   - ✅ Baris "Jumlah" muncul **HANYA 1 KALI** di paling bawah
   - ✅ BAB III muncul **HANYA 1 KALI** (tidak 4 kali lagi!)

---

## 🚨 Troubleshooting

### **Masalah: Variabel tidak terisi (tetap ${komponen_nama})**

**Penyebab:** Typo di nama variabel

**Solusi:** 
- Cek ejaan variabel di Word (harus persis!)
- Copy-paste variabel dari panduan ini ke Word
- Jangan ada spasi sebelum/sesudah variabel

### **Masalah: Tabel hanya punya 1 baris data (seharusnya 4)**

**Penyebab:** `cloneRow` tidak bekerja

**Solusi:**
- Pastikan variabel `${komponen_nama}` ada di Word
- Cek di code PHP: `$template->cloneRow('komponen_nama', count($komponens));` tidak di-comment
- Pastikan hanya ada **1 baris data** di template (bukan 4 baris)

### **Masalah: Baris "Jumlah" muncul 4 kali**

**Penyebab:** Baris "Jumlah" ada di dalam baris yang di-clone

**Solusi:**
- Baris "Jumlah" harus terpisah, bukan di baris yang sama dengan `${komponen_nama}`
- Pastikan ada 2 baris setelah header: 1 untuk data template, 1 untuk total

### **Masalah: BAB III masih muncul 4 kali**

**Penyebab:** Bukan karena tabel BAB II, tapi karena hal lain

**Solusi:**
- Cek apakah ada Section Break setelah tabel (hapus!)
- Cek apakah BAB III ada di dalam `${block_...}` sampai `${/block_...}`
- Lihat panduan: [CARA_MEMPERBAIKI_TEMPLATE.md](./CARA_MEMPERBAIKI_TEMPLATE.md)

---

## 📝 Contoh Visual

### ❌ SALAH (Jangan Seperti Ini):

```
| No | Komponen                               | Bobot | Nilai  |
|----|----------------------------------------|-------|--------|
| a. | Perencanaan Kinerja                    | 30%   | 26,20% |  ← Hardcoded!
| b. | Pengukuran Kinerja                     | 30%   | 28,33% |  ← Hardcoded!
| c. | Pelaporan Kinerja                      | 15%   | 13,93% |  ← Hardcoded!
| d. | Evaluasi Akuntabilitas Kinerja Internal| 25%   | 21,19% |  ← Hardcoded!
|    | Jumlah                                 | 100%  | 89,65% |
```

### ✅ BENAR (Seperti Ini):

```
| No | Komponen             | Bobot             | Nilai             |
|----|----------------------|-------------------|-------------------|
|    | ${komponen_nama}     | ${komponen_bobot} | ${komponen_nilai} |  ← 1 baris template
|    | Jumlah               | ${total_bobot}    | ${nilai_opd}      |  ← Baris manual
```

Saat di-ekspor akan jadi:

```
| No | Komponen                                | Bobot | Nilai  |
|----|----------------------------------------|-------|--------|
| a. | Perencanaan Kinerja                    | 30%   | 26,20% |  ← Clone 1
| b. | Pengukuran Kinerja                     | 30%   | 28,33% |  ← Clone 2
| c. | Pelaporan Kinerja                      | 15%   | 13,93% |  ← Clone 3
| d. | Evaluasi Akuntabilitas Kinerja Internal| 25%   | 21,19% |  ← Clone 4
|    | Jumlah                                 | 100%  | 89,65% |  ← Manual (tidak di-clone)
```

---

## 💡 Tips Tambahan

1. **Gunakan Word Navigation Pane** (View → Navigation Pane) untuk mudah jump ke BAB II
2. **Save sering** saat edit template
3. **Backup template** sebelum edit: copy `lap2025.docx` → `lap2025_backup.docx`
4. **Test dengan OPD berbeda** untuk memastikan semua data terisi
5. Jika bingung, **hapus tabel dan buat dari awal** (lebih cepat daripada troubleshoot)

---

## 📚 Referensi

- [PANDUAN_TEMPLATE_WORD.md](./PANDUAN_TEMPLATE_WORD.md) - Panduan lengkap variabel
- [CARA_BUAT_TABEL_WORD.md](./CARA_BUAT_TABEL_WORD.md) - Cara membuat tabel dengan cloneRow
- [CARA_MEMPERBAIKI_TEMPLATE.md](./CARA_MEMPERBAIKI_TEMPLATE.md) - Troubleshooting template

---

## ✨ Selesai!

Setelah ikuti panduan ini, tabel BAB II akan:
- ✅ Ter-clone otomatis jadi 4 baris (sesuai jumlah komponen)
- ✅ Punya numbering a, b, c, d otomatis
- ✅ Baris total muncul 1x di paling bawah
- ✅ Tidak menyebabkan BAB III muncul berulang

**Good luck! 🚀**
