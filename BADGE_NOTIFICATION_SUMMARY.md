# Badge Notification Summary

## Overview

Badge merah dengan animasi pulse ditampilkan pada item (Komponen, Sub Komponen, Kriteria Komponen, Bukti Dukung) yang memiliki penolakan dari verifikator atau penjamin.

## Kondisi is_verified

### Database Schema

```php
$table->boolean('is_verified')->nullable();
```

### Nilai Possible

-   **`null`**: Belum diverifikasi (default saat pertama kali insert jika tidak diisi)
-   **`0` atau `false`**: DITOLAK ❌ - Badge merah akan muncul
-   **`1` atau `true`**: DISETUJUI ✅ - Tidak ada badge

### Saat Insert/Update

Berdasarkan kode di `LembarKerja.php` line 872:

```php
'is_verified' => 'required|boolean',
```

Dan line 899:

```php
'is_verified' => $this->is_verified,
```

**Jadi:** Saat insert/update, field `is_verified` **required** dan harus bernilai boolean (0 atau 1). Tidak boleh null saat submit form verifikasi.

## Logic Badge Notification

### Method Helper: `hasRejection($item, $type)`

Method ini mengecek apakah ada penolakan (`is_verified = false/0`) pada:

1. Diri sendiri
2. Semua child/anak-anaknya

### Implementasi Per Level

#### 1. **Komponen Level**

```php
case 'komponen':
    // Cek di semua kriteria komponen yang punya bukti dukung
    return DB::table('penilaian')
        ->join('bukti_dukung', ...)
        ->join('kriteria_komponen', ...)
        ->join('sub_komponen', ...)
        ->where('sub_komponen.komponen_id', $item->id)
        ->where('penilaian.opd_id', $opdId)
        ->where('penilaian.is_verified', false)  // ← PENOLAKAN
        ->exists();
```

**Badge muncul jika:** Ada penilaian dengan `is_verified = 0` di salah satu sub komponen → kriteria → bukti dukung

#### 2. **Sub Komponen Level**

```php
case 'sub_komponen':
    // Cek di semua kriteria komponen dari sub komponen ini
    return DB::table('penilaian')
        ->join('bukti_dukung', ...)
        ->join('kriteria_komponen', ...)
        ->where('kriteria_komponen.sub_komponen_id', $item->id)
        ->where('penilaian.opd_id', $opdId)
        ->where('penilaian.is_verified', false)  // ← PENOLAKAN
        ->exists();
```

**Badge muncul jika:** Ada penilaian dengan `is_verified = 0` di salah satu kriteria → bukti dukung

#### 3. **Kriteria Komponen Level**

```php
case 'kriteria':
    // Cek di penilaian level kriteria ATAU di bukti dukungnya

    // Level kriteria (penilaian_di = 'kriteria')
    $hasRejectionKriteria = DB::table('penilaian')
        ->whereNull('bukti_dukung_id')
        ->where('kriteria_komponen_id', $item->id)
        ->where('opd_id', $opdId)
        ->where('is_verified', false)  // ← PENOLAKAN
        ->exists();

    // Level bukti dukung (penilaian_di = 'bukti')
    $hasRejectionBukti = DB::table('penilaian')
        ->join('bukti_dukung', ...)
        ->where('bukti_dukung.kriteria_komponen_id', $item->id)
        ->where('penilaian.opd_id', $opdId)
        ->where('penilaian.is_verified', false)  // ← PENOLAKAN
        ->exists();

    return $hasRejectionKriteria || $hasRejectionBukti;
```

**Badge muncul jika:**

-   Ada penilaian dengan `is_verified = 0` di kriteria itu sendiri, ATAU
-   Ada penilaian dengan `is_verified = 0` di salah satu bukti dukung

#### 4. **Bukti Dukung Level**

```php
case 'bukti':
    // Cek langsung di penilaian bukti dukung ini
    return DB::table('penilaian')
        ->where('bukti_dukung_id', $item->id)
        ->where('opd_id', $opdId)
        ->where('is_verified', false)  // ← PENOLAKAN
        ->exists();
```

**Badge muncul jika:** Ada penilaian dengan `is_verified = 0` di bukti dukung ini

## UI Implementation

### CSS Animation

```css
.badge.pulsate {
    display: inline-block;
    background-color: red;
    border-radius: 50%;
    width: 8px;
    height: 8px;
    padding: 0;
    position: relative;
}

.badge.pulsate::before {
    content: "";
    display: block;
    position: absolute;
    top: -4px;
    left: -4px;
    right: -4px;
    bottom: -4px;
    animation: pulse 1s ease infinite;
    border-radius: 50%;
    border: 2px solid rgba(255, 100, 100, 0.6);
}

@keyframes pulse {
    0% {
        transform: scale(1);
        opacity: 1;
    }
    60% {
        transform: scale(1.3);
        opacity: 0.4;
    }
    100% {
        transform: scale(1.4);
        opacity: 0;
    }
}
```

### Blade Template

```blade
@php
    $hasRejection = $this->hasRejection($lembar_kerja, 'komponen');
@endphp

<td>
    <span class="position-relative">
        {{ $lembar_kerja->nama }}
        @if ($hasRejection)
            <span class="position-absolute top-0 start-100 translate-middle badge pulsate"></span>
        @endif
    </span>
</td>
```

## Use Case Scenario

### Scenario 1: OPD Upload → Verifikator Tolak

1. OPD upload bukti dukung
2. Verifikator verifikasi dengan `is_verified = 0` (tolak)
3. Badge merah muncul di:
    - ✅ Bukti dukung tersebut
    - ✅ Kriteria komponen parent
    - ✅ Sub komponen parent
    - ✅ Komponen parent

### Scenario 2: Penjamin Tolak

1. OPD upload dan verifikator setuju (`is_verified = 1`)
2. Penjamin menilai dengan `is_verified = 0` (tolak)
3. Badge merah muncul di semua level parent (sama seperti scenario 1)

### Scenario 3: Mix Approval & Rejection

1. Kriteria A punya 3 bukti dukung:
    - Bukti 1: `is_verified = 1` ✅
    - Bukti 2: `is_verified = 0` ❌ (DITOLAK)
    - Bukti 3: `is_verified = null` (belum diverifikasi)
2. Badge merah muncul di:
    - ✅ Bukti 2 (yang ditolak)
    - ✅ Kriteria A (karena ada anak yang ditolak)
    - ✅ Sub Komponen parent
    - ✅ Komponen parent

## Performance Consideration

Method `hasRejection()` menggunakan query database dengan `exists()` yang efisien karena:

-   Hanya return boolean (tidak fetch semua data)
-   Stop query saat menemukan 1 record yang match
-   Menggunakan index pada foreign key dan `is_verified`

**Optimasi Future:**

-   Cache result per request menggunakan static array
-   Eager load relationship untuk bulk checking

## Testing Checklist

-   [ ] Test badge muncul saat `is_verified = 0`
-   [ ] Test badge hilang saat `is_verified = 1`
-   [ ] Test badge tidak muncul saat `is_verified = null`
-   [ ] Test cascade: rejection di bukti → kriteria → sub → komponen
-   [ ] Test mix scenario: beberapa approve, beberapa reject
-   [ ] Test per OPD (badge hanya muncul untuk OPD yang bersangkutan)
-   [ ] Test animasi pulse berfungsi dengan baik
-   [ ] Test responsive di mobile

## Related Files

-   **Controller:** `app/Livewire/Dashboard/LembarKerja.php` (line 1108-1180)
-   **View:** `resources/views/livewire/dashboard/lembar-kerja.blade.php`
-   **CSS:** Inline style di view (line 2-38)
-   **Migration:** `database/migrations/2025_12_01_033157_create_penilaian_verifikators_table.php`
-   **Model:** `app/Models/Penilaian.php`
