# Plan D — UX Bug Fix Round 2

## TL;DR

> **Quick Summary**: Fix 4 UX bugs reported by user:
> - **BUG-A**: Set Halaman button hanya muncul di LembarKerja mode bukti — tambah ke mode kriteria
> - **BUG-B**: Tombol Hapus dokumen hanya muncul di LembarKerja mode bukti — tambah ke mode kriteria
> - **BUG-C**: FilePond state (blue bar "upload complete") persist saat navigasi antar bukti dukung via prev/next
> - **BUG-E**: Tombol Hapus saat ini menghapus SELURUH dokumen — tambah opsi hapus per-file (hybrid)
>
> **Format file & ukuran (BUG-D)**: SKIPPED per user decision (pending consideration)
>
> **Deliverables**:
> - Set Halaman + Hapus button ditambahkan di mode kriteria block di LembarKerja blade
> - `previousBuktiDukung()` dan `nextBuktiDukung()` reset FilePond state lengkap
> - `wire:key` di FilePond component agar instance recreate saat bukti_dukung_id berubah
> - NEW method `deleteFileByIndex($index)` untuk hapus per-file
> - Tombol delete per-file (icon trash kecil) di setiap dokumen
> - Tombol "Hapus Semua" tetap ada untuk hapus seluruh link_file
>
> **Estimated Effort**: Medium-High
> **Parallel Execution**: YES — 2 wave (3 task wave 1 paralel, 1 task wave 2)
> **Critical Path**: Wave 1 fixes → Final verification

---

## Context

### Investigation Findings (CONFIRMED via direct code reading)

**BUG-A** ([lembar-kerja.blade.php](file:///C:/laragon/www/kke-sakip/resources/views/livewire/dashboard/lembar-kerja.blade.php)):
- Mode kriteria block: lines ~2000-2168 (`@foreach buktiItem`) — TIDAK ada Set Halaman button
- Mode bukti block: lines ~2179-2470 (`@else` dari `penilaianDiKriteria`) — punya 3 Set Halaman buttons
- User di mode kriteria tidak bisa set page number per file

**BUG-B** ([lembar-kerja.blade.php:2223](file:///C:/laragon/www/kke-sakip/resources/views/livewire/dashboard/lembar-kerja.blade.php#L2223)):
- Hanya 1 delete button di blade, di line 2223 — INSIDE mode bukti `@else` block
- Mode kriteria TIDAK ada delete button
- User di mode kriteria tidak bisa hapus dokumen

**BUG-C** ([LembarKerja.php:246-283](file:///C:/laragon/www/kke-sakip/app/Livewire/Dashboard/LembarKerja.php#L246-L283)):
- `previousBuktiDukung()` line 261: hanya panggil `resetPenilaianForm()`
- `nextBuktiDukung()` line 281: hanya panggil `resetPenilaianForm()`
- `resetPenilaianForm()` line 286-293: HANYA reset 5 fields (tingkatan_nilai_id, catatan, is_editing, is_verified, keterangan_verifikasi)
- TIDAK reset: `$file_bukti_dukung`, `$temporary_file_names`, `$file_count`, `$file_page_numbers`, `$keterangan_upload`, `$is_perubahan`, `$ganti_semua_dokumen`
- FilePond component di blade line 2486 tidak punya `wire:key` per bukti_dukung_id
- Akibat: state FilePond persist visual antar bukti dukung

**BUG-E** ([LembarKerja.php:1424-1427](file:///C:/laragon/www/kke-sakip/app/Livewire/Dashboard/LembarKerja.php#L1424-L1427)):
- `deleteFileBuktiDukung()` set `link_file = null` (hapus SELURUH array)
- `wire:confirm` di blade line 2224 sudah accurate ("Yakin ingin menghapus semua dokumen?")
- Tidak ada method untuk hapus per-index

### User Decisions

- **Format file & ukuran (BUG-D)**: SKIP — user belum decide
- **Delete per-file**: Hybrid — keep "Hapus Semua" + add per-file delete
- **Set Halaman + Hapus mode kriteria**: LembarKerja saja, skip Monitoring component

---

## Work Objectives

### Core Objective

Make UI consistent across mode kriteria and mode bukti di LembarKerja, plus fix navigation state bug, plus enable per-file deletion.

### Concrete Deliverables

**`app/Livewire/Dashboard/LembarKerja.php` modifications**:
1. Add new method `deleteFileByIndex($index)` — hapus 1 file dari link_file array by index
2. Modify `previousBuktiDukung()` line 246-263 — call new full state reset method
3. Modify `nextBuktiDukung()` line 266-283 — call same full reset method
4. Add new private method `resetUploadFormStates()` — reset all upload-related state

**`resources/views/livewire/dashboard/lembar-kerja.blade.php` modifications**:
5. Add `wire:key="filepond-{{ $bukti_dukung_id }}"` to FilePond component (line ~2486)
6. Add Set Halaman button to mode kriteria block (line ~2086-2105 area, inside foreach buktiItem)
7. Add Hapus Semua button to mode kriteria block (similar location)
8. Add per-file delete button (icon trash) di SETIAP dokumen tab di mode bukti AND mode kriteria

### Definition of Done

- [ ] Login as OPD, navigate to bukti dukung di mode kriteria → Set Halaman button visible
- [ ] Login as OPD, navigate to bukti dukung di mode kriteria → Hapus Semua button visible
- [ ] Upload file di bukti dukung A, click "Next" → FilePond bar di bukti dukung B kosong/clean (BUG-C fixed)
- [ ] Upload 3 files, click delete icon di file #2 → file #2 terhapus, file #1 dan #3 tetap
- [ ] Click "Hapus Semua" tetap berfungsi (hapus semua 3 files)
- [ ] `php -l` passes
- [ ] `php artisan migrate:fresh --seed` exits 0

### Must Have

- Backward compat: existing flow di mode bukti TIDAK BERUBAH behavior
- Per-file delete: hapus dari Storage juga (kecuali file dari eSAKIP — cek `from_esakip` flag)
- Per-file delete: trigger `recordHistory()` dengan keterangan deskriptif
- Set Halaman button mode kriteria: pakai handler `openSetPageNumberModal($index)` yang SUDAH ADA — tidak perlu method baru
- FilePond `wire:key` harus include `bukti_dukung_id` agar Livewire tahu kapan recreate

### Must NOT Have (Guardrails)

- **JANGAN** ubah `recordHistory()` atau `getActionDescription()` (sudah benar dari Plan B)
- **JANGAN** ubah validation rules `mimes:pdf` (BUG-D format SKIP per user decision)
- **JANGAN** ubah `Monitoring/KriteriaKomponen/BuktiDukung.php` (out of scope per user)
- **JANGAN** edit migration files
- **JANGAN** ubah `RekapPenolakan/Perbaikan/Verifikasi` (out of scope)
- **JANGAN** edit AGENTS.md atau .sisyphus/docs/
- **JANGAN** ubah `EsakipSyncService.php`
- **JANGAN** delete files lama (hanya tambah/edit)
- **JANGAN** introduce new dependency

---

## Verification Strategy

### Test Decision

- **Strategy**: `php -l` + `migrate:fresh --seed` + Playwright manual page render dengan login OPD + simulate navigate + verify FilePond state empty + simulate delete per-file

### QA Policy

- File syntax check via `php -l`
- DB integrity via `migrate:fresh --seed`
- Browser UI verification via Playwright (login, navigate, screenshot)

Evidence saved ke `.sisyphus/evidence/plan-d-task-{N}-{slug}.{ext}`.

---

## Execution Strategy

### Parallel Execution Waves

```
Wave 1 (PARALLEL — 3 independent edits):
├── Task 1: Fix navigation reset state + add wire:key (BUG-C)        [unspecified-high]
├── Task 2: Add deleteFileByIndex method + per-file delete UI (BUG-E) [unspecified-high]
└── Task 3: Add Set Halaman + Hapus button to mode kriteria (BUG-A,B) [writing]

Wave 2 (SEQUENTIAL):
└── Task 4: Final verification + commit

Wave FINAL (3 parallel reviews):
├── Task F1: Plan compliance audit (oracle)
├── Task F2: Manual QA via Playwright
└── Task F3: Scope fidelity check (deep)
```

### Dependency Matrix

- **T1**: — Depends: none. Blocks: T4.
- **T2**: — Depends: none. Blocks: T4.
- **T3**: — Depends: none. Blocks: T4.
- **T4**: — Depends: T1, T2, T3. Blocks: F1-F3.
- **F1-F3**: — Depends: T4. Blocks: user okay.

---

## TODOs

- [x] 1. Fix navigation state reset + add wire:key (BUG-C)

  **What to do**:
  - Open `app/Livewire/Dashboard/LembarKerja.php`
  - Add new private method (after `resetPenilaianForm()` at line 286):
    ```php
    /**
     * Reset upload form states + penilaian states saat pindah bukti dukung.
     * Mencakup field FilePond + form upload sehingga UI tidak persist antar bukti.
     */
    private function resetAllFormStatesForNavigation()
    {
        // Reset penilaian fields
        $this->tingkatan_nilai_id = null;
        $this->catatan_penilaian = '';
        $this->is_editing_penilaian = false;
        $this->is_verified = null;
        $this->keterangan_verifikasi = '';
        
        // Reset upload form fields (BUG-C fix)
        $this->file_bukti_dukung = [];
        $this->keterangan_upload = '';
        $this->page_number = null;
        $this->is_perubahan = false;
        $this->ganti_semua_dokumen = false;
        $this->is_final = false;
        $this->file_count = 0;
        $this->temporary_file_names = [];
        $this->file_page_numbers = [];
        $this->is_setting_upload_page = false;
    }
    ```
  - Modify `previousBuktiDukung()` line 261: change `$this->resetPenilaianForm();` to `$this->resetAllFormStatesForNavigation();`
  - Modify `nextBuktiDukung()` line 281: change `$this->resetPenilaianForm();` to `$this->resetAllFormStatesForNavigation();`
  - Open `resources/views/livewire/dashboard/lembar-kerja.blade.php`
  - Find FilePond component at line ~2486:
    ```blade
    <x-filepond::upload wire:model="file_bukti_dukung" multiple />
    ```
  - Change to:
    ```blade
    <x-filepond::upload wire:model="file_bukti_dukung" wire:key="filepond-bukti-{{ $bukti_dukung_id ?? 'none' }}" multiple />
    ```
  - This ensures Livewire recreates FilePond instance when bukti_dukung_id changes

  **Must NOT do**:
  - Don't remove `resetPenilaianForm()` method (still used elsewhere)
  - Don't change behavior of upload action methods
  - Don't edit Monitoring component

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
  - **Skills**: []

  **Acceptance Criteria**:
  - [ ] New method `resetAllFormStatesForNavigation()` exists in LembarKerja.php
  - [ ] `previousBuktiDukung()` calls new method instead of `resetPenilaianForm()`
  - [ ] `nextBuktiDukung()` calls new method instead of `resetPenilaianForm()`
  - [ ] FilePond component has `wire:key="filepond-bukti-{{ $bukti_dukung_id ?? 'none' }}"`
  - [ ] `php -l` passes
  - [ ] `migrate:fresh --seed` passes

- [x] 2. Add deleteFileByIndex method + per-file delete UI (BUG-E)

  **What to do**:
  - Open `app/Livewire/Dashboard/LembarKerja.php`
  - Add new public method (place near `deleteFileBuktiDukung()` method around line 1370):
    ```php
    /**
     * Hapus 1 file dari link_file array by index.
     * Tidak hapus file yang from_esakip=true.
     */
    public function deleteFileByIndex($fileIndex)
    {
        // Cek akses waktu
        $aksesCheck = $this->cekAksesWaktu();
        if (!$aksesCheck['allowed']) {
            flash()->use('theme.ruby')->option('position', 'bottom-right')->error($aksesCheck['message']);
            return;
        }

        // Authorization — hanya admin dan opd
        if (!in_array(Auth::user()->role->jenis, ['admin', 'opd'])) {
            flash()->use('theme.ruby')->option('position', 'bottom-right')->error('Anda tidak memiliki akses untuk menghapus dokumen.');
            return;
        }

        if (!$this->bukti_dukung_id || !$this->opd_session) {
            flash()->use('theme.ruby')->option('position', 'bottom-right')->error('Bukti dukung tidak ditemukan.');
            return;
        }

        $opdRoleId = Role::where('jenis', 'opd')->first()?->id;
        if (!$opdRoleId) {
            flash()->use('theme.ruby')->option('position', 'bottom-right')->error('Role OPD tidak ditemukan.');
            return;
        }

        $penilaian = Penilaian::where('kriteria_komponen_id', $this->kriteria_komponen_session)
            ->where('bukti_dukung_id', $this->bukti_dukung_id)
            ->where('opd_id', $this->opd_session)
            ->where('role_id', $opdRoleId)
            ->first();

        if (!$penilaian || !$penilaian->link_file || !is_array($penilaian->link_file)) {
            flash()->use('theme.ruby')->option('position', 'bottom-right')->error('File tidak ditemukan.');
            return;
        }

        $files = $penilaian->link_file;

        // Validate index
        if (!isset($files[$fileIndex])) {
            flash()->use('theme.ruby')->option('position', 'bottom-right')->error('File tidak ditemukan pada index tersebut.');
            return;
        }

        $fileToDelete = $files[$fileIndex];
        $fileName = $fileToDelete['original_name'] ?? 'Dokumen ' . ($fileIndex + 1);

        try {
            // Hapus file dari Storage (kecuali dari eSAKIP)
            if (isset($fileToDelete['path']) && empty($fileToDelete['from_esakip'])) {
                Storage::disk('public')->delete($fileToDelete['path']);
            }

            // Remove file dari array, re-index
            unset($files[$fileIndex]);
            $files = array_values($files); // Re-index

            // Update record
            $penilaian->update([
                'link_file' => count($files) > 0 ? $files : null,
            ]);

            // Record history
            $penilaian->recordHistory(
                userId: Auth::id(),
                roleId: $opdRoleId,
                opdId: $this->opd_session,
                kriteriaKomponenId: $this->kriteria_komponen_session,
                buktiDukungId: $this->bukti_dukung_id,
                tingkatanNilaiId: $penilaian->tingkatan_nilai_id,
                isVerified: $penilaian->is_verified,
                keterangan: 'Menghapus file dokumen', // EXACT match getActionDescription
                isPerubahan: true
            );

            flash()->use('theme.ruby')->option('position', 'bottom-right')
                ->success("File '{$fileName}' berhasil dihapus.");
        } catch (\Exception $e) {
            \Log::error('Error deleting file by index: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'bukti_dukung_id' => $this->bukti_dukung_id,
                'file_index' => $fileIndex,
                'error' => $e->getMessage(),
            ]);
            flash()->use('theme.ruby')->option('position', 'bottom-right')->error('Gagal menghapus file: ' . $e->getMessage());
        }
    }
    ```

  - Open `resources/views/livewire/dashboard/lembar-kerja.blade.php`
  - Find action buttons in mode bukti (line ~2318-2345 — currently has "Buka di Tab Baru" + "Set Halaman")
  - Add per-file delete button NEXT to those buttons:
    ```blade
    @if (in_array(Auth::user()->role->jenis, ['admin', 'opd']) && $this->dalamRentangAkses)
        <button type="button"
            wire:click="deleteFileByIndex({{ $index }})"
            wire:confirm="Yakin ingin menghapus file ini saja? Tindakan ini tidak dapat dibatalkan."
            class="btn btn-sm btn-outline-danger"
            title="Hapus file ini saja">
            <i class="ri-delete-bin-line"></i>
        </button>
    @endif
    ```
  - Same addition di action buttons section line ~2422-2445 (single file display block)
  - Same addition akan ditambah ke mode kriteria di T3

  **Must NOT do**:
  - Don't remove existing `deleteFileBuktiDukung()` (Hapus Semua tetap ada)
  - Don't change `wire:confirm` text di Hapus Semua button (line 2224 — already accurate)

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
  - **Skills**: []

  **Acceptance Criteria**:
  - [ ] Method `deleteFileByIndex()` exists in LembarKerja.php
  - [ ] Method validates index, deletes from Storage (skip if from_esakip), unset+re-index array
  - [ ] Method calls `recordHistory()` with EXACT keterangan `'Menghapus file dokumen'`
  - [ ] Per-file delete button (`wire:click="deleteFileByIndex(...)"`) appears in mode bukti file tabs
  - [ ] `php -l` passes

- [x] 3. Add Set Halaman + Hapus button to mode kriteria block (BUG-A, BUG-B)

  **What to do**:
  - Open `resources/views/livewire/dashboard/lembar-kerja.blade.php`
  - Find mode kriteria document rendering block (lines ~2086-2168, inside `@foreach ($files as $fileIndex => $file)` per buktiItem)
  - Mode kriteria currently has buttons "Buka di Tab Baru" only (around line 2086-2104). Need to add:
    1. Set Halaman button (mirror line 2328-2345 pattern):
       ```blade
       @if (in_array(Auth::user()->role->jenis, ['admin', 'opd']) && $this->dalamRentangAkses)
           <button type="button"
               wire:click="openSetPageNumberModalForBukti({{ $buktiItem->id }}, {{ $fileIndex }})"
               class="btn btn-sm btn-outline-secondary"
               data-bs-toggle="modal"
               data-bs-target="#modalSetPageNumber">
               <i class="ri-bookmark-line me-1"></i>
               @if (isset($file['page_number']) && $file['page_number'])
                   Hal. {{ $file['page_number'] }}
               @else
                   Set Halaman
               @endif
           </button>
       @endif
       ```
    2. Per-file delete button (mirror T2 addition):
       ```blade
       @if (in_array(Auth::user()->role->jenis, ['admin', 'opd']) && $this->dalamRentangAkses)
           <button type="button"
               wire:click="deleteFileByIndexForBukti({{ $buktiItem->id }}, {{ $fileIndex }})"
               wire:confirm="Yakin ingin menghapus file ini saja?"
               class="btn btn-sm btn-outline-danger"
               title="Hapus file ini">
               <i class="ri-delete-bin-line"></i>
           </button>
       @endif
       ```
    3. Hapus Semua button (per buktiItem):
       ```blade
       @if (in_array(Auth::user()->role->jenis, ['admin', 'opd']) && $this->dalamRentangAkses)
           <button type="button"
               wire:click="deleteAllFilesForBukti({{ $buktiItem->id }})"
               wire:confirm="Yakin ingin menghapus semua dokumen di bukti dukung ini?"
               class="btn btn-sm btn-outline-danger"
               title="Hapus semua dokumen">
               <i class="ri-delete-bin-line me-1"></i>Hapus Semua
           </button>
       @endif
       ```

  - Open `app/Livewire/Dashboard/LembarKerja.php` and ADD 3 new wrapper methods:
    ```php
    /**
     * Wrapper: open page modal untuk bukti spesifik di mode kriteria.
     */
    public function openSetPageNumberModalForBukti($buktiDukungId, $fileIndex)
    {
        $this->bukti_dukung_id = $buktiDukungId;
        $this->openSetPageNumberModal($fileIndex);
    }

    /**
     * Wrapper: delete file by index untuk bukti spesifik di mode kriteria.
     */
    public function deleteFileByIndexForBukti($buktiDukungId, $fileIndex)
    {
        $this->bukti_dukung_id = $buktiDukungId;
        $this->deleteFileByIndex($fileIndex);
    }

    /**
     * Wrapper: hapus semua files untuk bukti spesifik di mode kriteria.
     */
    public function deleteAllFilesForBukti($buktiDukungId)
    {
        $this->bukti_dukung_id = $buktiDukungId;
        $this->deleteFileBuktiDukung();
    }
    ```

  **Why wrappers?**: Mode kriteria iterates `@foreach buktiItem` — each iteration has different `$buktiItem->id`. Existing methods (`openSetPageNumberModal`, `deleteFileBuktiDukung`) rely on `$this->bukti_dukung_id` set in component state. Wrappers set state first, then call existing method.

  **Must NOT do**:
  - Don't duplicate existing methods — use wrappers
  - Don't add buttons in places where they already exist (mode bukti)
  - Don't edit Monitoring component

  **Recommended Agent Profile**:
  - **Category**: `writing`
  - **Skills**: []

  **Acceptance Criteria**:
  - [ ] Mode kriteria block has Set Halaman button per file
  - [ ] Mode kriteria block has per-file delete button
  - [ ] Mode kriteria block has Hapus Semua button per buktiItem
  - [ ] 3 wrapper methods exist in LembarKerja.php
  - [ ] `php -l` passes

- [x] 4. Final verification + commit

  **What to do**:
  - Run `php -l app/Livewire/Dashboard/LembarKerja.php`
  - Run `php artisan migrate:fresh --seed`
  - Grep for new methods to confirm presence
  - Stage files: LembarKerja.php + lembar-kerja.blade.php
  - Commit: `fix(ux): add Set Halaman + Hapus buttons to mode kriteria, fix FilePond state persistence, add per-file delete`

---

## Final Verification Wave

- [x] F1. **Plan Compliance Audit** — `oracle`
  Verify all Must Have/Must NOT Have. Check 4 BUG fixes implemented. Check Monitoring untouched.
  Output: `Must Have [N/N] | Must NOT Have [N/N] | VERDICT`

- [x] F2. **Manual QA via Playwright** — `unspecified-high` (+ playwright skill)
  Login as OPD. Navigate /lembar-kerja, pick kriteria mode bukti, upload file, click Next/Prev — verify FilePond bar empty (BUG-C). Pick kriteria mode kriteria, verify Set Halaman + Hapus buttons visible (BUG-A,B). Upload 3 files, click delete icon on file #2, verify only file #2 removed (BUG-E hybrid).
  Output: `BUG-A [PASS/FAIL] | BUG-B [PASS/FAIL] | BUG-C [PASS/FAIL] | BUG-E [PASS/FAIL] | VERDICT`

- [x] F3. **Scope Fidelity Check** — `deep`
  Verify only 2 files diffed (LembarKerja.php + blade). No Monitoring changes. No format/mimes changes (BUG-D skipped).
  Output: `Files [2/2] | Forbidden [CLEAN] | VERDICT`

---

## Commit Strategy

Single commit after T4:
- **Commit**: `fix(ux): mode kriteria UI parity + FilePond state reset + per-file delete (4 bugs)`
- Files: `app/Livewire/Dashboard/LembarKerja.php`, `resources/views/livewire/dashboard/lembar-kerja.blade.php`
- Pre-commit: `php -l` + `migrate:fresh --seed` exit 0
