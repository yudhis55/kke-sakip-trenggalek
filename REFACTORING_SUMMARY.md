# Refactoring Summary: File Storage Migration

## Overview

Successfully migrated file storage metadata from dedicated `file_bukti_dukung` table to unified `penilaian` table.

**Date:** December 21, 2025  
**Status:** ✅ Complete - Backend & Views Updated

---

## Database Changes

### Migration: `2025_12_21_131315_add_file_columns_to_penilaian_table.php`

**Added Columns:**

-   `link_file` (JSON, nullable) - Stores array of file metadata
-   `is_perubahan` (boolean, default false) - Marks document as revision/change

**Removed:**

-   `file_bukti_dukung_id` (foreign key) - No longer needed

**Status:** ✅ Executed successfully with `php artisan migrate:fresh --seed`

---

## Model Changes

### `app/Models/Penilaian.php`

**Added:**

```php
protected $casts = [
    'link_file' => 'array',
    'is_perubahan' => 'boolean',
];
```

**Commented Out:**

-   `file_bukti_dukung()` relationship (deprecated)

### `app/Models/BuktiDukung.php`

**Commented Out:**

-   `file_bukti_dukung()` relationship (deprecated)

---

## Backend Changes

### `app/Livewire/Dashboard/LembarKerja/KriteriaKomponen/BuktiDukung.php`

#### 1. **uploadBuktiDukung()** (lines ~473-575)

**Before:**

-   Created/updated `FileBuktiDukung` records
-   Stored files separately from penilaian

**After:**

-   Creates/updates `Penilaian` records with `link_file` array
-   Queries by: `kriteria_komponen_id`, `opd_id`, `role_id` (OPD), `bukti_dukung_id` (mode-dependent)
-   **REPLACE mode:** Deletes old files via `Storage::delete()`, saves new array
-   **APPEND mode:** Merges new files with existing array

#### 2. **deleteFileBuktiDukung()** (lines ~577-625)

**Before:**

-   Found `FileBuktiDukung` record and deleted it

**After:**

-   Finds `Penilaian` record, deletes physical files, sets `link_file` to `null`
-   Preserves `keterangan` field (may contain penilaian mandiri notes)
-   Does not delete Penilaian record (preserves evaluation history)

#### 3. **selectedFileBuktiDukung()** (lines ~439-475)

**Before:**

-   Queried `FileBuktiDukung` table
-   Used `json_decode()` on `link_file`

**After:**

-   Queries `Penilaian` table for OPD role
-   Returns `link_file` array (auto-decoded by model cast)
-   Uses subquery to find OPD `role_id`

#### 4. **riwayatVerifikasi()** (lines ~477-495)

**Before:**

-   Filtered by `file_bukti_dukung_id` FK

**After:**

-   Filters by `kriteria_komponen_id`, `opd_id`, `bukti_dukung_id` (mode-dependent)
-   Checks `whereNotNull('is_verified')`

#### 5. **semuaBuktiDukungDenganDokumen()** (lines ~194-220)

**Before:**

-   Eager loaded `with(['file_bukti_dukung'])`

**After:**

-   Queries `BuktiDukung`, then loops to attach `Penilaian` data
-   Adds virtual property: `$bukti->penilaian_opd = Penilaian::where(...)->first()`
-   Compatible with existing view structure

#### 6. **canDoPenilaian()** (lines ~133-195)

**Before:**

-   Checked `FileBuktiDukung::exists()`

**After:**

-   Checks `Penilaian::whereNotNull('link_file')->exists()`
-   Gets OPD role_id: `Role::where('jenis', 'opd')->first()?->id`
-   Validates file existence before allowing penilaian

#### 7. **buktiDukungList()** (lines ~425-447)

**Before:**

-   Eager loaded `with(['file_bukti_dukung'])`

**After:**

-   Queries `BuktiDukung`, attaches virtual `penilaian_opd` property
-   Similar pattern to `semuaBuktiDukungDenganDokumen()`

#### 8. **Imports Updated**

**Removed:**

-   `use App\Models\FileBuktiDukung;`

**Added:**

-   `use App\Models\Role;` (needed for OPD role queries)

---

## View Changes

### `resources/views/livewire/dashboard/lembar-kerja/kriteria-komponen/bukti-dukung.blade.php`

#### Mode Kriteria - Document Display (lines ~355-465)

**Before:**

```blade
@if ($buktiItem->file_bukti_dukung->isNotEmpty())
    @foreach ($buktiItem->file_bukti_dukung as $uploadIndex => $fileRecord)
        @php $files = json_decode($fileRecord->link_file, true); @endphp
```

**After:**

```blade
@if ($buktiItem->penilaian_opd && $buktiItem->penilaian_opd->link_file)
    @php $files = $buktiItem->penilaian_opd->link_file; @endphp
```

**Changes:**

-   Removed nested `@foreach` loop (single penilaian record per bukti dukung)
-   No `json_decode()` needed (auto-decoded by cast)
-   Access metadata directly: `$buktiItem->penilaian_opd->is_perubahan`, `->keterangan`, `->created_at`
-   Simplified tab IDs (removed `$uploadIndex` variable)

#### Mode Bukti - Metadata Section (lines ~475-512)

**Before:**

```blade
@php
    $fileBuktiDukungRecord = \App\Models\FileBuktiDukung::where('bukti_dukung_id', $bukti_dukung_id)
        ->where('opd_id', $opd_id)
        ->first();
@endphp
@if ($fileBuktiDukungRecord)
```

**After:**

```blade
@php
    $penilaianOpdRecord = \App\Models\Penilaian::where('bukti_dukung_id', $bukti_dukung_id)
        ->where('opd_id', $opd_id)
        ->where('role_id', function($query) {
            $query->select('id')->from('role')->where('jenis', 'opd')->limit(1);
        })
        ->whereNotNull('link_file')
        ->first();
@endphp
@if ($penilaianOpdRecord)
```

**Changes:**

-   Query `Penilaian` instead of `FileBuktiDukung`
-   Access properties: `$penilaianOpdRecord->is_perubahan`, `->keterangan`, `->created_at`

#### Bukti Dukung List - Status Check (lines ~188-220)

**Before:**

```blade
@elseif ($bukti_dukung->file_bukti_dukung->isEmpty())
```

**After:**

```blade
@elseif (!$bukti_dukung->penilaian_opd || !$bukti_dukung->penilaian_opd->link_file)
```

**Changes:**

-   Check virtual `penilaian_opd` property instead of relationship
-   Verify `link_file` is not null

---

## Query Patterns

### Finding OPD Files

**Pattern:**

```php
Penilaian::where('kriteria_komponen_id', $id)
    ->where('opd_id', $opd_id)
    ->where('role_id', function($query) {
        $query->select('id')->from('role')->where('jenis', 'opd')->limit(1);
    })
    ->when($penilaianDiKriteria, function($q) {
        return $q->whereNull('bukti_dukung_id');
    }, function($q) use ($bukti_dukung_id) {
        return $q->where('bukti_dukung_id', $bukti_dukung_id);
    })
    ->whereNotNull('link_file')
    ->first();
```

### Mode Detection

-   **Mode Kriteria:** `$this->penilaianDiKriteria == true` → `whereNull('bukti_dukung_id')`
-   **Mode Bukti:** `$this->penilaianDiKriteria == false` → `where('bukti_dukung_id', $id)`

---

## File Storage (Unchanged)

**Physical Storage Location:** `storage/app/public/bukti_dukung/`

**Metadata Format (link_file JSON):**

```json
[
    {
        "path": "bukti_dukung/xxx.pdf",
        "original_name": "Document.pdf",
        "size": 1024,
        "mime_type": "application/pdf"
    }
]
```

---

## Benefits

1. **Simplified Data Model:** Single record per role per bukti dukung
2. **Easier Queries:** No JOIN needed, direct access to files
3. **Better Tracking:** All evaluation data in one table
4. **Unified Keterangan:** Upload notes + penilaian notes in same field
5. **Consistent Updates:** Update existing record instead of creating new ones

---

## Evaluation Workflow (After Refactoring)

```
1. OPD uploads files
   → Creates Penilaian record with link_file (role_id = OPD)

2. OPD does penilaian mandiri
   → Updates same Penilaian record with tingkatan_nilai_id

3. Verifikator verifies
   → Creates/updates Penilaian record (role_id = Verifikator) with is_verified

4. Penjamin verifies + assesses
   → Creates/updates Penilaian record (role_id = Penjamin) with is_verified + tingkatan_nilai_id

5. Penilai assesses
   → Creates/updates Penilaian record (role_id = Penilai) with tingkatan_nilai_id
```

---

## Testing Checklist

-   [ ] Upload new files (OPD) → Should create Penilaian record
-   [ ] Upload additional files (APPEND mode) → Should merge arrays
-   [ ] Replace all files (REPLACE mode) → Should delete old, save new
-   [ ] Delete files → Should null link_file, keep record
-   [ ] Display files (kriteria mode) → Show all bukti dukung grouped
-   [ ] Display files (bukti mode) → Show selected bukti only
-   [ ] Validation → Check link_file not null in Penilaian
-   [ ] Tracking modal → Query Penilaian for all roles
-   [ ] Penilaian mandiri → Update existing record
-   [ ] Verifikasi → Create/update with is_verified
-   [ ] Penjamin/Penilai → Create/update with tingkatan_nilai_id

---

## Future Cleanup

**Optional (Not Required):**

-   Drop `file_bukti_dukung` table (after thorough testing)
-   Remove `FileBuktiDukung` model file
-   Clean up commented relationships

**Recommendation:** Keep deprecated table for 1-2 months as backup before dropping.

---

## Files Modified

### Database

-   ✅ `database/migrations/2025_12_21_131315_add_file_columns_to_penilaian_table.php` (new)

### Models

-   ✅ `app/Models/Penilaian.php`
-   ✅ `app/Models/BuktiDukung.php`

### Livewire Components

-   ✅ `app/Livewire/Dashboard/LembarKerja/KriteriaKomponen/BuktiDukung.php`

### Views

-   ✅ `resources/views/livewire/dashboard/lembar-kerja/kriteria-komponen/bukti-dukung.blade.php`

---

## Summary

**Total Files Modified:** 5  
**Backend Methods Refactored:** 7/7 (100%)  
**View Sections Updated:** 3/3 (100%)  
**Database Migrations:** 1 executed successfully  
**Tests Required:** 11 scenarios

**Status:** ✅ Refactoring Complete - Ready for Testing
