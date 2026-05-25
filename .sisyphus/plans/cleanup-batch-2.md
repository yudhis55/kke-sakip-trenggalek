# Plan C — Cleanup Batch 2

## TL;DR

> **Quick Summary**: Drop 5 deprecated tables (FileBuktiDukung tier + 4 deprecated penilaian tables), hapus 5 model PHP deprecated, bersihkan zombie relations di 9 model lain, hapus 3 file root yang tidak dipakai (welcome.blade.php, IMPLEMENTATION_STATUS.md, API_DOCUMENTATION.md).
>
> **Deliverables**:
> - 6 migration files baru (1 per tabel/kolom drop, urutan dependency-safe)
> - 5 model PHP dihapus: FileBuktiDukung, PenilaianMandiri, PenilaianVerifikator, PenilaianPenjamin, PenilaianPenilai
> - 9 model dibersihkan dari zombie relations
> - 3 file root dihapus: welcome.blade.php, IMPLEMENTATION_STATUS.md, API_DOCUMENTATION.md
> - `php artisan migrate:fresh --seed` tetap jalan
>
> **Estimated Effort**: Medium-High (migration ordering kritis)
> **Parallel Execution**: PARTIAL — file deletions paralel, migrations sequential
> **Critical Path**: Migration drop order → model cleanup → file deletions → verify

---

## Context

### Original Request

User minta Cleanup Batch 2 dari DEAD_CODE.md recommendations:
- Drop FileBuktiDukung tier (model + tabel + FK + relasi sisa)
- Drop 4 model deprecated (PenilaianMandiri/Verifikator/Penjamin/Penilai) + 4 tabel terkait
- Hapus blok komentar di routes/web.php (item #8) — SKIP (trivial, bisa nanti)
- Hapus welcome.blade.php view (item #9)
- Hapus IMPLEMENTATION_STATUS.md dan API_DOCUMENTATION.md (item #10)

### Investigation Findings (CONFIRMED)

**FK Constraints yang masih aktif (harus di-drop dulu):**
1. `penilaian_history.file_perbaikan_id` → FK ke `file_bukti_dukung` (migration `2026_02_01_114223`)
2. `penilaian_verifikator.file_bukti_dukung_id` → FK ke `file_bukti_dukung` (migration `2025_12_01_033157`)

**Urutan drop yang aman (dependency order):**
```
Step 1: DROP FK penilaian_history.file_perbaikan_id + kolom (tanggal_perbaikan, status_perbaikan juga)
Step 2: DROP table penilaian_verifikator (punya FK ke file_bukti_dukung)
Step 3: DROP table file_bukti_dukung (setelah semua FK ke sini hilang)
Step 4: DROP table penilaian_mandiri
Step 5: DROP table penilaian_penjamin
Step 6: DROP table penilaian_penilai
```

**Zombie relations di models (tidak ada call site aktif):**
- `Opd.php`: `file_bukti_dukung()`, `penilaian_mandiri()`, `penilaian_verifikator()`
- `Role.php`: `penilaian_verifikator()`
- `KriteriaKomponen.php`: `penilaian_mandiri()`
- `TingkatanNilai.php`: `penilaian_mandiri()`
- `PenilaianHistory.php`: `file_perbaikan()` relation
- `PenilaianMandiri.php`: `penilaian_verifikator()` (model akan dihapus)
- `BuktiDukung.php`: commented-out `file_bukti_dukung()` (sudah komentar, hapus komentar)
- `Penilaian.php`: commented-out `file_bukti_dukung()` (sudah komentar, hapus komentar)

**Files to delete:**
- `app/Models/FileBuktiDukung.php`
- `app/Models/PenilaianMandiri.php`
- `app/Models/PenilaianVerifikator.php`
- `app/Models/PenilaianPenjamin.php`
- `app/Models/PenilaianPenilai.php`
- `resources/views/welcome.blade.php`
- `IMPLEMENTATION_STATUS.md`
- `API_DOCUMENTATION.md`

---

## Work Objectives

### Core Objective

Bersihkan codebase dari 5 deprecated model + 5 deprecated tabel + zombie relations + 3 file root yang tidak dipakai. Setelah cleanup, `migrate:fresh --seed` harus tetap jalan dan tidak ada reference ke class yang dihapus.

### Concrete Deliverables

**6 Migration files baru** (1 per drop operation):
1. `drop_fk_file_perbaikan_from_penilaian_history_table.php` — drop FK + kolom file_perbaikan_id, tanggal_perbaikan, status_perbaikan dari penilaian_history
2. `drop_penilaian_verifikators_table.php`
3. `drop_file_bukti_dukungs_table.php`
4. `drop_penilaian_mandiris_table.php`
5. `drop_penilaian_penjamins_table.php`
6. `drop_penilaian_penilais_table.php`

**Model deletions (8 files)**:
- `app/Models/FileBuktiDukung.php` — DELETE
- `app/Models/PenilaianMandiri.php` — DELETE
- `app/Models/PenilaianVerifikator.php` — DELETE
- `app/Models/PenilaianPenjamin.php` — DELETE
- `app/Models/PenilaianPenilai.php` — DELETE

**Model cleanups (zombie relation removal)**:
- `app/Models/Opd.php` — remove 3 zombie relations
- `app/Models/Role.php` — remove 1 zombie relation
- `app/Models/KriteriaKomponen.php` — remove 1 zombie relation + use statement
- `app/Models/TingkatanNilai.php` — remove 1 zombie relation + use statement
- `app/Models/PenilaianHistory.php` — remove `file_perbaikan()` relation + use statement

**File deletions (3 files)**:
- `resources/views/welcome.blade.php`
- `IMPLEMENTATION_STATUS.md`
- `API_DOCUMENTATION.md`

### Definition of Done

- [ ] `php artisan migrate:fresh --seed` exits 0
- [ ] `php artisan migrate` (incremental) exits 0 on fresh DB
- [ ] `composer dump-autoload` exits 0 (no missing class references)
- [ ] `grep -r "FileBuktiDukung\|PenilaianMandiri\|PenilaianVerifikator\|PenilaianPenjamin\|PenilaianPenilai" app/` returns 0 active references (only in migration files is OK)
- [ ] Tables dropped: `file_bukti_dukung`, `penilaian_mandiri`, `penilaian_verifikator`, `penilaian_penjamin`, `penilaian_penilai`
- [ ] Columns dropped from `penilaian_history`: `file_perbaikan_id`, `tanggal_perbaikan`, `status_perbaikan`
- [ ] `Test-Path "resources/views/welcome.blade.php"` returns False
- [ ] `Test-Path "IMPLEMENTATION_STATUS.md"` returns False
- [ ] `Test-Path "API_DOCUMENTATION.md"` returns False
- [ ] `Test-Path "app/Models/FileBuktiDukung.php"` returns False
- [ ] All 5 deprecated model files deleted

### Must Have

- Migration drop order MUST follow dependency chain (FK constraints first)
- Each migration has proper `down()` method
- `migrate:fresh --seed` passes after all migrations applied
- No active PHP code references deleted classes after cleanup

### Must NOT Have (Guardrails)

- **JANGAN** drop tabel `penilaian` (unified table — AKTIF dipakai)
- **JANGAN** drop tabel `penilaian_history` (AKTIF dipakai — hanya drop 3 kolom)
- **JANGAN** edit `routes/web.php` (item #8 SKIP)
- **JANGAN** edit `app/Livewire/` components
- **JANGAN** edit `app/Services/EsakipSyncService.php`
- **JANGAN** edit AGENTS.md files
- **JANGAN** drop kolom `status_perbaikan` dari `penilaian_history` jika masih dipakai di kode — VERIFY dulu
- **JANGAN** hapus migration files yang sudah ada (hanya tambah migration baru)
- **JANGAN** introduce new dependency

---

## Verification Strategy

### Test Decision

- **Strategy**: `migrate:fresh --seed` + `composer dump-autoload` + grep for deleted class references

### QA Policy

- `php artisan migrate:fresh --seed` MUST pass after each migration wave
- `composer dump-autoload` MUST pass after model deletions
- Grep for deleted class names MUST return 0 active references

---

## Execution Strategy

### Parallel Execution Waves

```
Wave 1 (SEQUENTIAL — migrations must run in order):
├── Task 1: Create 6 migration files (drop FK + 5 tables)
└── Task 2: Verify migrate:fresh --seed passes

Wave 2 (PARALLEL — independent file operations):
├── Task 3: Delete 5 deprecated model PHP files
├── Task 4: Clean zombie relations from 9 models
└── Task 5: Delete 3 root files (welcome.blade.php, IMPLEMENTATION_STATUS.md, API_DOCUMENTATION.md)

Wave 3 (SEQUENTIAL — verify everything):
└── Task 6: Final verification (migrate:fresh, composer dump-autoload, grep checks)

Wave FINAL (3 parallel reviews):
├── Task F1: Plan compliance audit (oracle)
├── Task F2: Code integrity check (unspecified-high)
└── Task F3: Scope fidelity check (deep)
```

### Dependency Matrix

- **T1**: — Depends: none. Blocks: T2, T3, T4.
- **T2**: — Depends: T1. Blocks: T3, T4, T5.
- **T3**: — Depends: T2. Blocks: T6.
- **T4**: — Depends: T2. Blocks: T6.
- **T5**: — Depends: none. Blocks: T6.
- **T6**: — Depends: T3, T4, T5. Blocks: F1-F3.
- **F1-F3**: — Depends: T6. Blocks: user okay.

---

## TODOs

- [x] 1. Create 6 migration files (drop FK + 5 deprecated tables)

  **What to do**:
  Create 6 migration files in `database/migrations/` with timestamps in correct order. Use `php artisan make:migration` or create manually. Each migration drops ONE table/set of columns.

  **Migration 1**: `2026_05_25_000001_drop_fk_file_perbaikan_from_penilaian_history_table.php`
  ```php
  public function up(): void
  {
      Schema::table('penilaian_history', function (Blueprint $table) {
          $table->dropForeign(['file_perbaikan_id']);
          $table->dropColumn(['file_perbaikan_id', 'tanggal_perbaikan', 'status_perbaikan']);
      });
  }
  public function down(): void
  {
      Schema::table('penilaian_history', function (Blueprint $table) {
          $table->enum('status_perbaikan', ['belum_diperbaiki', 'sudah_diperbaiki', 'diterima_setelah_perbaikan'])
              ->default('belum_diperbaiki')->after('keterangan');
          $table->timestamp('tanggal_perbaikan')->nullable()->after('status_perbaikan');
          $table->unsignedBigInteger('file_perbaikan_id')->nullable()->after('tanggal_perbaikan');
          $table->foreign('file_perbaikan_id')->references('id')->on('file_bukti_dukung')->onDelete('set null');
      });
  }
  ```

  **CRITICAL**: Sebelum drop `status_perbaikan`, VERIFY apakah kolom ini masih dipakai di kode aktif:
  - `grep -r "status_perbaikan" app/` — jika ada hasil di Livewire/Models yang AKTIF, JANGAN drop kolom ini
  - Dari investigasi sebelumnya: `RekapPenolakan.php` dan `RekapPerbaikan.php` menggunakan `status_perbaikan` di query! Jadi **JANGAN drop `status_perbaikan`** — hanya drop `file_perbaikan_id` dan `tanggal_perbaikan`.

  **Migration 1 FINAL** (hanya drop `file_perbaikan_id` — `status_perbaikan` dan `tanggal_perbaikan` TETAP karena masih aktif dipakai di LembarKerja.php, RekapPenolakan.php, RekapPerbaikan.php):
  ```php
  public function up(): void
  {
      Schema::table('penilaian_history', function (Blueprint $table) {
          $table->dropForeign(['file_perbaikan_id']);
          $table->dropColumn('file_perbaikan_id');
      });
  }
  public function down(): void
  {
      Schema::table('penilaian_history', function (Blueprint $table) {
          $table->unsignedBigInteger('file_perbaikan_id')->nullable()->after('tanggal_perbaikan');
          $table->foreign('file_perbaikan_id')->references('id')->on('file_bukti_dukung')->onDelete('set null');
      });
  }
  ```

  **Migration 2**: `2026_05_25_000002_drop_penilaian_verifikators_table.php`
  ```php
  public function up(): void { Schema::dropIfExists('penilaian_verifikator'); }
  public function down(): void { /* recreate if needed */ }
  ```

  **Migration 3**: `2026_05_25_000003_drop_file_bukti_dukungs_table.php`
  ```php
  public function up(): void { Schema::dropIfExists('file_bukti_dukung'); }
  public function down(): void { /* recreate if needed */ }
  ```

  **Migration 4**: `2026_05_25_000004_drop_penilaian_mandiris_table.php`
  ```php
  public function up(): void { Schema::dropIfExists('penilaian_mandiri'); }
  public function down(): void { /* recreate if needed */ }
  ```

  **Migration 5**: `2026_05_25_000005_drop_penilaian_penjamins_table.php`
  ```php
  public function up(): void { Schema::dropIfExists('penilaian_penjamin'); }
  public function down(): void { /* recreate if needed */ }
  ```

  **Migration 6**: `2026_05_25_000006_drop_penilaian_penilais_table.php`
  ```php
  public function up(): void { Schema::dropIfExists('penilaian_penilai'); }
  public function down(): void { /* recreate if needed */ }
  ```

  **IMPORTANT**: Verify `status_perbaikan` usage BEFORE creating Migration 1:
  ```powershell
  Select-String -Path "app/Livewire/Dashboard/RekapPenolakan.php","app/Livewire/Dashboard/RekapPerbaikan.php" -Pattern "status_perbaikan"
  ```
  If found → DO NOT drop `status_perbaikan` column.

  **Acceptance Criteria**:
  - [ ] 6 migration files created in `database/migrations/`
  - [ ] `php artisan migrate:fresh --seed` exits 0
  - [ ] `php artisan migrate` (on fresh DB) exits 0
  - [ ] Tables no longer exist: `file_bukti_dukung`, `penilaian_mandiri`, `penilaian_verifikator`, `penilaian_penjamin`, `penilaian_penilai`
  - [ ] `penilaian_history` still exists with `status_perbaikan` column intact
  - [ ] `penilaian_history.file_perbaikan_id` column GONE

  **Commit**: YES after T2 verification passes
  - Message: `chore(db): drop deprecated tables (FileBuktiDukung tier + 4 penilaian role tables)`

- [x] 2. Verify migrate:fresh --seed passes after migrations

  **What to do**:
  - Run `php artisan migrate:fresh --seed`
  - Verify exit 0
  - Verify tables dropped: `file_bukti_dukung`, `penilaian_mandiri`, `penilaian_verifikator`, `penilaian_penjamin`, `penilaian_penilai`
  - Verify `penilaian_history` still has `status_perbaikan` column
  - Save evidence

  **Acceptance Criteria**:
  - [ ] `php artisan migrate:fresh --seed` exits 0
  - [ ] All 5 deprecated tables absent from DB
  - [ ] `penilaian_history` intact with `status_perbaikan`

  **Commit**: NO (part of T1 commit)

- [x] 3. Delete 5 deprecated model PHP files

  **What to do**:
  Delete these 5 files:
  - `app/Models/FileBuktiDukung.php`
  - `app/Models/PenilaianMandiri.php`
  - `app/Models/PenilaianVerifikator.php`
  - `app/Models/PenilaianPenjamin.php`
  - `app/Models/PenilaianPenilai.php`

  Then run `composer dump-autoload` to verify no autoload errors.

  **Acceptance Criteria**:
  - [ ] All 5 files deleted
  - [ ] `composer dump-autoload` exits 0

  **Commit**: NO (part of Wave 2 commit)

- [x] 4. Clean zombie relations from 9 models

  **What to do**:
  Remove zombie relations from these models (relations that reference deleted classes):

  **`app/Models/Opd.php`**: Remove 3 methods:
  - `file_bukti_dukung()` (hasMany FileBuktiDukung)
  - `penilaian_mandiri()` (hasMany PenilaianMandiri)
  - `penilaian_verifikator()` (hasMany PenilaianVerifikator)
  - Remove corresponding `use` statements

  **`app/Models/Role.php`**: Remove 1 method:
  - `penilaian_verifikator()` (hasMany PenilaianVerifikator)
  - Remove corresponding `use` statement

  **`app/Models/KriteriaKomponen.php`**: Remove 1 method:
  - `penilaian_mandiri()` (hasMany PenilaianMandiri)
  - Remove corresponding `use` statement

  **`app/Models/TingkatanNilai.php`**: Remove 1 method:
  - `penilaian_mandiri()` (hasMany PenilaianMandiri)
  - Remove corresponding `use` statement

  **`app/Models/PenilaianHistory.php`**: Remove 1 method:
  - `file_perbaikan()` (belongsTo FileBuktiDukung)
  - Remove corresponding `use` statement

  **`app/Models/BuktiDukung.php`**: Remove commented-out block:
  - Lines ~51-55: `// Deprecated: File storage now in penilaian table` + commented relation

  **`app/Models/Penilaian.php`**: Remove commented-out block:
  - Lines ~25-29: `// Relasi ke FileBuktiDukung sudah tidak digunakan` + commented relation

  **Acceptance Criteria**:
  - [ ] All 7 zombie relations removed
  - [ ] All corresponding `use` statements removed
  - [ ] `php -l` passes for all 7 modified files
  - [ ] `composer dump-autoload` exits 0

  **Commit**: NO (part of Wave 2 commit)

- [x] 5. Delete 3 root files

  **What to do**:
  Delete:
  - `resources/views/welcome.blade.php`
  - `IMPLEMENTATION_STATUS.md`
  - `API_DOCUMENTATION.md`

  Verify no active references to `welcome.blade.php` in routes or views.

  **Acceptance Criteria**:
  - [ ] All 3 files deleted
  - [ ] `Test-Path "resources/views/welcome.blade.php"` returns False
  - [ ] `Test-Path "IMPLEMENTATION_STATUS.md"` returns False
  - [ ] `Test-Path "API_DOCUMENTATION.md"` returns False

  **Commit**: NO (part of Wave 2 commit)

- [x] 6. Final verification

  **What to do**:
  - `php artisan migrate:fresh --seed` exits 0
  - `composer dump-autoload` exits 0
  - Grep for deleted class names returns 0 active references in `app/` (excluding migration files)
  - All 5 model files deleted
  - All 3 root files deleted
  - `penilaian_history.status_perbaikan` still exists (NOT dropped)

  **Acceptance Criteria**:
  - [ ] All checks pass
  - [ ] Commit Wave 2: `chore: remove deprecated model files, clean zombie relations, delete unused root files`

---

## Final Verification Wave (MANDATORY)

- [x] F1. **Plan Compliance Audit** — `oracle`
  Verify Must Have/Must NOT Have. Check all 5 model files deleted. Check 6 migrations created. Check `status_perbaikan` NOT dropped. Check `penilaian` table intact.
  Output: `Must Have [N/N] | Must NOT Have [N/N] | VERDICT`

- [x] F2. **Code Integrity Check** — `unspecified-high`
  Run `php artisan migrate:fresh --seed`. Run `composer dump-autoload`. Grep for deleted class names in `app/` (excluding migrations). Verify no broken references.
  Output: `Migrate [PASS/FAIL] | Autoload [PASS/FAIL] | Refs [CLEAN/N issues] | VERDICT`

- [x] F3. **Scope Fidelity Check** — `deep`
  Verify only expected files changed. No Livewire components edited. No EsakipSyncService edited. No AGENTS.md edited. `penilaian` table untouched.
  Output: `Files [correct] | Forbidden [CLEAN] | VERDICT`

---

## Commit Strategy

- **Commit 1** (after T1+T2): `chore(db): drop deprecated tables (FileBuktiDukung tier + 4 penilaian role tables)`
  - Files: 6 new migration files
  - Pre-commit: `php artisan migrate:fresh --seed` exit 0

- **Commit 2** (after T3+T4+T5+T6): `chore: remove deprecated model files, clean zombie relations, delete unused root files`
  - Files: 5 model deletions, 7 model edits, 3 file deletions
  - Pre-commit: `composer dump-autoload` exit 0 + grep returns 0 active refs
