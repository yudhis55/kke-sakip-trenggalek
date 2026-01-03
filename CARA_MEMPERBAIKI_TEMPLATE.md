# 🔧 Cara Memperbaiki Template Word

## Masalah yang Terjadi:

1. **BAB II:** Tabel komponen rusak karena struktur tidak benar
2. **BAB III:** Tabel perbandingan muncul 4 kali karena row total ikut ter-clone

---

## ✅ SOLUSI 1: Perbaiki Tabel BAB II

### Struktur yang BENAR:

Buat tabel dengan 5 kolom di Word:

| No  | Komponen         | Bobot             | Persentase Nilai Capaian | Keterangan |
| --- | ---------------- | ----------------- | ------------------------ | ---------- |
| a.  | ${komponen_nama} | ${komponen_bobot} | ${komponen_nilai}        |            |
|     | **Jumlah**       | ${total_bobot}    | ${nilai_opd}             |            |

### Cara Membuatnya di Word:

1. **Buat Tabel 3 baris x 5 kolom**

    - Baris 1: Header (No, Komponen, Bobot, Persentase Nilai Capaian, Keterangan)
    - Baris 2: Data row (akan di-clone)
    - Baris 3: Total row (manual)

2. **Isi Baris Header (Row 1):**

    ```
    | No | Komponen | Bobot | Persentase Nilai Capaian | Keterangan |
    ```

3. **Isi Baris Data (Row 2):**

    ```
    | a. | ${komponen_nama} | ${komponen_bobot} | ${komponen_nilai} | (kosong) |
    ```

    **PENTING:**

    - "a." tulis manual di sel pertama (atau gunakan numbering otomatis Word)
    - Setiap `${...}` harus di **sel terpisah**
    - Jangan gabungkan beberapa variabel dalam 1 sel

4. **Isi Baris Total (Row 3):**
    ```
    | (kosong) | Jumlah | ${total_bobot} | ${nilai_opd} | (kosong) |
    ```

### Tips:

-   Untuk "No" otomatis (a, b, c, d), gunakan fitur **Numbering** Word dengan format lowercase letter
-   Untuk "Jumlah" bisa di-bold agar lebih menonjol

---

## ✅ SOLUSI 2: Perbaiki Tabel BAB III

### Struktur yang BENAR:

Tabel dengan header 2 baris (merged cells):

| NO. | KOMPONEN / SUB KOMPONEN/KRITERIA | BOBOT | NILAI AKUNTABILITAS KINERJA ||
|-----|----------------------------------|-------|-----------------------------------------------||
| | | | TAHUN ${tahun_sebelumnya} | TAHUN ${tahun_saat_ini} |
| (kosong) | ${perbandingan_komponen} | ${perbandingan_bobot} | ${perbandingan_nilai_tahun_lalu} | ${perbandingan_nilai_tahun_ini} |
| | **Nilai Akuntabilitas Kinerja** | ${total_bobot_100} | ${total_nilai_tahun_lalu} | ${total_nilai_tahun_ini} |
| | **Predikat Nilai Akuntabilitas Kinerja** | (kosong) | ${predikat_tahun_lalu} | ${predikat_tahun_ini} |

### Cara Membuatnya di Word:

#### **Langkah 1: Buat Tabel 5 baris x 5 kolom**

#### **Langkah 2: Buat Header (Row 1-2)**

**Row 1:**

-   Merge sel kolom 4-5 untuk "NILAI AKUNTABILITAS KINERJA"
-   Isi: `NO.`, `KOMPONEN / SUB KOMPONEN/KRITERIA`, `BOBOT`, `NILAI AKUNTABILITAS KINERJA` (merged)

**Row 2:**

-   Merge sel kolom 1-3 (kosong)
-   Isi: (kosong), (kosong), (kosong), `TAHUN ${tahun_sebelumnya}`, `TAHUN ${tahun_saat_ini}`

#### **Langkah 3: Isi Baris Data (Row 3) - Akan di-clone**

```
| (kosong) | ${perbandingan_komponen} | ${perbandingan_bobot} | ${perbandingan_nilai_tahun_lalu} | ${perbandingan_nilai_tahun_ini} |
```

**⚠️ PENTING:**

-   Kolom NO (kolom pertama) **KOSONG** atau gunakan numbering Word otomatis
-   **JANGAN** tulis "1." di sini!
-   Baris ini yang akan di-clone menjadi 4 baris (sesuai jumlah komponen)

#### **Langkah 4: Isi Baris Total (Row 4-5) - Manual**

**Row 4: Nilai Akuntabilitas**

```
| (kosong) | Nilai Akuntabilitas Kinerja | ${total_bobot_100} | ${total_nilai_tahun_lalu} | ${total_nilai_tahun_ini} |
```

**Row 5: Predikat**

```
| (kosong) | Predikat Nilai Akuntabilitas Kinerja | (kosong) | ${predikat_tahun_lalu} | ${predikat_tahun_ini} |
```

### Tips Numbering Otomatis:

1. Klik sel NO di baris data (row 3)
2. Klik Home → Numbering → pilih format "1. 2. 3."
3. Saat di-clone, numbering akan otomatis jadi 1, 2, 3, 4

---

## 🎯 Checklist Akhir

### BAB II:

-   [ ] Tabel punya 5 kolom terpisah
-   [ ] Setiap variabel ${} di sel terpisah
-   [ ] Baris total ada DI LUAR baris data (baris terpisah)
-   [ ] Total menggunakan variabel: ${total_bobot}, ${nilai_opd}

### BAB III:

-   [ ] Header menggunakan ${tahun_sebelumnya} dan ${tahun_saat_ini}
-   [ ] Kolom NO di baris data KOSONG (tidak ada "1.")
-   [ ] Baris "Nilai Akuntabilitas" dan "Predikat" ada DI BAWAH baris data
-   [ ] Semua variabel ${} di sel terpisah

---

## 📸 Contoh Visual

### Tabel BAB II yang Benar:

```
+-----+---------------------------+--------+---------------------------+-------------+
| No  | Komponen                  | Bobot  | Persentase Nilai Capaian  | Keterangan  |
+-----+---------------------------+--------+---------------------------+-------------+
| a.  | ${komponen_nama}          | ${komponen_bobot} | ${komponen_nilai}  |            |
+-----+---------------------------+--------+---------------------------+-------------+  ← Baris ini di-clone!
|     | Jumlah                    | ${total_bobot}    | ${nilai_opd}       |            |
+-----+---------------------------+--------+---------------------------+-------------+  ← Baris total (manual)
```

### Tabel BAB III yang Benar:

```
+-----+----------------------------------+--------+---------------------------+---------------------------+
| NO. | KOMPONEN                         | BOBOT  | NILAI AKUNTABILITAS KINERJA                          |
|     |                                  |        +---------------------------+---------------------------+
|     |                                  |        | TAHUN ${tahun_sebelumnya} | TAHUN ${tahun_saat_ini}   |
+-----+----------------------------------+--------+---------------------------+---------------------------+
|     | ${perbandingan_komponen}         | ${perbandingan_bobot} | ${perbandingan_nilai_tahun_lalu} | ${perbandingan_nilai_tahun_ini} |
+-----+----------------------------------+--------+---------------------------+---------------------------+  ← Baris ini di-clone!
|     | Nilai Akuntabilitas Kinerja      | ${total_bobot_100} | ${total_nilai_tahun_lalu} | ${total_nilai_tahun_ini} |
+-----+----------------------------------+--------+---------------------------+---------------------------+  ← Baris total (manual)
|     | Predikat Nilai Akuntabilitas     |        | ${predikat_tahun_lalu}    | ${predikat_tahun_ini}     |
+-----+----------------------------------+--------+---------------------------+---------------------------+  ← Baris predikat (manual)
```

---

## ⚠️ Yang HARUS DIHINDARI:

### ❌ JANGAN:

1. Menulis nomor urut (1., 2., 3.) **hardcoded** di baris yang akan di-clone
2. Meletakkan baris total/jumlah **sebelum atau di tengah** baris data
3. Menggabung beberapa variabel dalam 1 sel: `a. ${komponen_nama}` ← SALAH!
4. Menulis tahun hardcoded: "TAHUN 2024" ← gunakan ${tahun_sebelumnya}

### ✅ LAKUKAN:

1. Biarkan kolom NO **kosong** di baris data, atau gunakan numbering Word otomatis
2. Letakkan baris total **SETELAH SEMUA** baris data
3. Setiap variabel di **sel terpisah**: sel 1: "a.", sel 2: "${komponen_nama}"
4. Gunakan variabel untuk tahun: ${tahun_sebelumnya}, ${tahun_saat_ini}

---

## 🚀 Testing

Setelah memperbaiki template:

1. **Simpan** template di: `public/assets/template/lap2025.docx`
2. **Pilih OPD** di form ekspor
3. **Isi catatan & rekomendasi** untuk beberapa komponen
4. **Klik Ekspor**
5. **Buka file hasil ekspor**, cek:
    - BAB II: Tabel punya 4 baris komponen + 1 baris total
    - BAB III: Tabel punya 4 baris komponen + 2 baris total/predikat
    - Tidak ada yang muncul berulang-ulang

---

## 💡 Butuh Bantuan?

Lihat dokumentasi lain:

-   [PANDUAN_TEMPLATE_WORD.md](./PANDUAN_TEMPLATE_WORD.md) - Panduan variabel lengkap
-   [CARA_BUAT_TABEL_WORD.md](./CARA_BUAT_TABEL_WORD.md) - Cara membuat tabel dengan cloneRow
-   [PANDUAN_TABEL_PERBANDINGAN.md](./PANDUAN_TABEL_PERBANDINGAN.md) - Detail tabel BAB III
