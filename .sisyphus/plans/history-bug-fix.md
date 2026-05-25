# Plan B — History Bug Fix

## TL;DR

> **Quick Summary**: Perbaiki 2 bug kritis di fitur PenilaianHistory:
> 1. **Action label confused** — `getActionDescription()` salah label untuk role OPD karena membaca `is_perubahan` flag bukan tipe aksi
> 2. **Monitoring tidak record history** — `Monitoring/KriteriaKomponen/BuktiDukung.php` punya 4 method (uploadBuktiDukung, simpanVerifikasi, deleteFileBuktiDukung, simpanPenilaian) yang tidak panggil `recordHistory()` sama sekali
>
> **Deliverables**:
> - `PenilaianHistory::getActionDescription()` rewritten dengan logika benar (tipe aksi, bukan flag)
> - `Monitoring/KriteriaKomponen/BuktiDukung.php` tambah 4-5 `recordHistory()` calls (mirror LembarKerja.php)
> - History sekarang konsisten antara LembarKerja dan Monitoring
>
> **Estimated Effort**: Medium-Low
> **Parallel Execution**: YES — 2 wave (2 task wave 1 paralel, 1 review final)
> **Critical Path**: Wave 1 fixes → Final verification

---

## Context

### Original Request

User aduan:
1. *"untuk fitur history, aksi nya masih kurang sinkon, waktu upload dokumen tertulis di aksi 'melakukan penilaian mandiri' tapi waktu melakukan penilaian mandiri tertulis di aksi 'melakukan revisi/perbaikan'. yang sinkron menurut saya malah penjelasan di kolom keterangan dengan simbol huruf 'i' paling kanan."*
2. *"selanjutnya untuk history yang penilaiannya 'A/B/C/D' saat upload dokumen tidak tertulis dalam history"*

### Investigation Findings (CONFIRMED via code reading)

**Bug #1 Root Cause** ([PenilaianHistory.php:67-72](file:///C:/laragon/www/kke-sakip/app/Models/PenilaianHistory.php#L67-L72)):

```php
if ($roleJenis == 'opd') {
    if ($this->is_perubahan) {
        return 'melakukan revisi/perbaikan';
    }
    return 'melakukan penilaian mandiri';
}
```

Logic ini cek `is_perubahan` (apakah row penilaian sudah ada saat aksi dilakukan), BUKAN tipe aksi (upload vs scoring).

Trace skenario aktual:

| Aksi User | `is_perubahan` saat record | Action Label Saat Ini |
|-----------|----------------------------|----------------------|
| Upload dokumen pertama (penilaian baru) | `false` | "melakukan penilaian mandiri" ❌ |
| Upload re-upload (penilaian sudah ada) | `true` | "melakukan revisi/perbaikan" ✅ |
| Scoring pertama (row sudah dari upload) | `true` (force) | "melakukan revisi/perbaikan" ❌ (harusnya "memberikan penilaian mandiri") |
| Edit scoring | `true` | "melakukan revisi/perbaikan" ✅ |

User benar — labeling salah. `keterangan` column di [LembarKerja.php:1059, 1199, 1260, etc.](file:///C:/laragon/www/kke-sakip/app/Livewire/Dashboard/LembarKerja.php#L1059) lebih akurat:
- Upload → keterangan: `"Upload N file bukti dukung"` atau user input
- Scoring awal → keterangan: `"Penilaian awal"`
- Scoring update → keterangan: `"Update penilaian"`
- Hapus nilai → keterangan: `"Menghapus penilaian"`

**Fix approach**: Cek `tingkatan_nilai_id` untuk distinguish scoring dari upload:
- `tingkatan_nilai_id IS NOT NULL` → action = scoring
- `tingkatan_nilai_id IS NULL` → action = upload/dokumen action
- Tetap pakai `is_perubahan` sebagai modifier (suffix " (revisi)")

**Bug #2 Root Cause** ([Monitoring/KriteriaKomponen/BuktiDukung.php](file:///C:/laragon/www/kke-sakip/app/Livewire/Dashboard/Monitoring/KriteriaKomponen/BuktiDukung.php)):

Investigasi confirmed:

| Method | Lines | Punya `recordHistory()` call? |
|--------|-------|-------------------------------|
| `uploadBuktiDukung()` | 595-688 | **❌ TIDAK** (4 update + 1 create call, 0 recordHistory) |
| `simpanVerifikasi()` | 749-820 | **❌ TIDAK** |
| `deleteFileBuktiDukung()` | 703-747 | **❌ TIDAK** |
| `simpanPenilaian()` | (perlu cek penuh) | **kemungkinan ❌** |

Bandingkan dengan `LembarKerja.php` yang punya 6 `recordHistory()` calls.

**User complain "A/B/C/D tidak tercatat"** — kemungkinan user sering pakai page Monitoring untuk scoring kriteria A/B/C/D (mode kriteria dimana `bukti_dukung_id=null`). Ketika pakai Monitoring, history TIDAK pernah ditulis. Ini explain user complaint exactly.

**Fix approach**: Mirror logika `LembarKerja.php` recordHistory pattern ke Monitoring component. Pastikan setiap update/create Penilaian di Monitoring punya recordHistory call.

### Metis Review

**Identified Gaps**:
- ⚠️ **Risiko regression**: getActionDescription() dipakai di banyak tempat (riwayat modal, tracking modal, dll). Test di multiple view sebelum push.
- ⚠️ **Backward compat**: Existing PenilaianHistory rows yang sudah ter-record dengan logic lama akan tampil DENGAN label baru (yang lebih akurat). Tidak ada data migration needed.
- ⚠️ **Ada kemungkinan Monitoring component punya `simpanPenilaian()`** yang tidak saya cek penuh. Investigasi tambahan sebelum implement.

---

## Work Objectives

### Core Objective

Perbaiki dua bug history yang membuat user bingung dan menyebabkan data tidak tercatat. Pastikan history KONSISTEN dan AKURAT antara LembarKerja dan Monitoring components.

### Concrete Deliverables

**Modifikasi Model**:
- `app/Models/PenilaianHistory.php::getActionDescription()` — rewrite logic untuk role `opd`

**Modifikasi Livewire Component**:
- `app/Livewire/Dashboard/Monitoring/KriteriaKomponen/BuktiDukung.php` — tambah recordHistory di 4 method (uploadBuktiDukung, simpanVerifikasi, deleteFileBuktiDukung, simpanPenilaian jika ada)

### Definition of Done

- [ ] Action label di history UNTUK ROLE OPD:
  - Upload dokumen pertama → "mengupload dokumen" (atau similar)
  - Upload re-upload → "mengupload dokumen (revisi)"
  - Scoring pertama → "memberikan penilaian mandiri"
  - Scoring update → "memberikan penilaian mandiri (revisi)"
  - Hapus dokumen → "menghapus dokumen"
  - Hapus penilaian → "menghapus penilaian"
- [ ] Action label untuk role lain (verifikator, penjamin, penilai) **TIDAK BERUBAH** (sudah benar)
- [ ] Setiap aksi di Monitoring component sekarang menulis ke `penilaian_history`:
  - `uploadBuktiDukung` (mode kriteria + mode bukti)
  - `simpanVerifikasi`
  - `deleteFileBuktiDukung`
  - `simpanPenilaian` jika ada
- [ ] `php artisan migrate:fresh --seed` masih jalan
- [ ] Login as OPD, scoring A/B/C/D dari Monitoring page → history tercatat
- [ ] Existing flow LembarKerja TIDAK berubah (regression test)

### Must Have

- Backward compatible: existing history rows tetap render dengan label yang lebih akurat (no data migration needed)
- Konsistensi: `recordHistory()` calls di Monitoring **MIRROR** signature dan args dari LembarKerja
- Action description harus distinguish 4 jenis aksi OPD: upload, scoring, hapus dokumen, hapus penilaian

### Must NOT Have (Guardrails)

- **JANGAN** ubah `recordHistory()` method di Penilaian model. Hanya tambah CALLS di Monitoring component.
- **JANGAN** ubah skema database (`penilaian_history` kolom).
- **JANGAN** ubah action label untuk role NON-opd (verifikator, penjamin, penilai sudah benar).
- **JANGAN** edit `RekapPenolakan`, `RekapPerbaikan`, `RekapVerifikasi` (itu Plan A).
- **JANGAN** edit `EsakipSyncService.php` atau migration manapun.
- **JANGAN** ubah `LembarKerja.php` recordHistory existing calls (sudah benar — Monitoring yang harus mirror).
- **JANGAN** edit AGENTS.md atau dokumen di `.sisyphus/docs/`.

---

## Verification Strategy

> **ZERO HUMAN INTERVENTION** — semua verifikasi agent-executable.

### Test Decision

- **Infrastructure exists**: YES (PHPUnit, but only stub).
- **Automated tests**: NO.
- **Strategy**: Verify via `migrate:fresh --seed` + `tinker` simulation + Playwright manual page render dengan login OPD + scoring + check history.

### QA Policy

Setiap task wajib include QA Scenarios:
- Code reference checks (Select-String for recordHistory pattern)
- DB state via tinker (count PenilaianHistory rows before/after action)
- Page render via Playwright (login OPD → /monitoring → scoring → check history modal)

Evidence saved ke `.sisyphus/evidence/plan-b-task-{N}-{slug}.{ext}`.

---

## Execution Strategy

### Parallel Execution Waves

```
Wave 1 (Bug fixes, MAX PARALLEL — 2 independent files):
├── Task 1: Rewrite PenilaianHistory::getActionDescription()                      [unspecified-high]
└── Task 2: Add recordHistory calls in Monitoring/KriteriaKomponen/BuktiDukung    [unspecified-high]

Wave FINAL (3 parallel reviews + user okay):
├── Task F1: Plan compliance audit (oracle)
├── Task F2: Manual QA via Playwright (login OPD + Monitoring + check history)
└── Task F3: Scope fidelity check (deep)
-> Present results -> Get user okay
```

### Dependency Matrix

- **T1**: — Depends: none. Blocks: F1-F3.
- **T2**: — Depends: none. Blocks: F1-F3.
- **F1-F3**: — Depends: T1, T2. Blocks: user okay.

### Agent Dispatch Summary

- **Wave 1**: **2 tasks** — T1, T2 → `unspecified-high`
- **FINAL**: **3 tasks** — F1 → `oracle`, F2 → `unspecified-high`, F3 → `deep`

---

## TODOs

- [x] 1. Rewrite `PenilaianHistory::getActionDescription()` for role OPD

  **What to do**:
  - Open `app/Models/PenilaianHistory.php`.
  - Find `getActionDescription()` method (lines 63-93).
  - **Rewrite block role 'opd'** (lines 67-72) dengan logika berbasis tipe aksi:
    ```php
    if ($roleJenis == 'opd') {
        // Tentukan tipe aksi berdasarkan kombinasi field di history record
        $hasTingkatanNilai = $this->tingkatan_nilai_id !== null;
        $keterangan = $this->keterangan ?? '';
        
        // EXACT MATCH terhadap string yang di-SET oleh code (LembarKerja.php)
        // BUKAN substring match — untuk hindari false positive dari user keterangan input
        // String exact match dari LembarKerja.php:1260, 1439:
        if ($keterangan === 'Menghapus penilaian') {
            return 'menghapus penilaian';
        }
        if ($keterangan === 'Menghapus file dokumen') {
            return 'menghapus dokumen';
        }
        
        if ($hasTingkatanNilai) {
            // Scoring action — Penilaian punya tingkatan_nilai_id
            return $this->is_perubahan
                ? 'memberikan penilaian mandiri (revisi)'
                : 'memberikan penilaian mandiri';
        }
        
        // Upload action — tingkatan_nilai null AND bukan delete keterangan
        return $this->is_perubahan
            ? 'mengupload dokumen (revisi)'
            : 'mengupload dokumen';
    }
    ```
  - **PRESERVE block role lain** (verifikator, penjamin, penilai) — JANGAN diubah.
  - Add docblock comment menjelaskan logic baru:
    ```php
    /**
     * Helper untuk mendapatkan deskripsi aksi
     * 
     * Untuk role 'opd': action ditentukan oleh kombinasi (tingkatan_nilai_id, is_perubahan, keterangan):
     * - keterangan EXACT == 'Menghapus penilaian' → 'menghapus penilaian'
     * - keterangan EXACT == 'Menghapus file dokumen' → 'menghapus dokumen'
     * - tingkatan_nilai_id NOT NULL → 'memberikan penilaian mandiri' [+ ' (revisi)' kalau is_perubahan]
     * - tingkatan_nilai_id NULL → 'mengupload dokumen' [+ ' (revisi)' kalau is_perubahan]
     * 
     * IMPORTANT: Pakai EXACT match string yang di-set oleh LembarKerja.php (line 1260, 1439).
     * BUKAN stripos/substring — untuk hindari false positive dari user keterangan input
     * (misal user input 'menghapus dokumen lama dari catatan').
     * 
     * Untuk role verifikator/penjamin/penilai: pakai is_verified flag (existing logic).
     */
    ```
  - Verify with `php -l app/Models/PenilaianHistory.php` (PHP syntax check).

  **Must NOT do**:
  - JANGAN ubah block role `verifikator`, `penjamin`, `penilai`
  - JANGAN tambah/hapus method lain
  - JANGAN ubah relasi atau cast
  - JANGAN ubah `recordHistory()` di Penilaian model
  - JANGAN pakai `stripos`, `str_contains`, atau substring match — gunakan EXACT match (`===`)

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
    - Reason: Logic rewrite di model yang dipakai di banyak tempat. Resiko regression. Butuh attention to detail.
  - **Skills**: []

  **Parallelization**:
  - **Can Run In Parallel**: YES dengan T2
  - **Parallel Group**: Wave 1
  - **Blocks**: F1-F3
  - **Blocked By**: None

  **References**:

  **Pattern References**:
  - `app/Models/PenilaianHistory.php:63-93` — existing `getActionDescription()` method
  - `app/Livewire/Dashboard/LembarKerja.php:1051-1064` — recordHistory call untuk upload (keterangan example: 'Upload N file bukti dukung')
  - `app/Livewire/Dashboard/LembarKerja.php:1191-1201` — recordHistory call untuk scoring (keterangan: 'Penilaian awal' atau 'Update penilaian')
  - `app/Livewire/Dashboard/LembarKerja.php:1252-1262` — recordHistory hapus penilaian (EXACT keterangan: 'Menghapus penilaian')
  - `app/Livewire/Dashboard/LembarKerja.php:1431-1441` — recordHistory hapus file (EXACT keterangan: 'Menghapus file dokumen')
  - `.sisyphus/docs/FLOWS.md` Section 12 — Penilaian History dokumentasi
  - `.sisyphus/docs/KNOWN_BUGS.md` — pattern untuk dokumentasi bug

  **WHY Each Reference Matters**:
  - LembarKerja recordHistory calls show keterangan strings yang KONSTAN dan EXACT — gunakan untuk detect aksi
  - Existing method preserve untuk role non-opd tetap benar
  - Exact match HINDARI false positive dari user keterangan input

  **Acceptance Criteria**:

  - [ ] File `PenilaianHistory.php` masih syntactically valid PHP (`php -l` pass)
  - [ ] Method `getActionDescription()` exist
  - [ ] Block role `opd` rewritten dengan check `tingkatan_nilai_id`
  - [ ] Block role `verifikator`/`penjamin`/`penilai` UNCHANGED (verify via diff)
  - [ ] String "memberikan penilaian mandiri" muncul (scoring)
  - [ ] String "mengupload dokumen" muncul (upload)
  - [ ] String "menghapus" muncul (delete actions)
  - [ ] String " (revisi)" suffix logic ada
  - [ ] **TIDAK ada `stripos` atau `str_contains`** untuk detect aksi (verify dengan grep)
  - [ ] Pakai `===` (strict equality) untuk match keterangan delete

  **QA Scenarios**:

  ```
  Scenario: Code review verification
    Tool: Bash (PowerShell)
    Steps:
      1. php -l app/Models/PenilaianHistory.php (expect "No syntax errors")
      2. Select-String -Path "app/Models/PenilaianHistory.php" -Pattern "memberikan penilaian mandiri" (expect ≥1)
      3. Select-String -Path "app/Models/PenilaianHistory.php" -Pattern "mengupload dokumen" (expect ≥1)
      4. Select-String -Path "app/Models/PenilaianHistory.php" -Pattern "menghapus" (expect ≥2)
      5. Select-String -Path "app/Models/PenilaianHistory.php" -Pattern "is_verified" (expect ≥3, blocks role lain unchanged)
      6. Select-String -Path "app/Models/PenilaianHistory.php" -Pattern "stripos|str_contains" (expect 0 — pakai exact match)
      7. Select-String -Path "app/Models/PenilaianHistory.php" -Pattern "=== 'Menghapus penilaian'" (expect ≥1)
      8. Select-String -Path "app/Models/PenilaianHistory.php" -Pattern "=== 'Menghapus file dokumen'" (expect ≥1)
    Expected Result: All checks pass
    Evidence: .sisyphus/evidence/plan-b-task-1-code-review.txt

  Scenario: Tinker simulation - verify output for each scenario
    Tool: Bash (PowerShell + tinker)
    Steps:
      1. php artisan migrate:fresh --seed
      2. Create test data via tinker (or use existing seeded data)
      3. Test getActionDescription for:
         - History dengan tingkatan_nilai_id, is_perubahan=false → "memberikan penilaian mandiri"
         - History dengan tingkatan_nilai_id, is_perubahan=true → "memberikan penilaian mandiri (revisi)"
         - History tanpa tingkatan_nilai, is_perubahan=false, keterangan="Upload" → "mengupload dokumen"
         - History tanpa tingkatan_nilai, is_perubahan=true → "mengupload dokumen (revisi)"
         - History keterangan="Menghapus penilaian" (EXACT) → "menghapus penilaian"
         - History keterangan="Menghapus file dokumen" (EXACT) → "menghapus dokumen"
         - History keterangan="user-input random text" → fallback ke upload/scoring (TIDAK match delete)
    Expected Result: All return correct string. Random keterangan TIDAK trigger "menghapus".
    Evidence: .sisyphus/evidence/plan-b-task-1-tinker.txt
  ```

  **Evidence to Capture**:
  - [ ] `.sisyphus/evidence/plan-b-task-1-code-review.txt`
  - [ ] `.sisyphus/evidence/plan-b-task-1-tinker.txt`

  **Commit**: NO (akan masuk Commit 1 setelah Wave 1)

- [x] 2. Add `recordHistory()` calls in `Monitoring/KriteriaKomponen/BuktiDukung.php`

  **What to do**:
  
  **STEP 1: VERIFY method existence (MANDATORY before edit)**
  - Read `app/Livewire/Dashboard/Monitoring/KriteriaKomponen/BuktiDukung.php` FULL (812 lines).
  - Inventory ALL public methods yang modify Penilaian (update/create/delete):
    - `uploadBuktiDukung()` — confirmed at line 595-688
    - `simpanVerifikasi()` — confirmed at line 749-820
    - `deleteFileBuktiDukung()` — confirmed at line 703-747
    - `simpanPenilaian()` — **VERIFY EXISTENCE** by grep `function simpanPenilaian`
    - `hapusNilai()` — **VERIFY EXISTENCE** by grep `function hapusNilai`
    - Any OTHER method that touches Penilaian
  - DOCUMENT findings di `.sisyphus/evidence/plan-b-task-2-method-inventory.txt` BEFORE making changes.
  
  **STEP 2: For EACH method that modifies Penilaian, add `recordHistory()` call**
  
  Mirror logika `LembarKerja.php` recordHistory pattern. Setiap call HARUS punya:
  - userId: Auth::id()
  - roleId: relevant role_id (penilaian role atau user role tergantung context)
  - opdId: $this->opd_id
  - kriteriaKomponenId: $this->kriteria_komponen_id
  - buktiDukungId: $this->bukti_dukung_id (atau null untuk mode kriteria)
  - tingkatanNilaiId: relevant value
  - isVerified: relevant value
  - keterangan: context-specific
  - isPerubahan: based on existing penilaian or operation type
  
  **A. uploadBuktiDukung() (line 595-688)**:
  Setelah block update/create Penilaian (around line 681), sebelum `// Simpan message sebelum reset` (line 683):
  ```php
  // Record history - Upload dokumen
  $penilaian = $existingPenilaian ?: Penilaian::where('kriteria_komponen_id', $this->kriteria_komponen_id)
      ->where('bukti_dukung_id', $this->bukti_dukung_id)
      ->where('opd_id', $this->opd_id)
      ->where('role_id', Auth::user()->role_id)
      ->first();
  
  if ($penilaian) {
      $penilaian->recordHistory(
          userId: Auth::id(),
          roleId: $penilaian->role_id,
          opdId: $this->opd_id,
          kriteriaKomponenId: $this->kriteria_komponen_id,
          buktiDukungId: $this->bukti_dukung_id,
          tingkatanNilaiId: $penilaian->tingkatan_nilai_id,
          isVerified: null,
          keterangan: $this->keterangan_upload ?: 'Upload ' . count($uploadedFiles) . ' file bukti dukung',
          isPerubahan: $existingPenilaian !== null
      );
  }
  ```
  
  **B. simpanVerifikasi() (line 749-820)**:
  Setelah update/create Penilaian (around line 819), sebelum reset form:
  ```php
  // Record history - Verifikasi
  $penilaian = $existingPenilaian ?: Penilaian::where('kriteria_komponen_id', $this->kriteria_komponen_id)
      ->where('bukti_dukung_id', $buktiDukungId)
      ->where('opd_id', $this->opd_id)
      ->where('role_id', Auth::user()->role_id)
      ->first();
  
  if ($penilaian) {
      $penilaian->recordHistory(
          userId: Auth::id(),
          roleId: Auth::user()->role_id,
          opdId: $this->opd_id,
          kriteriaKomponenId: $this->kriteria_komponen_id,
          buktiDukungId: $buktiDukungId,
          tingkatanNilaiId: null,
          isVerified: $this->is_verified,
          keterangan: $this->keterangan_verifikasi,
          isPerubahan: $existingPenilaian !== null
      );
  }
  ```
  
  **C. deleteFileBuktiDukung() (line 703-747)**:
  Setelah update Penilaian set link_file=null (around line 740):
  ```php
  // Record history - Hapus file dokumen
  $penilaian->recordHistory(
      userId: Auth::id(),
      roleId: $penilaian->role_id,
      opdId: $this->opd_id,
      kriteriaKomponenId: $this->kriteria_komponen_id,
      buktiDukungId: $this->bukti_dukung_id,
      tingkatanNilaiId: $penilaian->tingkatan_nilai_id,
      isVerified: $penilaian->is_verified,
      keterangan: 'Menghapus file dokumen', // EXACT match dengan LembarKerja - akan match getActionDescription
      isPerubahan: true
  );
  ```
  
  **D. simpanPenilaian() (jika ditemukan di STEP 1)**:
  Mirror LembarKerja.php:1191-1201 EXACTLY. Keterangan: `$isPerubahan ? 'Update penilaian' : 'Penilaian awal'`.
  
  **E. hapusNilai() (jika ditemukan di STEP 1)**:
  Mirror LembarKerja.php:1252-1262 EXACTLY. Keterangan: `'Menghapus penilaian'` (EXACT match supaya getActionDescription return 'menghapus penilaian').

  **STEP 3: Verifikasi via grep setelah implementasi**
  - `Select-String -Path "app/Livewire/Dashboard/Monitoring/KriteriaKomponen/BuktiDukung.php" -Pattern "recordHistory" | Measure-Object` — should be ≥4 (was 0).
  - Verify keterangan exact match strings ('Menghapus file dokumen', 'Menghapus penilaian' jika applicable).

  **CRITICAL: Keterangan Strings HARUS EXACT MATCH**
  - 'Menghapus file dokumen' (lowercase 'file') — match LembarKerja.php:1439 dan T1 fix
  - 'Menghapus penilaian' — match LembarKerja.php:1260 dan T1 fix
  - JANGAN ubah string ini, atau getActionDescription tidak akan match dan label salah lagi.

  **Must NOT do**:
  - JANGAN modify EXISTING update/create logic (logic Penilaian update tetap)
  - JANGAN ubah `getActionDescription` (itu T1)
  - JANGAN ubah LembarKerja.php (sudah benar)
  - JANGAN edit komponen lain
  - JANGAN ubah keterangan strings dari LembarKerja pattern (akan break detection di getActionDescription)

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
    - Reason: Logic addition di komponen kritis. Resiko regression. Butuh symmetry dengan LembarKerja.
  - **Skills**: []

  **Parallelization**:
  - **Can Run In Parallel**: YES dengan T1
  - **Parallel Group**: Wave 1
  - **Blocks**: F1-F3
  - **Blocked By**: None

  **References**:

  **Pattern References**:
  - `app/Livewire/Dashboard/LembarKerja.php:1051-1064` — recordHistory upload pattern (KETERANGAN: 'Upload N file bukti dukung' atau user input)
  - `app/Livewire/Dashboard/LembarKerja.php:1191-1201` — recordHistory scoring pattern (KETERANGAN: 'Penilaian awal' atau 'Update penilaian')
  - `app/Livewire/Dashboard/LembarKerja.php:1252-1262` — recordHistory hapus penilaian (KETERANGAN EXACT: 'Menghapus penilaian')
  - `app/Livewire/Dashboard/LembarKerja.php:1318-1328` — recordHistory verifikasi pattern (KETERANGAN: $this->keterangan_verifikasi)
  - `app/Livewire/Dashboard/LembarKerja.php:1431-1441` — recordHistory hapus file (KETERANGAN EXACT: 'Menghapus file dokumen')
  - `app/Models/Penilaian.php:59-82` — `recordHistory()` method signature
  - `.sisyphus/docs/FLOWS.md` Section 12 — Penilaian History flow

  **WHY Each Reference Matters**:
  - LembarKerja sudah implement recordHistory dengan benar — Monitoring harus MIRROR exactly
  - Keterangan strings 'Menghapus penilaian' dan 'Menghapus file dokumen' adalah CONTRACT antara recordHistory caller dan getActionDescription. Harus EXACT.

  **Acceptance Criteria**:

  - [ ] STEP 1 done: `.sisyphus/evidence/plan-b-task-2-method-inventory.txt` exists dengan list method
  - [ ] File `Monitoring/KriteriaKomponen/BuktiDukung.php` masih syntactically valid PHP
  - [ ] Method `uploadBuktiDukung()` punya recordHistory call (≥1)
  - [ ] Method `simpanVerifikasi()` punya recordHistory call (≥1)
  - [ ] Method `deleteFileBuktiDukung()` punya recordHistory call (≥1)
  - [ ] Method `simpanPenilaian()` punya recordHistory call (jika method exists per STEP 1)
  - [ ] Method `hapusNilai()` punya recordHistory call (jika method exists per STEP 1)
  - [ ] Total recordHistory calls di file ini ≥4 (sebelumnya 0)
  - [ ] Keterangan EXACT strings:
    - `'Menghapus file dokumen'` di deleteFileBuktiDukung
    - `'Menghapus penilaian'` di hapusNilai (jika exists)
  - [ ] LembarKerja.php tidak diubah (verify via git diff)
  - [ ] `php artisan migrate:fresh --seed` exit 0

  **QA Scenarios**:

  ```
  Scenario: Method inventory + recordHistory now called from Monitoring
    Tool: Bash (PowerShell)
    Steps:
      1. Read Monitoring/KriteriaKomponen/BuktiDukung.php FULL
      2. Grep: Select-String -Pattern "function (uploadBuktiDukung|simpanVerifikasi|deleteFileBuktiDukung|simpanPenilaian|hapusNilai)" 
      3. Document findings ke .sisyphus/evidence/plan-b-task-2-method-inventory.txt
      4. php -l app/Livewire/Dashboard/Monitoring/KriteriaKomponen/BuktiDukung.php (expect "No syntax errors")
      5. Select-String -Pattern "recordHistory" | Measure-Object (expect ≥4)
      6. Select-String -Pattern "'Menghapus file dokumen'" | Measure-Object (expect ≥1 di deleteFileBuktiDukung)
      7. Verify LembarKerja unchanged: git diff -- app/Livewire/Dashboard/LembarKerja.php | Measure-Object -Line (expect 0)
    Expected Result: All checks pass
    Evidence: .sisyphus/evidence/plan-b-task-2-code-review.txt

  Scenario: End-to-end - upload from Monitoring records history
    Tool: Bash (PowerShell + tinker)
    Steps:
      1. php artisan migrate:fresh --seed
      2. tinker: count PenilaianHistory rows BEFORE
      3. (Manual) login as OPD, navigate /monitoring/.../bukti-dukung, upload file
      4. tinker: count PenilaianHistory rows AFTER (expect +1)
      5. Verify last row has correct fields (role_id, opd_id, kriteria_komponen_id, etc.)
    Expected Result: History count increased by 1, fields populated correctly
    Evidence: .sisyphus/evidence/plan-b-task-2-history-recorded.txt

  Scenario: getActionDescription correctly labels Monitoring upload
    Tool: Bash (PowerShell + tinker)
    Preconditions: Both T1 and T2 complete
    Steps:
      1. After upload from Monitoring, query last PenilaianHistory row
      2. Call ->getActionDescription()
      3. Expected: "mengupload dokumen" or "mengupload dokumen (revisi)" — NOT "melakukan penilaian mandiri"
    Expected Result: Action label correct
    Evidence: .sisyphus/evidence/plan-b-task-2-action-label.txt
  ```

  **Evidence to Capture**:
  - [ ] `.sisyphus/evidence/plan-b-task-2-method-inventory.txt`
  - [ ] `.sisyphus/evidence/plan-b-task-2-code-review.txt`
  - [ ] `.sisyphus/evidence/plan-b-task-2-history-recorded.txt`
  - [ ] `.sisyphus/evidence/plan-b-task-2-action-label.txt`

  **Commit**: YES (Commit 1 setelah T1+T2 selesai)
  - Message: `fix(history): correct action labels for OPD + add recordHistory in Monitoring component`
  - Files staged: `app/Models/PenilaianHistory.php`, `app/Livewire/Dashboard/Monitoring/KriteriaKomponen/BuktiDukung.php`
  - Pre-commit verification: `php artisan migrate:fresh --seed` exit 0 + `php -l` both files exit 0

---

## Final Verification Wave (MANDATORY — after ALL implementation tasks)

> 3 review agents run in PARALLEL. ALL must APPROVE.

- [x] F1. **Plan Compliance Audit** — `oracle`
  Read this plan end-to-end. Verify Must Have/Must NOT Have. Check getActionDescription rewrite preserves non-opd blocks. Check Monitoring component has ≥4 recordHistory calls. Check LembarKerja unchanged.
  Output: `Must Have [N/N] | Must NOT Have [N/N] | VERDICT: APPROVE/REJECT`

- [x] F2. **Manual QA via Playwright** — `unspecified-high` (+ `playwright` skill)
  Login as OPD (e.g. `disdikpora@trenggalekkab.go.id`). Navigate `/monitoring`, pilih kriteria mode kriteria (penilaian_di='kriteria') with jenis_nilai='A/B/C/D'. Upload dokumen, lalu scoring. Open tracking modal. Verify history shows:
  1. Action label "mengupload dokumen" (bukan "melakukan penilaian mandiri")
  2. Action label "memberikan penilaian mandiri" untuk scoring
  3. Tracking modal show NEW history entry untuk Monitoring upload (sebelum tidak ada)
  Save screenshots.
  Output: `Action labels [PASS/FAIL] | Monitoring history recorded [PASS/FAIL] | VERDICT`

- [x] F3. **Scope Fidelity Check** — `deep`
  Verify only 2 files diff'd (PenilaianHistory.php, Monitoring/.../BuktiDukung.php). Verify role non-opd block UNCHANGED. Verify LembarKerja unchanged. Verify no schema migration.
  Output: `Files [2/2] | Forbidden changes [CLEAN/N issues] | VERDICT`

---

## Commit Strategy

Single commit setelah Wave 1 selesai:

- **Commit**: `fix(history): correct action labels for OPD + add recordHistory in Monitoring component`
  - Files staged:
    - **MODIFIED**: `app/Models/PenilaianHistory.php` (getActionDescription rewrite)
    - **MODIFIED**: `app/Livewire/Dashboard/Monitoring/KriteriaKomponen/BuktiDukung.php` (4-5 recordHistory calls added)
  - Pre-commit verification:
    - `php -l app/Models/PenilaianHistory.php` exit 0
    - `php -l app/Livewire/Dashboard/Monitoring/KriteriaKomponen/BuktiDukung.php` exit 0
    - `php artisan migrate:fresh --seed` exit 0
    - `git diff --stat -- app/Livewire/Dashboard/LembarKerja.php` shows 0 lines changed

---

## Success Criteria

### Verification Commands

```powershell
# Files unchanged check
git diff --stat -- app/Livewire/Dashboard/LembarKerja.php   # Expect 0 lines

# Files modified check
git diff --stat HEAD~1..HEAD | Select-String "PenilaianHistory|Monitoring.*BuktiDukung"  # Expect ≥2 matches

# PHP syntax valid
php -l app/Models/PenilaianHistory.php
php -l app/Livewire/Dashboard/Monitoring/KriteriaKomponen/BuktiDukung.php

# recordHistory now in Monitoring
Select-String -Path "app/Livewire/Dashboard/Monitoring/KriteriaKomponen/BuktiDukung.php" -Pattern "recordHistory" | Measure-Object  # Expect ≥4

# Migrate masih sukses
php artisan migrate:fresh --seed   # Expect exit 0

# Action label strings present
Select-String -Path "app/Models/PenilaianHistory.php" -Pattern "memberikan penilaian mandiri"  # ≥1
Select-String -Path "app/Models/PenilaianHistory.php" -Pattern "mengupload dokumen"  # ≥1
```
