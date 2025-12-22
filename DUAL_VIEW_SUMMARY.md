# DUAL VIEW LEMBAR KERJA - 23 Des 2025

## 🎯 PERUBAHAN

Halaman Lembar Kerja sekarang memiliki **2 tampilan berbeda** berdasarkan role:

### ✅ ROLE OPD → Card-Based (Seperti Sebelumnya)

-   Nav Pills horizontal untuk komponen
-   Card grid 3 kolom untuk sub komponen
-   Icon folder, badge bobot & nilai
-   Alpine.js tabs untuk switch komponen

### ✅ ROLE SELAIN OPD → Table-Based (3-Tier)

-   **TIER 1:** Tabel OPD (pilih OPD)
-   **TIER 2:** Tabel Komponen (pilih komponen)
-   **TIER 3:** Tabel Sub Komponen (ke kriteria)
-   Progress bar, breadcrumb, total footer

---

## 📊 PERBEDAAN

| Aspek             | OPD   | Non-OPD |
| ----------------- | ----- | ------- |
| Navigasi Komponen | Tabs  | Tabel   |
| Sub Komponen      | Cards | Tabel   |
| Pemilihan OPD     | Auto  | Manual  |
| Progress          | ❌    | ✅      |
| Breadcrumb        | ❌    | ✅      |
| Alpine.js         | ✅    | ❌      |

---

## 🔧 FILE DIUBAH

**1 file:**

-   `resources/views/livewire/dashboard/lembar-kerja.blade.php`

**Struktur:**

```blade
@if (Auth::user()->role->jenis == 'opd')
    {{-- Card view dengan Alpine.js --}}
@endif

@if (Auth::user()->role->jenis != 'opd')
    {{-- 3-tier table view --}}
@endif
```

---

## ✅ BENEFIT

1. **OPD:** Tetap pakai UI familiar (no learning curve)
2. **Evaluator:** Bisa compare antar OPD dengan tabel
3. **Maintainable:** 1 component, 2 view, jelas kondisinya
4. **Backward compatible:** OPD workflow tidak berubah
