# Cleanup Batch 1 + Sync Documentation Rewrite

## TL;DR

> **Quick Summary**: Bersihkan dead code low-risk (4 item) + rewrite/update 5 dokumentasi sinkronisasi yang OUTDATED (tidak menyebut fitur baru: `is_n_minus_1`, `predecessor_opd_id`, `tahun_mulai_berlaku`) + tulis analisis alur sinkronisasi saat ini termasuk gap & rekomendasi perbaikan.
>
> **Deliverables**:
> - 14 scratch script PHP dipindah ke `tmp/` (gitignored)
> - File `database/seeders/JenisNilai.php` (orphan, BUKAN `JenisNilaiSeeder`) terhapus
> - File `app/Livewire/Dashboard/SinkronDokumen.php` + view-nya terhapus
> - 5 dokumen sync di-rewrite/update agar menyebut SinkronData (bukan SinkronDokumen), buang `sync_mode`, tambah dokumentasi 3 fitur baru
> - Dokumen baru `.sisyphus/docs/SYNC_FLOW_ANALYSIS.md` berisi analisa alur + 15+ issues yang ditemukan
>
> **Estimated Effort**: Medium (low resiko, pekerjaan repetitif + dokumentasi tebal)
> **Parallel Execution**: YES — 3 wave (5 task wave 1 paralel, 6 task wave 2 paralel, 1 task wave 3, lalu final review)
> **Critical Path**: Wave 1 setup `tmp/` + delete files → Wave 2 update docs (paralel) → Wave 3 analysis → Final verification

---

## Context

### Original Request

User minta cleanup dead code yang sudah didokumentasikan di `.sisyphus/docs/DEAD_CODE.md`, ITEM #1-4 saja:
1. Hapus 14 root-level scratch PHP scripts (pindah ke `tmp/`)
2. Hapus `database/seeders/JenisNilai.php` (orphan, bukan `JenisNilaiSeeder`)
3. Hapus `app/Livewire/Dashboard/SinkronDokumen.php` + view
4. Update `PANDUAN_SINKRONISASI_ESAKIP.md` agar konsisten dengan `SinkronData` saja

Setelah itu user juga minta:
- **Periksa & update dokumentasi sinkronisasi** karena dokumen lama belum mencakup fitur baru: OPD baru (`tahun_mulai_berlaku`, `predecessor_opd_id`), dokumen N-1 (`is_n_minus_1`).
- **Analisa alur sinkronisasi saat ini**: apakah ada yang kurang pas/kurang tepat untuk diimplementasikan.

### Interview Summary

**Decisions confirmed**:
- Scope: hanya item #1-4 dari list 10 cleanup recommendations (bukan #6-10).
- Format migration: 1 migration per tabel (untuk batch berikutnya bila dropping tabel deprecated).
- Tidak ada DB migration di batch ini — pure file operations + dokumentasi.

**Research findings (verified by exhaustive grep + ast-grep)**:
- 14 scratch PHP scripts di root: `test-*.php`, `validate-*.php`, `check-*.php`, `debug_*.php`, `syncPenilaian_NEW_TEMPLATE.php`. Tidak ada yang autoload, dipanggil manual saja.
- `database/seeders/JenisNilai.php` adalah class `JenisNilai extends Seeder` dengan body kosong. Tidak ada referensi (DatabaseSeeder pakai `JenisNilaiSeeder.php` yang benar).
- `SinkronDokumen.php` (Livewire component) + `sinkron-dokumen.blade.php` (view) tidak terdaftar di `routes/web.php`. Sidebar tidak punya link. Tidak ada referensi dari komponen lain. Field `sync_mode` (merge/replace/skip) yang ada di komponen ini tidak digunakan oleh `SinkronData` (yang aktif dipakai).
- File `validate-sync.php:127` masih reference `$lastSync->sync_mode` (kolom yang tidak ada di tabel `riwayat_sinkron`). Itu bug di scratch script — tidak relevan, file akan dipindah ke `tmp/` anyway.
- 3 fitur baru sinkronisasi (`is_n_minus_1`, `predecessor_opd_id`, `tahun_mulai_berlaku`) **tidak disebut di SATU PUN** dari 5 dokumen sync. Verified via grep di seluruh `*.md` root. Hanya `OpdSeeder.php`, `EsakipSyncService.php`, `Mapping.blade.php`, `Pengaturan.blade.php` yang implement. Dokumentasi tertinggal jauh.

### Metis Review

**Identified Gaps (addressed)**:
- `tmp/` perlu di-`.gitignore` agar tidak commit scratch scripts. Akan ditambah.
- View `sinkron-dokumen.blade.php` — perlu pastikan tidak ada `@include`/`x-` reference ke ini dari blade lain. Verified: tidak ada.
- Apakah sync doc rewrite sebaiknya di file BARU (e.g. `SYNC_GUIDE.md`) atau update file existing? **Decision**: update existing files supaya tidak tambah file baru di root. Konsolidasi konten dilakukan dalam pekerjaan rewrite.
- `IMPLEMENTATION_STATUS.md` di-mention ulang sebagai "outdated" tapi tidak masuk batch ini (item #10) — biarkan untuk batch berikutnya.
- `tmp/.gitkeep` perlu agar dir tetap ada di git (dengan dirinya sendiri di-ignore).

---

## Work Objectives

### Core Objective

Buat root project lebih bersih (hapus 17 file dead code/scratch dari root + 1 dari seeders + 1 dari Livewire), DAN update 5 dokumentasi sinkronisasi supaya AKURAT dan UP-TO-DATE dengan implementasi saat ini.

### Concrete Deliverables

**File operations**:
- `tmp/` directory baru (gitignored, dengan `.gitkeep`)
- 14 file PHP root → pindah ke `tmp/`
- `database/seeders/JenisNilai.php` → terhapus
- `app/Livewire/Dashboard/SinkronDokumen.php` → terhapus
- `resources/views/livewire/dashboard/sinkron-dokumen.blade.php` → terhapus
- `.gitignore` → tambah baris `/tmp/*` dan `!/tmp/.gitkeep`

**Documentation updates** (5 file, di-rewrite atau update):
- `PANDUAN_SINKRONISASI_ESAKIP.md` — full rewrite. Buang reference ke SinkronDokumen + sync_mode. Tambah section: OPD reorganisasi (predecessor + tahun_mulai_berlaku) + Dokumen N-1.
- `DATABASE_SINKRONISASI.md` — tambah dokumentasi 3 kolom baru (`bukti_dukung.is_n_minus_1`, `opd.tahun_mulai_berlaku`, `opd.predecessor_opd_id`). Update tabel `riwayat_sinkron` reference jika perlu.
- `SMART_SYNC_STRATEGY.md` — buang reference `$syncMode` (parameter sudah tidak ada di syncPenilaian). Tegaskan: smart-merge sekarang DEFAULT (tidak ada mode pilihan).
- `STRUKTUR_LINK_FILE.md` — verifikasi accurate dengan implementasi sekarang. Tambah catatan kalau ada yang belum cover.
- `TROUBLESHOOTING_SINKRONISASI.md` — tambah scenario troubleshooting OPD baru (predecessor lookup) + N-1 documents.

**Analysis document baru**:
- `.sisyphus/docs/SYNC_FLOW_ANALYSIS.md` — peta lengkap alur sinkronisasi saat ini + 15+ issues yang ditemukan + rekomendasi perbaikan dengan severity.

### Definition of Done

- [ ] `Get-ChildItem -File -Filter *.php -LiteralPath "."` di project root return ZERO scratch script (hanya `artisan`).
- [ ] `Test-Path "tmp/.gitkeep"` returns true.
- [ ] `git status` show `tmp/<14 files>` are untracked AND ignored (verify dengan `git check-ignore tmp/test-sync.php`).
- [ ] `Test-Path "database/seeders/JenisNilai.php"` returns false.
- [ ] `Test-Path "database/seeders/JenisNilaiSeeder.php"` returns true (yang benar tetap ada).
- [ ] `Test-Path "app/Livewire/Dashboard/SinkronDokumen.php"` returns false.
- [ ] `Test-Path "resources/views/livewire/dashboard/sinkron-dokumen.blade.php"` returns false.
- [ ] `php artisan migrate:fresh --seed` berjalan tanpa error (memastikan delete `JenisNilai.php` tidak break, dan delete SinkronDokumen tidak break composer autoload).
- [ ] `php artisan route:list` menampilkan SinkronData route, BUKAN SinkronDokumen.
- [ ] `composer dev` start tanpa error.
- [ ] Login sebagai admin, buka `/sinkron-data` — page render normal, preview sync masih jalan.
- [ ] `grep -r "SinkronDokumen" app/ routes/ resources/views/` returns zero results (di luar `*.md` di root, yang akan di-update).
- [ ] `grep -r "sync_mode" app/ resources/views/` returns zero results.
- [ ] 5 dokumen sync di-update — tidak ada lagi mention `SinkronDokumen` atau `sync_mode` (kecuali di sejarah/audit). Tiga fitur baru (`is_n_minus_1`, `predecessor_opd_id`, `tahun_mulai_berlaku`) tercatat di setidaknya 2 dari 5 dokumen.
- [ ] File baru `.sisyphus/docs/SYNC_FLOW_ANALYSIS.md` ada, ≥300 baris, mengandung section: Method Map, Feature Handling (3 fitur), Issues Found (≥10), Recommendations (≥5).

### Must Have

- File operasi REVERSIBLE (pindah ke `tmp/`, bukan delete permanent untuk scratch scripts).
- Verifikasi no broken reference setelah delete SinkronDokumen + JenisNilai.
- Dokumentasi sync HANYA mereferensi feature yang BENAR-BENAR aktif di codebase (cross-check ke `EsakipSyncService.php`).
- Analisis flow ditulis dengan basis kode aktual (file:line citation).

### Must NOT Have (Guardrails)

- **JANGAN drop tabel database**. Item #6-7 (FileBuktiDukung tier + 4 deprecated penilaian models) **DI-SKIP** untuk batch ini.
- **JANGAN edit migration files**. Tidak ada DB schema change di batch ini.
- **JANGAN hapus blok komentar di `routes/web.php`** (item #8). Skip untuk batch berikutnya.
- **JANGAN hapus `welcome.blade.php`** (item #9). Skip.
- **JANGAN hapus `IMPLEMENTATION_STATUS.md` atau `API_DOCUMENTATION.md`** (item #10). Skip.
- **JANGAN edit source PHP code** (`app/Services/EsakipSyncService.php`, `app/Livewire/*`). Tujuan batch ini hanya cleanup file + dokumentasi. Bug yang ditemukan di analisis ditulis sebagai REKOMENDASI di `.sisyphus/docs/SYNC_FLOW_ANALYSIS.md`, bukan diperbaiki sekarang.
- **JANGAN ubah `EsakipSyncService.php` flow**. Termasuk perbaikan bug yang ditemukan — itu pekerjaan terpisah.
- **JANGAN edit root `AGENTS.md` atau `app/*/AGENTS.md`** subdir. Sudah finalized di session sebelumnya. Akan ada follow-up batch terpisah untuk update stale references setelah file di-cleanup.
- **JANGAN edit `.sisyphus/docs/ROLES.md`, `FLOWS.md`, `KNOWN_BUGS.md`, `DEAD_CODE.md`** atau `.sisyphus/docs/README.md` existing. Hanya tambah file BARU `SYNC_FLOW_ANALYSIS.md`.
- **JANGAN add new dependency** (composer/npm).
- **JANGAN buat CI workflow baru** (no `.github/workflows/`).
- **JANGAN gunakan `git add -f` atau `--force`** untuk track file di `tmp/`. Files HARUS untracked (gitignored). Commit 1 di git's perspective adalah 14 DELETIONS dari root + 1 `tmp/.gitkeep` add + `.gitignore` modify. Bukan "move".
- **JANGAN buat issue di `SYNC_FLOW_ANALYSIS.md` yang TIDAK terverifikasi** dari kode aktual. Pre-listed 15 issues di T11 adalah PANDUAN, BUKAN target wajib. Agent boleh skip issue yang setelah inspeksi ternyata tidak valid (e.g. sudah ditangani dengan benar). Minimum 10 valid issue, MAX tidak terbatas tapi setiap satu HARUS punya citation file:line.
- **JANGAN duplikat issue dari `KNOWN_BUGS.md`** (BUG-001 sampai BUG-015). Cross-reference ke file itu jika perlu, jangan tulis ulang.

---

## Verification Strategy

> **ZERO HUMAN INTERVENTION** — semua verifikasi agent-executable.

### Test Decision

- **Infrastructure exists**: YES (PHPUnit configured, but only stub tests).
- **Automated tests**: NO (project tidak punya test suite real, dan batch ini pure file ops + docs — no logic to unit-test).
- **Framework**: PHPUnit (untuk `composer test` smoke check).
- **Strategy**: rely on **migrate:fresh --seed** + **route:list** + **manual page load via Playwright** sebagai integration check. No new tests added.

### QA Policy

Setiap task wajib include QA Scenarios — agent verifies file existence/non-existence, runs commands, captures screenshot of `/sinkron-data` page (hanya untuk task delete SinkronDokumen).

- **File ops**: `Test-Path` PowerShell + `git check-ignore`.
- **Database**: `php artisan migrate:fresh --seed` + verify exit code 0.
- **Route**: `php artisan route:list` + grep for `sinkron-data` & `sinkron-dokumen`.
- **UI verification**: Playwright open `/sinkron-data` setelah hapus SinkronDokumen, capture screenshot, assert no console error.
- **Doc verification**: grep untuk string yang harus ada (e.g. `is_n_minus_1`) dan yang harus tidak ada (e.g. `SinkronDokumen`).

Evidence saved ke `.sisyphus/evidence/task-{N}-{scenario-slug}.{ext}`.

---

## Execution Strategy

### Parallel Execution Waves

```
Wave 1 (Foundation - file operations, MAX PARALLEL):
├── Task 1: Setup tmp/ directory + .gitignore        [quick]
├── Task 2: Move 14 scratch PHP scripts to tmp/      [quick]   (depends: 1)
├── Task 3: Delete database/seeders/JenisNilai.php   [quick]
├── Task 4: Delete SinkronDokumen.php component      [quick]
└── Task 5: Delete sinkron-dokumen.blade.php view    [quick]

Wave 2 (Documentation rewrites, MAX PARALLEL — independent files):
├── Task 6:  Rewrite PANDUAN_SINKRONISASI_ESAKIP.md          [writing]
├── Task 7:  Update DATABASE_SINKRONISASI.md                 [writing]
├── Task 8:  Update SMART_SYNC_STRATEGY.md                   [writing]
├── Task 9:  Update STRUKTUR_LINK_FILE.md                    [writing]
├── Task 10: Update TROUBLESHOOTING_SINKRONISASI.md          [writing]
└── Task 11: Write SYNC_FLOW_ANALYSIS.md                     [deep]

Wave 3 (Cross-validate documentation - SEQUENTIAL, depends Wave 2):
└── Task 12: Cross-validate 5 sync docs konsisten            [unspecified-high]

Wave FINAL (4 parallel reviews, then user okay):
├── Task F1: Plan compliance audit (oracle)
├── Task F2: Code quality + integrity review (unspecified-high)
├── Task F3: Real manual QA via Playwright + curl (unspecified-high)
└── Task F4: Scope fidelity check (deep)
-> Present results -> Get explicit user okay

Critical Path: T1 → T2 → T6-T11 (parallel) → T12 → F1-F4 → user okay
Parallel Speedup: ~60% faster than sequential
Max Concurrent: 6 (Wave 2)
```

### Dependency Matrix

- **T1**: — — Blocks: T2, T12. Depends: none.
- **T2**: — Depends: T1. Blocks: T12.
- **T3**: — Depends: none. Blocks: T12.
- **T4**: — Depends: none. Blocks: T5, T12.
- **T5**: — Depends: T4. Blocks: T12.
- **T6**: — Depends: T4 (must reference SinkronData not SinkronDokumen). Blocks: T12.
- **T7**: — Depends: none. Blocks: T12.
- **T8**: — Depends: none. Blocks: T12.
- **T9**: — Depends: none. Blocks: T12.
- **T10**: — Depends: none. Blocks: T12.
- **T11**: — Depends: none. Blocks: T12.
- **T12**: — Depends: T1-T11. Blocks: F1-F4.
- **F1-F4**: — Depends: T12. Blocks: user okay.

### Agent Dispatch Summary

- **Wave 1**: **5 tasks** — T1-T5 → `quick`
- **Wave 2**: **6 tasks** — T6-T10 → `writing`, T11 → `deep`
- **Wave 3**: **1 task** — T12 → `unspecified-high`
- **FINAL**: **4 tasks** — F1 → `oracle`, F2 → `unspecified-high`, F3 → `unspecified-high`, F4 → `deep`

---

## TODOs

---

## TODOs

- [ ] 1. Setup `tmp/` directory + update `.gitignore`

  **What to do**:
  - Create new directory: `tmp/` di project root.
  - Buat file `tmp/.gitkeep` (empty file) supaya dir ke-track git walaupun isinya di-ignore.
  - Update `.gitignore`: tambahkan baris `/tmp/*` dan `!/tmp/.gitkeep` di akhir file.
  - Verifikasi: `git check-ignore -v tmp/test.php` should return `.gitignore:<line>:/tmp/*  tmp/test.php`.

  **Must NOT do**:
  - JANGAN gunakan `storage/tmp/` atau path lain. Spesifik: `tmp/` di root.
  - JANGAN edit selain `.gitignore` di file config lain (composer.json, package.json, etc.)
  - JANGAN buat file lain di `tmp/` selain `.gitkeep`.

  **Recommended Agent Profile**:
  - **Category**: `quick`
    - Reason: Trivial file ops + 2-line .gitignore edit. No domain knowledge needed.
  - **Skills**: []
    - No skills required.

  **Parallelization**:
  - **Can Run In Parallel**: NO (foundation untuk T2)
  - **Parallel Group**: Wave 1 — runs FIRST in wave
  - **Blocks**: Task 2 (move scratch files), Task 12 (cross-validate)
  - **Blocked By**: None — can start immediately

  **References**:

  **Pattern References**:
  - `.gitignore` (current file) - lihat existing patterns supaya format konsisten (relative path, leading `/` for root-only).

  **External References**:
  - https://git-scm.com/docs/gitignore — pattern: `!` negate, `*` wildcard.

  **WHY Each Reference Matters**:
  - `.gitignore` existing supaya format baru match style yang ada (e.g. apakah pakai `/path` atau `path`, apakah ada section comment).

  **Acceptance Criteria**:

  - [ ] `Test-Path "tmp"` returns True
  - [ ] `Test-Path "tmp/.gitkeep"` returns True
  - [ ] `(Get-Item "tmp/.gitkeep").Length` is 0 (file kosong)
  - [ ] Grep `.gitignore` mengandung baris `/tmp/*`
  - [ ] Grep `.gitignore` mengandung baris `!/tmp/.gitkeep`
  - [ ] `git check-ignore tmp/dummy.php` returns success (file would be ignored)
  - [ ] `git check-ignore tmp/.gitkeep` returns failure (file is NOT ignored — survives)

  **QA Scenarios**:

  ```
  Scenario: Happy path - tmp/ created and gitignored correctly
    Tool: Bash (PowerShell)
    Preconditions: Project root has no `tmp/` dir, `.gitignore` exists
    Steps:
      1. Run: New-Item -ItemType Directory -Path "tmp" -Force
      2. Run: New-Item -ItemType File -Path "tmp/.gitkeep" -Force
      3. Append to .gitignore: "`n# Local scratch directory (excluded from git)`n/tmp/*`n!/tmp/.gitkeep"
      4. Test-Path "tmp/.gitkeep"
      5. git check-ignore tmp/dummy-test.php (create dummy first)
    Expected Result: Test-Path returns True. git check-ignore returns exit 0 (means file IS ignored).
    Failure Indicators: tmp/ doesn't exist, .gitkeep missing, .gitignore not updated, dummy file NOT ignored.
    Evidence: .sisyphus/evidence/task-1-tmp-setup.txt (capture stdout of all commands)

  Scenario: Negative - .gitkeep itself is NOT ignored (survives in git)
    Tool: Bash (PowerShell)
    Preconditions: Wave 1 step 3 completed
    Steps:
      1. git check-ignore tmp/.gitkeep
      2. Capture exit code
    Expected Result: Exit code is NON-zero (.gitkeep is not ignored, survives commit).
    Evidence: .sisyphus/evidence/task-1-gitkeep-survives.txt
  ```

  **Evidence to Capture**:
  - [ ] `.sisyphus/evidence/task-1-tmp-setup.txt`
  - [ ] `.sisyphus/evidence/task-1-gitkeep-survives.txt`

  **Commit**: NO (groups with Commit 1 setelah Wave 1 selesai)

- [ ] 2. Move 14 scratch PHP scripts from project root to `tmp/`

  **What to do**:
  - Pindahkan 14 file PHP berikut dari project root (`C:\laragon\www\kke-sakip\`) ke `tmp/`:
    - `check-kriteria.php`
    - `check-linkfile-structure.php`
    - `check_schema.php`
    - `debug_rpjmd.php`
    - `syncPenilaian_NEW_TEMPLATE.php`
    - `test-is-perubahan.php`
    - `test-preview-shared.php`
    - `test-skip-upload.php`
    - `test-sync-shared.php`
    - `test-sync.php`
    - `test-validate-rpjmd.php`
    - `validate-is-perubahan.php`
    - `validate-multiple-files.php`
    - `validate-sync.php`
  - Gunakan `Move-Item` PowerShell. JANGAN copy + delete (atomic move untuk safety).
  - Setelah move: verifikasi 14 file ada di `tmp/` dan tidak ada lagi di root.

  **Must NOT do**:
  - JANGAN hapus file. PINDAHKAN saja.
  - JANGAN sentuh `artisan` (PHP file lain di root yang adalah Laravel CLI entry — TETAP di root).
  - JANGAN sentuh file selain 14 yang disebut.

  **Recommended Agent Profile**:
  - **Category**: `quick`
    - Reason: Trivial bulk move operation.
  - **Skills**: []

  **Parallelization**:
  - **Can Run In Parallel**: NO (depends on Task 1 — `tmp/` must exist first)
  - **Parallel Group**: Wave 1 — runs after T1
  - **Blocks**: Task 12
  - **Blocked By**: Task 1

  **References**:

  **Pattern References**:
  - PowerShell `Move-Item` documentation.

  **WHY Each Reference Matters**:
  - Move-Item lebih atomic dari Copy-Item + Remove-Item. Kalau gagal di tengah, source masih intact.

  **Acceptance Criteria**:

  - [ ] `(Get-ChildItem -File -Filter *.php -LiteralPath ".").Count` returns 0 (only `artisan` if it's PHP — but artisan tidak punya `.php` extension, jadi expect 0).
  - [ ] `(Get-ChildItem -File -LiteralPath "tmp" | Where-Object { $_.Name -match '^(test-|validate-|check-|debug_|check_schema|syncPenilaian_)' }).Count` returns 14.
  - [ ] Setiap dari 14 file: `Test-Path "tmp/<filename>"` returns True (file ada di filesystem).
  - [ ] Setiap dari 14 file: `Test-Path "<filename>"` (di root) returns False (sudah pindah).
  - [ ] `git check-ignore tmp/test-sync.php` returns exit 0 (file IS ignored — tidak akan tracked).
  - [ ] `git status --short` shows 14 lines `D <filename>` (deleted from tracking) untuk file di root, BUKAN `R <old> -> <new>` (rename). Ini OK karena `tmp/*` gitignored.

  **QA Scenarios**:

  ```
  Scenario: Happy path - 14 files moved successfully
    Tool: Bash (PowerShell)
    Preconditions: Task 1 completed (tmp/ exists). 14 scratch PHP files at root.
    Steps:
      1. $files = @('check-kriteria.php','check-linkfile-structure.php','check_schema.php','debug_rpjmd.php','syncPenilaian_NEW_TEMPLATE.php','test-is-perubahan.php','test-preview-shared.php','test-skip-upload.php','test-sync-shared.php','test-sync.php','test-validate-rpjmd.php','validate-is-perubahan.php','validate-multiple-files.php','validate-sync.php')
      2. foreach ($f in $files) { Move-Item -LiteralPath $f -Destination "tmp/$f" -Force }
      3. Verify: foreach ($f in $files) { if (-not (Test-Path "tmp/$f")) { throw "Missing $f" }; if (Test-Path $f) { throw "Still at root: $f" } }
    Expected Result: All 14 files now at tmp/, none at root. No exception thrown.
    Failure Indicators: Move-Item error, file still at root, file missing at tmp/.
    Evidence: .sisyphus/evidence/task-2-move-result.txt

  Scenario: Negative - artisan stays at root (NOT moved)
    Tool: Bash (PowerShell)
    Preconditions: Move complete
    Steps:
      1. Test-Path "artisan"
      2. Test-Path "tmp/artisan"
    Expected Result: First True, second False. artisan must remain at root.
    Evidence: .sisyphus/evidence/task-2-artisan-preserved.txt
  ```

  **Evidence to Capture**:
  - [ ] `.sisyphus/evidence/task-2-move-result.txt`
  - [ ] `.sisyphus/evidence/task-2-artisan-preserved.txt`

  **Commit**: NO (bagian Commit 1)

- [ ] 3. Delete `database/seeders/JenisNilai.php` (orphan, BUKAN `JenisNilaiSeeder.php`)

  **What to do**:
  - Hapus file `database/seeders/JenisNilai.php`. File ini adalah seeder kosong dengan class `JenisNilai extends Seeder`, body method `run()` cuma `//`. Tidak dipanggil dari `DatabaseSeeder`.
  - JANGAN hapus `database/seeders/JenisNilaiSeeder.php` — itu yang aktif dipakai dan benar.
  - Verifikasi setelah hapus: `php artisan migrate:fresh --seed` jalan tanpa error (memastikan composer autoload bersih).

  **Must NOT do**:
  - JANGAN hapus `JenisNilaiSeeder.php` (file yang BENAR dan AKTIF).
  - JANGAN edit `DatabaseSeeder.php` (sudah benar, tidak panggil `JenisNilai::class`).

  **Recommended Agent Profile**:
  - **Category**: `quick`
    - Reason: Single file delete + smoke test migrate:fresh --seed.
  - **Skills**: []

  **Parallelization**:
  - **Can Run In Parallel**: YES (independent dari T1, T2, T4, T5)
  - **Parallel Group**: Wave 1
  - **Blocks**: Task 12
  - **Blocked By**: None

  **References**:

  **Pattern References**:
  - `database/seeders/JenisNilai.php` — file yang akan dihapus (verify isinya kosong sebelum delete).
  - `database/seeders/JenisNilaiSeeder.php` — file yang BENAR dan harus tetap ada.
  - `database/seeders/DatabaseSeeder.php` — verify `$this->call([...])` includes `JenisNilaiSeeder::class` not `JenisNilai::class`.

  **WHY Each Reference Matters**:
  - Sebelum hapus, baca isi `JenisNilai.php` untuk konfirmasi memang orphan/empty (defensive).
  - `DatabaseSeeder.php` adalah entry point seeder — kalau ada `JenisNilai::class` di sana, hapus akan break seeding.

  **Acceptance Criteria**:

  - [ ] Pre-check: `Get-Content database/seeders/JenisNilai.php` shows class with empty `run()` body (only `//` or whitespace).
  - [ ] Pre-check: `Select-String -Path "database/seeders/DatabaseSeeder.php" -Pattern "JenisNilai\b"` returns matches HANYA untuk `JenisNilaiSeeder` (BUKAN `JenisNilai::class`).
  - [ ] Run: `Remove-Item -LiteralPath "database/seeders/JenisNilai.php" -Force`
  - [ ] `Test-Path "database/seeders/JenisNilai.php"` returns False
  - [ ] `Test-Path "database/seeders/JenisNilaiSeeder.php"` returns True
  - [ ] `php artisan migrate:fresh --seed` exits 0
  - [ ] Output `migrate:fresh --seed` mentions `JenisNilaiSeeder` running successfully (line "Seeded: Database\Seeders\JenisNilaiSeeder")

  **QA Scenarios**:

  ```
  Scenario: Happy path - orphan deleted, seeder still works
    Tool: Bash (PowerShell)
    Preconditions: database/seeders/JenisNilai.php exists, JenisNilaiSeeder.php exists, DatabaseSeeder.php call JenisNilaiSeeder
    Steps:
      1. Get-Content "database/seeders/JenisNilai.php" -Raw  (verify body kosong)
      2. Select-String -Path "database/seeders/DatabaseSeeder.php" -Pattern "JenisNilai" (verify cuma JenisNilaiSeeder yang dipanggil)
      3. Remove-Item -LiteralPath "database/seeders/JenisNilai.php" -Force
      4. Test-Path "database/seeders/JenisNilai.php"  (expect False)
      5. Test-Path "database/seeders/JenisNilaiSeeder.php"  (expect True)
      6. php artisan migrate:fresh --seed
    Expected Result: Step 4 False, step 5 True, step 6 exit 0 with "Seeded: ... JenisNilaiSeeder" in output.
    Failure Indicators: migrate:fresh fails with "Class 'Database\Seeders\JenisNilai' not found", or seeder didn't run.
    Evidence: .sisyphus/evidence/task-3-delete-jenisnilai.txt (capture full migrate:fresh stdout)

  Scenario: Negative - try to find any reference to deleted class
    Tool: Bash (PowerShell)
    Preconditions: Step 3 of happy path complete
    Steps:
      1. Select-String -Path "database/seeders/*.php","app/**/*.php" -Pattern "use Database\\Seeders\\JenisNilai\b"
      2. Select-String -Path "database/seeders/*.php","app/**/*.php" -Pattern "Database\\Seeders\\JenisNilai::class"
    Expected Result: Both return zero matches (only `JenisNilaiSeeder` references should remain).
    Evidence: .sisyphus/evidence/task-3-no-orphan-refs.txt
  ```

  **Evidence to Capture**:
  - [ ] `.sisyphus/evidence/task-3-delete-jenisnilai.txt`
  - [ ] `.sisyphus/evidence/task-3-no-orphan-refs.txt`

  **Commit**: NO (bagian Commit 1)

- [ ] 4. Delete `app/Livewire/Dashboard/SinkronDokumen.php`

  **What to do**:
  - Hapus file `app/Livewire/Dashboard/SinkronDokumen.php`. Komponen ini tidak terdaftar di route dan tidak punya call site dari kode lain.
  - JANGAN hapus `SinkronData.php` — itu yang aktif terdaftar di route `/sinkron-data`.
  - JANGAN edit `routes/web.php` (file ini tidak punya route untuk SinkronDokumen anyway — verify dengan grep).

  **Must NOT do**:
  - JANGAN sentuh `SinkronData.php`.
  - JANGAN edit `routes/web.php`.
  - JANGAN edit sidebar di `resources/views/components/layouts/app.blade.php` (verifikasi: grep konfirmasi tidak ada link ke `/sinkron-dokumen` di sidebar).

  **Recommended Agent Profile**:
  - **Category**: `quick`
  - **Skills**: []

  **Parallelization**:
  - **Can Run In Parallel**: YES dengan T3
  - **Parallel Group**: Wave 1
  - **Blocks**: Task 5 (delete view), Task 6 (rewrite PANDUAN agar tidak refer SinkronDokumen), Task 12
  - **Blocked By**: None

  **References**:

  **Pattern References**:
  - `routes/web.php` — verify no route registered untuk SinkronDokumen.
  - `resources/views/components/layouts/app.blade.php` — verify no sidebar link ke /sinkron-dokumen.
  - `app/Livewire/Dashboard/SinkronData.php` — file yang HARUS TETAP ADA.

  **External References**:
  - Livewire 3 component lifecycle (https://livewire.laravel.com/docs/components) — komponen tidak ter-discover otomatis kalau tidak dirujuk.

  **WHY Each Reference Matters**:
  - Verify pre-conditions: route + sidebar memang tidak refer ke SinkronDokumen sebelum hapus. Kalau ternyata ada, abort dan eskalasi.

  **Acceptance Criteria**:

  - [ ] Pre-check: `Select-String -Path "routes/web.php" -Pattern "SinkronDokumen"` returns 0 matches.
  - [ ] Pre-check: `Select-String -Path "resources/views/components/layouts/app.blade.php" -Pattern "sinkron-dokumen|SinkronDokumen"` returns 0 matches.
  - [ ] Pre-check: `Select-String -Path "app/**/*.php" -Pattern "SinkronDokumen" -Exclude "SinkronDokumen.php"` returns 0 matches.
  - [ ] Run: `Remove-Item -LiteralPath "app/Livewire/Dashboard/SinkronDokumen.php" -Force`
  - [ ] `Test-Path "app/Livewire/Dashboard/SinkronDokumen.php"` returns False
  - [ ] `Test-Path "app/Livewire/Dashboard/SinkronData.php"` returns True
  - [ ] `php artisan route:list | Select-String "sinkron-data"` returns ≥1 match
  - [ ] `php artisan route:list | Select-String "sinkron-dokumen"` returns 0 matches
  - [ ] `composer dump-autoload` runs successfully

  **QA Scenarios**:

  ```
  Scenario: Happy path - SinkronDokumen deleted, SinkronData unaffected
    Tool: Bash (PowerShell)
    Preconditions: SinkronDokumen.php exists, SinkronData.php exists
    Steps:
      1. Select-String -Path "routes/web.php","app/**/*.php" -Pattern "SinkronDokumen" (capture all matches)
      2. Verify only match is the file itself (`app/Livewire/Dashboard/SinkronDokumen.php` shows as filename in grep result)
      3. Remove-Item -LiteralPath "app/Livewire/Dashboard/SinkronDokumen.php" -Force
      4. Test-Path "app/Livewire/Dashboard/SinkronData.php" (expect True)
      5. composer dump-autoload (smoke check)
      6. php artisan route:list | Select-String "sinkron"
    Expected Result: Step 5 succeeds. Step 6 shows 1 line for /sinkron-data, 0 for /sinkron-dokumen.
    Failure Indicators: composer dump-autoload errors out, route:list empty, SinkronData.php missing.
    Evidence: .sisyphus/evidence/task-4-delete-sinkrondokumen.txt

  Scenario: Negative - try to access /sinkron-dokumen URL after delete (should 404)
    Tool: Bash (curl) - run from any terminal with `php artisan serve` running OR use route:list verification only
    Preconditions: composer dev running, route:list verified
    Steps:
      1. (If server up) curl -I http://localhost:8000/sinkron-dokumen
      2. (Else) php artisan route:list | grep sinkron-dokumen | Measure-Object -Line
    Expected Result: HTTP 404 OR 0 line count from route:list.
    Evidence: .sisyphus/evidence/task-4-sinkron-dokumen-404.txt
  ```

  **Evidence to Capture**:
  - [ ] `.sisyphus/evidence/task-4-delete-sinkrondokumen.txt`
  - [ ] `.sisyphus/evidence/task-4-sinkron-dokumen-404.txt`

  **Commit**: NO (bagian Commit 1)

- [ ] 5. Delete view `resources/views/livewire/dashboard/sinkron-dokumen.blade.php`

  **What to do**:
  - Hapus file `resources/views/livewire/dashboard/sinkron-dokumen.blade.php`.
  - View ini adalah pasangan dari komponen `SinkronDokumen.php` yang dihapus di Task 4. Tidak ada cara dipakai setelah komponen hilang.
  - Verifikasi tidak ada `@include('livewire.dashboard.sinkron-dokumen')` atau `<x-livewire.dashboard.sinkron-dokumen>` di blade lain.

  **Must NOT do**:
  - JANGAN hapus view lain di `resources/views/livewire/dashboard/`.
  - JANGAN edit `sinkron-data.blade.php` (yang aktif).

  **Recommended Agent Profile**:
  - **Category**: `quick`
  - **Skills**: []

  **Parallelization**:
  - **Can Run In Parallel**: NO (depends on T4)
  - **Parallel Group**: Wave 1 — runs after T4
  - **Blocks**: Task 12
  - **Blocked By**: Task 4

  **References**:

  **Pattern References**:
  - `resources/views/livewire/dashboard/sinkron-data.blade.php` — file yang HARUS TETAP ADA.

  **WHY Each Reference Matters**:
  - Konfirmasi tidak salah hapus.

  **Acceptance Criteria**:

  - [ ] Pre-check: `Select-String -Path "resources/views/**/*.blade.php" -Pattern "sinkron-dokumen"` returns 0 matches outside the file itself.
  - [ ] Pre-check: `Select-String -Path "resources/views/**/*.blade.php" -Pattern "@include.*sinkron-dokumen|<x-.*sinkron-dokumen"` returns 0 matches.
  - [ ] Run: `Remove-Item -LiteralPath "resources/views/livewire/dashboard/sinkron-dokumen.blade.php" -Force`
  - [ ] `Test-Path "resources/views/livewire/dashboard/sinkron-dokumen.blade.php"` returns False
  - [ ] `Test-Path "resources/views/livewire/dashboard/sinkron-data.blade.php"` returns True

  **QA Scenarios**:

  ```
  Scenario: Happy path - view deleted, no broken reference
    Tool: Bash (PowerShell)
    Preconditions: Task 4 complete (component already deleted). View still exists.
    Steps:
      1. Select-String -Path "resources/views/**/*.blade.php" -Pattern "sinkron-dokumen" (capture)
      2. Verify all matches are within the file itself (sinkron-dokumen.blade.php) — NO references from other blades
      3. Remove-Item -LiteralPath "resources/views/livewire/dashboard/sinkron-dokumen.blade.php" -Force
      4. Test-Path "resources/views/livewire/dashboard/sinkron-dokumen.blade.php" (expect False)
      5. Test-Path "resources/views/livewire/dashboard/sinkron-data.blade.php" (expect True)
    Expected Result: All steps succeed. Sinkron-data view unaffected.
    Failure Indicators: Other blade files reference sinkron-dokumen (would break rendering).
    Evidence: .sisyphus/evidence/task-5-delete-view.txt

  Scenario: Negative - try to render with View facade fails after delete
    Tool: Bash (PowerShell + tinker)
    Preconditions: Step 3 above complete
    Steps:
      1. echo "view('livewire.dashboard.sinkron-dokumen')->render();" | php artisan tinker --execute=-
      2. Capture exit code OR error output
    Expected Result: Throws InvalidArgumentException ("View [livewire.dashboard.sinkron-dokumen] not found.").
    Evidence: .sisyphus/evidence/task-5-view-not-found.txt
  ```

  **Evidence to Capture**:
  - [ ] `.sisyphus/evidence/task-5-delete-view.txt`
  - [ ] `.sisyphus/evidence/task-5-view-not-found.txt`

  **Commit**: YES (Commit 1 setelah T1-T5 selesai)
  - Message: `chore: move scratch scripts to tmp/, remove dead Livewire SinkronDokumen + orphan JenisNilai seeder`
  - Files staged: `tmp/.gitkeep`, `.gitignore` (modified), 14 moved files (`tmp/test-*.php`, etc.), 3 deletions (`database/seeders/JenisNilai.php`, `app/Livewire/Dashboard/SinkronDokumen.php`, `resources/views/livewire/dashboard/sinkron-dokumen.blade.php`)
  - Pre-commit: `php artisan migrate:fresh --seed` exit 0 AND `php artisan route:list | Select-String "sinkron-data"` returns ≥1 match.

- [ ] 6. Rewrite `PANDUAN_SINKRONISASI_ESAKIP.md`

  **What to do**:
  - REWRITE LENGKAP file `PANDUAN_SINKRONISASI_ESAKIP.md` (yang saat ini 261 baris). Buang semua reference ke:
    - `SinkronDokumen.php` (komponen yang dihapus di T4) → ganti dengan `SinkronData.php`
    - `Sinkronisasi Dokumen` (label menu) → ganti `Sinkronisasi Data`
    - Mode sinkronisasi `Gabung/Ganti/Lewati` (3-mode picker yang sudah tidak ada) → tegaskan: smart-merge automatic, no mode choice
    - Field `sync_mode` di form/code → tidak ada lagi
  - TAMBAHKAN section baru:
    - **Setup OPD Reorganisasi** — penjelasan kolom `tahun_mulai_berlaku` dan `predecessor_opd_id` di tabel `opd`. Skenario: OPD baru di tahun X, butuh ambil dokumen tahun <X dari predecessor (esakip_opd_id lama).
    - **Dokumen N-1** — penjelasan kolom `bukti_dukung.is_n_minus_1`. Skenario: Renstra OPD = dokumen tahun lalu (N-1) selalu.
    - **Smart Merge & Source Tracking** — `penilaian.source` (`upload`/`esakip`) dan implication: upload manual TIDAK akan ditimpa sync.
    - **Skenario terbaru**: OPD baru reorganisasi (lookup ke predecessor), N-1 untuk Renstra/IKU, kombinasi.
  - PERTAHANKAN section yang masih relevan:
    - Setup Mapping Bukti Dukung
    - Cara menggunakan SinkronData (preview → process)
    - Konfigurasi `config/esakip.php` & `.env`
    - Logging & History
  - UPDATE section `Edge Cases yang Di-handle`:
    - Buang sub-bullets tentang "Mode Gabung/Ganti/Lewati"
    - Ganti dengan: Smart merge by URL+timestamp, source-based skip protection
  - UPDATE section `Database Reference`:
    - Tambah dokumentasi 3 kolom baru
    - Refresh struktur `riwayat_sinkron` agar match migration aktual

  **Must NOT do**:
  - JANGAN edit file lain di Wave 2.
  - JANGAN edit source PHP code (mention pattern saja).
  - JANGAN translate ke English (project Indonesian-first).
  - JANGAN add screenshot atau gambar (text + code blocks only).
  - JANGAN buat file baru (cuma rewrite existing).

  **Recommended Agent Profile**:
  - **Category**: `writing`
    - Reason: Documentation rewrite — needs prose + technical accuracy.
  - **Skills**: []

  **Parallelization**:
  - **Can Run In Parallel**: YES dengan T7-T11 (independent files)
  - **Parallel Group**: Wave 2
  - **Blocks**: Task 12
  - **Blocked By**: Task 4 (komponen SinkronDokumen harus sudah dihapus, supaya doc tidak refer ke sesuatu yang tidak ada)

  **References**:

  **Pattern References**:
  - `app/Services/EsakipSyncService.php` lines 200-499 (`processSync` + `syncDocumentForOpd`) — sumber kebenaran untuk flow.
  - `app/Services/EsakipSyncService.php` lines 350-410 — penanganan `is_n_minus_1` + `predecessor_opd_id` + `tahun_mulai_berlaku`.
  - `app/Services/EsakipSyncService.php` lines 530-600 (`syncSharedDocument`) — alur shared documents Pemkab.
  - `app/Livewire/Dashboard/SinkronData.php` — komponen yang aktif (entry point UI).
  - `database/seeders/OpdSeeder.php` lines 59-90 — contoh data OPD baru reorganisasi (Dinas Pendidikan baru → predecessor Disdikpora).
  - `database/migrations/2026_05_10_120000_add_opd_mapping_columns_to_opd_table.php` — definisi kolom `tahun_mulai_berlaku` dan `predecessor_opd_id`.
  - `database/migrations/2026_05_10_120100_add_is_n_minus_1_to_bukti_dukung_table.php` — definisi `is_n_minus_1`.
  - `config/esakip.php` — list document_types yang relevan.

  **API/Type References**:
  - `processSync($tahunId, $opdId = null, $documentType = null, $progressCallback = null)` — entry point public.
  - `previewSync($tahunId, $opdId = null, $documentType = null)` — entry point public.

  **Test References**:
  - Tidak ada test references — project tidak punya test suite.

  **External References**:
  - `.sisyphus/docs/FLOWS.md` section 10 (Sinkronisasi eSAKIP) — sudah ada flow analysis singkat, bisa dikutip/dirujuk.
  - `.sisyphus/docs/SYNC_FLOW_ANALYSIS.md` (akan dibuat di T11) — referensi untuk analisis lanjutan.

  **WHY Each Reference Matters**:
  - `EsakipSyncService` adalah sumber kebenaran. Setiap statement di doc HARUS verified ke kode aktual (file:line citation).
  - `OpdSeeder` punya contoh konkret OPD reorganisasi 2026 — pakai sebagai example data di doc.
  - 2 migration files menunjukkan EXACT struktur kolom — cantumkan di section Database Reference.

  **Acceptance Criteria**:

  - [ ] File `PANDUAN_SINKRONISASI_ESAKIP.md` exist dan ≥250 baris (substantial rewrite, tidak hanya delete).
  - [ ] Grep file: `Select-String -Pattern "SinkronDokumen|sync_mode|syncMode|Mode.*Gabung|Mode.*Ganti|Mode.*Lewati"` returns 0 matches (di file ini).
  - [ ] Grep file: `Select-String -Pattern "is_n_minus_1"` returns ≥3 matches.
  - [ ] Grep file: `Select-String -Pattern "predecessor_opd_id"` returns ≥3 matches.
  - [ ] Grep file: `Select-String -Pattern "tahun_mulai_berlaku"` returns ≥3 matches.
  - [ ] Grep file: `Select-String -Pattern "SinkronData"` returns ≥1 match.
  - [ ] Grep file: `Select-String -Pattern "smart.?merge"` returns ≥1 match (singgung smart merge sebagai default behavior).
  - [ ] Grep file: `Select-String -Pattern "source.*upload|source.*esakip"` returns ≥2 matches (jelaskan source column).
  - [ ] Grep file: `Select-String -Pattern "OPD baru|reorganisasi"` returns ≥2 matches.
  - [ ] At least 3 code-block examples (PHP/JSON/SQL) showing real syntax dari codebase.
  - [ ] Cross-reference: ada link/sebut ke `.sisyphus/docs/FLOWS.md` atau `SYNC_FLOW_ANALYSIS.md`.
  - [ ] Tidak ada referensi ke route `/sinkron-dokumen` (gunakan `/sinkron-data`).

  **QA Scenarios**:

  ```
  Scenario: Happy path - rewrite passes content audit
    Tool: Bash (PowerShell)
    Preconditions: PANDUAN_SINKRONISASI_ESAKIP.md telah di-rewrite oleh agent
    Steps:
      1. (Get-Content "PANDUAN_SINKRONISASI_ESAKIP.md" | Measure-Object -Line).Lines  (expect ≥250)
      2. Select-String -Path "PANDUAN_SINKRONISASI_ESAKIP.md" -Pattern "SinkronDokumen|sync_mode" | Measure-Object  (expect 0)
      3. Select-String -Path "PANDUAN_SINKRONISASI_ESAKIP.md" -Pattern "is_n_minus_1" | Measure-Object  (expect ≥3)
      4. Select-String -Path "PANDUAN_SINKRONISASI_ESAKIP.md" -Pattern "predecessor_opd_id" | Measure-Object  (expect ≥3)
      5. Select-String -Path "PANDUAN_SINKRONISASI_ESAKIP.md" -Pattern "tahun_mulai_berlaku" | Measure-Object  (expect ≥3)
    Expected Result: All steps pass thresholds.
    Failure Indicators: Stale references remain, new features not documented enough.
    Evidence: .sisyphus/evidence/task-6-rewrite-content-audit.txt

  Scenario: Negative - file is NOT just deleted/empty
    Tool: Bash (PowerShell)
    Preconditions: Step above complete
    Steps:
      1. (Get-Item "PANDUAN_SINKRONISASI_ESAKIP.md").Length  (expect ≥10000 bytes)
    Expected Result: File is substantial, not stub.
    Evidence: .sisyphus/evidence/task-6-file-size.txt
  ```

  **Evidence to Capture**:
  - [ ] `.sisyphus/evidence/task-6-rewrite-content-audit.txt`
  - [ ] `.sisyphus/evidence/task-6-file-size.txt`

  **Commit**: NO (bagian Commit 2 setelah Wave 2)

- [ ] 7. Update `DATABASE_SINKRONISASI.md`

  **What to do**:
  - File saat ini 231 baris. Update untuk MEN-CAKUP 3 kolom baru yang belum ter-dokumentasi.
  - **HAPUS line 271** (atau yang sekarang ada) yang berbunyi `5. ⏳ **Livewire Component** - Perlu dibuat SinkronDokumen.php` — ini stale reference. Ganti dengan `5. ✅ **Livewire Component** - SinkronData.php (di app/Livewire/Dashboard/SinkronData.php)`.
  - TAMBAHKAN section baru `### 4. Tabel opd` dengan struktur:
    | Kolom | Type | Nullable | Default | Keterangan |
    | `tahun_mulai_berlaku` | int | YES | NULL | Tahun mulai berlaku OPD ini (untuk OPD baru/reorganisasi) |
    | `predecessor_opd_id` | unsignedBigInteger | YES | NULL | esakip_opd_id OPD lama yang menjadi predecessor (BUKAN FK aplikasi) |
    | `esakip_opd_id` | string | YES | NULL | ID OPD di eSAKIP (sudah ada di doc lama, refresh kalau perlu) |
  - UPDATE section `1. Tabel bukti_dukung` — TAMBAH baris untuk `is_n_minus_1`:
    | `is_n_minus_1` | boolean | NO | false | Apakah bukti dukung mengambil dokumen tahun n-1 |
  - VERIFIKASI section `3. Tabel riwayat_sinkron` masih akurat dengan migration. Update kolom yang berubah:
    - `dokumen` (string) DIROBAK menjadi `document_type` + `document_name` (lihat migration `2026_01_13_121656`).
    - `tahun` (string) DIROBAK menjadi `tahun_value` (sama migration).
    - Status enum: tambah `'no_document'` (migration `2026_01_14_090635`).
  - VERIFIKASI section `2. Tabel penilaian` sudah include `page_number`, `source`, `esakip_document_id`, `esakip_synced_at`. Refresh kalau ada yang missing.
  - Hapus `// Deprecated` notation yang tidak relevan kalau ada.

  **Must NOT do**:
  - JANGAN ubah struktur dokumen (heading hierarchy).
  - JANGAN tambah info tentang OPD baru flow di sini (itu di T6).
  - JANGAN sentuh file lain.
  - JANGAN biarkan reference ke `SinkronDokumen.php` di file ini setelah update.

  **Recommended Agent Profile**:
  - **Category**: `writing`
  - **Skills**: []

  **Parallelization**:
  - **Can Run In Parallel**: YES dengan T6, T8-T11
  - **Parallel Group**: Wave 2
  - **Blocks**: Task 12
  - **Blocked By**: None

  **References**:

  **Pattern References**:
  - `database/migrations/2026_05_10_120000_add_opd_mapping_columns_to_opd_table.php` — exact struct.
  - `database/migrations/2026_05_10_120100_add_is_n_minus_1_to_bukti_dukung_table.php` — exact struct.
  - `database/migrations/2026_01_13_121656_update_riwayat_sinkron_table_structure.php` — riwayat_sinkron rename + new columns.
  - `database/migrations/2026_01_14_090635_update_riwayat_sinkron_status_enum.php` — enum update.
  - `database/migrations/2026_01_14_161938_add_page_number_to_penilaian_table.php` — page_number column.
  - `database/migrations/2026_01_13_121720_add_source_columns_to_penilaian_table.php` — source/esakip_document_id/esakip_synced_at.
  - Existing `DATABASE_SINKRONISASI.md` — preserve heading style + table format.

  **WHY Each Reference Matters**:
  - Migration adalah satu-satunya source of truth untuk schema. Setiap row di tabel docs HARUS persis match dengan migration.

  **Acceptance Criteria**:

  - [ ] File `DATABASE_SINKRONISASI.md` exist dan ≥260 baris (tambah dari 231).
  - [ ] Grep file: `Select-String -Pattern "is_n_minus_1"` returns ≥1 match.
  - [ ] Grep file: `Select-String -Pattern "predecessor_opd_id"` returns ≥1 match.
  - [ ] Grep file: `Select-String -Pattern "tahun_mulai_berlaku"` returns ≥1 match.
  - [ ] Grep file: `Select-String -Pattern "Tabel opd"` returns ≥1 match.
  - [ ] Grep file: `Select-String -Pattern "no_document"` returns ≥1 match.
  - [ ] Tabel opd di-dokumentasikan dengan ≥3 kolom (esakip_opd_id, tahun_mulai_berlaku, predecessor_opd_id).
  - [ ] Format Markdown table preserved (pipe-separator, alignment).

  **QA Scenarios**:

  ```
  Scenario: Happy path - new columns documented
    Tool: Bash (PowerShell)
    Preconditions: DATABASE_SINKRONISASI.md updated
    Steps:
      1. Select-String -Path "DATABASE_SINKRONISASI.md" -Pattern "is_n_minus_1|predecessor_opd_id|tahun_mulai_berlaku" | Measure-Object  (expect ≥3)
      2. Select-String -Path "DATABASE_SINKRONISASI.md" -Pattern "Tabel opd"  (expect ≥1)
      3. (Get-Content "DATABASE_SINKRONISASI.md" | Measure-Object -Line).Lines  (expect ≥260)
    Expected Result: All thresholds met.
    Failure Indicators: Section "Tabel opd" missing, atau kolom baru tidak disebut.
    Evidence: .sisyphus/evidence/task-7-db-doc-audit.txt

  Scenario: Negative - schema match migration files (citation check)
    Tool: Bash (PowerShell + grep)
    Steps:
      1. Get migration content for predecessor_opd_id
      2. Verify doc says "unsignedBigInteger" matches migration
    Expected Result: Schema description in doc matches migration verbatim.
    Evidence: .sisyphus/evidence/task-7-schema-citation.txt
  ```

  **Evidence to Capture**:
  - [ ] `.sisyphus/evidence/task-7-db-doc-audit.txt`
  - [ ] `.sisyphus/evidence/task-7-schema-citation.txt`

  **Commit**: NO (Commit 2 batch)

- [ ] 8. Update `SMART_SYNC_STRATEGY.md`

  **What to do**:
  - File saat ini ~340 baris (perlu verify length). Update untuk ALIGN dengan implementasi aktual.
  - HAPUS reference ke parameter `$syncMode` di method `syncPenilaian()`. Saat ini di file ada line: `protected function syncPenilaian($buktiDukung, $opd, $documents, $syncMode)`. Implementasi aktual TIDAK punya `$syncMode` parameter. Ganti reference ke signature yang benar dengan citation file:line dari `EsakipSyncService.php`.
  - TAMBAH section baru `## Source-Based Skip Protection` yang menjelaskan:
    - Penilaian dengan `source = 'upload'` (manual) TIDAK akan ditimpa sync.
    - Penilaian dengan `source = 'esakip'` akan smart-merged.
    - Penilaian baru dari sync akan punya `source = 'esakip'` by default.
  - TAMBAH section baru `## OPD Reorganisasi & N-1 Documents` yang merangkum:
    - Bagaimana `is_n_minus_1` mengubah `sourceYear` (current - 1).
    - Bagaimana `predecessor_opd_id` + `tahun_mulai_berlaku` menentukan `sourceEsakipOpdId` (lookup ke OPD lama saat OPD baru belum eksis di tahun target).
    - Skenario kombinasi (N-1 untuk OPD reorganisasi).
    - Citation: `EsakipSyncService.php:355-410`.
  - PERTAHANKAN section yang masih akurat:
    - Unique Identifier Dokumen (URL + timestamp)
    - Logika Sync Tanpa Mode
    - Strategi 1 & 2 (URL only / Timestamp in Filename)
    - Smart Merge implementation pseudocode
  - VERIFIKASI signature `smartMergeDocuments($existingFiles, $apiDocuments)` di doc match dengan `EsakipSyncService.php:1306`.

  **Must NOT do**:
  - JANGAN edit source code untuk fix `$syncMode` discrepancy — itu pekerjaan terpisah.
  - JANGAN bikin section baru yang duplicate dengan T6 (PANDUAN) atau T11 (ANALYSIS).

  **Recommended Agent Profile**:
  - **Category**: `writing`
  - **Skills**: []

  **Parallelization**:
  - **Can Run In Parallel**: YES dengan T6, T7, T9-T11
  - **Parallel Group**: Wave 2
  - **Blocks**: Task 12
  - **Blocked By**: None

  **References**:

  **Pattern References**:
  - `app/Services/EsakipSyncService.php:1306` — `smartMergeDocuments()` actual signature.
  - `app/Services/EsakipSyncService.php:1280-1296` — `documentExists()` dedup logic (URL + timestamp).
  - `app/Services/EsakipSyncService.php:355-410` — N-1 + predecessor handling.
  - `app/Services/EsakipSyncService.php:600-700` — `syncPenilaian` actual signature (no $syncMode).
  - `database/migrations/2026_01_13_121720_add_source_columns_to_penilaian_table.php` — source enum.

  **WHY Each Reference Matters**:
  - Doc lama mengandung pseudo-code yang BERBEDA dari implementasi. Setiap claim harus diverifikasi.

  **Acceptance Criteria**:

  - [ ] Grep file: `Select-String -Pattern "\$syncMode"` returns 0 matches (parameter sudah tidak ada).
  - [ ] Grep file: `Select-String -Pattern "is_n_minus_1|predecessor_opd_id"` returns ≥2 matches.
  - [ ] Grep file: `Select-String -Pattern "source.*upload|source.*esakip"` returns ≥2 matches.
  - [ ] Section `## OPD Reorganisasi` atau similar exist (grep `OPD Reorganisasi|Reorganisasi`).
  - [ ] Section `## Source-Based|## Skip Protection` exist.
  - [ ] Existing sections preserved (Unique Identifier, Smart Merge logic).

  **QA Scenarios**:

  ```
  Scenario: Happy path - stale references removed, new section added
    Tool: Bash (PowerShell)
    Preconditions: SMART_SYNC_STRATEGY.md updated
    Steps:
      1. Select-String -Path "SMART_SYNC_STRATEGY.md" -Pattern "\$syncMode" | Measure-Object  (expect 0)
      2. Select-String -Path "SMART_SYNC_STRATEGY.md" -Pattern "Reorganisasi|N-1|n-1" | Measure-Object  (expect ≥2)
      3. Select-String -Path "SMART_SYNC_STRATEGY.md" -Pattern "smartMergeDocuments" | Measure-Object  (expect ≥1, signature still mentioned)
    Expected Result: Stale removed, new added.
    Failure Indicators: $syncMode masih ada, atau new sections missing.
    Evidence: .sisyphus/evidence/task-8-smart-sync-audit.txt
  ```

  **Evidence to Capture**:
  - [ ] `.sisyphus/evidence/task-8-smart-sync-audit.txt`

  **Commit**: NO (Commit 2 batch)

- [ ] 9. Update `STRUKTUR_LINK_FILE.md`

  **What to do**:
  - File saat ini ~140 baris. Verifikasi struktur JSON `link_file` yang didokumentasikan match dengan implementasi `EsakipSyncService::buildFileObject()` dan `LembarKerja::uploadBuktiDukung()`.
  - Schema yang harus tertulis di doc (cross-check ke kode):
    ```json
    {
      "url": "...",                  // Required, primary key
      "path": "...",                 // Manual upload only (Storage path), null untuk eSAKIP
      "original_name": "...",
      "is_perubahan": false,         // Per-file
      "kategori": "induk|perubahan",
      "keterangan": "...",
      "periode": "...",              // From eSAKIP API kalau ada, null untuk upload manual
      "tanggal_publish": "YYYY-MM-DD",
      "from_esakip": false,
      "uploaded_at": "YYYY-MM-DD HH:MM:SS",
      "synced_at": "YYYY-MM-DD HH:MM:SS",  // Hanya from_esakip=true
      "page_number": 1
    }
    ```
  - TAMBAH catatan tentang `page_number` (kolom di top level Penilaian + per-file di JSON).
  - TAMBAH catatan tentang `source` field di Penilaian (`upload` vs `esakip`) — TIDAK di JSON, tapi di kolom Penilaian.
  - VERIFY `is_perubahan` semantic: per-file di JSON, juga di top-level Penilaian (boolean cast).
  - VERIFY `kategori` values: hanya `induk` atau `perubahan`.

  **Must NOT do**:
  - JANGAN add field yang tidak benar-benar dipakai di kode.
  - JANGAN translate bahasa.

  **Recommended Agent Profile**:
  - **Category**: `writing`
  - **Skills**: []

  **Parallelization**:
  - **Can Run In Parallel**: YES dengan T6, T7, T8, T10, T11
  - **Parallel Group**: Wave 2
  - **Blocks**: Task 12
  - **Blocked By**: None

  **References**:

  **Pattern References**:
  - `app/Services/EsakipSyncService.php` — search `buildFileObject` method (location: ~line 1230-1280).
  - `app/Livewire/Dashboard/LembarKerja.php:982-997` — manual upload file struct.
  - `app/Livewire/Dashboard/Monitoring/KriteriaKomponen/BuktiDukung.php:611-628` — alternative upload path file struct.
  - `app/Models/Penilaian.php:14` — cast `link_file => 'array'`.
  - Existing `STRUKTUR_LINK_FILE.md` — preserve format.

  **WHY Each Reference Matters**:
  - 2 upload code paths (LembarKerja dan Monitoring) harus produce JSON struct yang KONSISTEN. Kalau tidak, doc-nya harus mention diff.

  **Acceptance Criteria**:

  - [ ] Grep file: `Select-String -Pattern "page_number"` returns ≥1 match (field di-dokumentasikan).
  - [ ] Grep file: `Select-String -Pattern "synced_at"` returns ≥1 match.
  - [ ] Grep file: `Select-String -Pattern "from_esakip"` returns ≥1 match.
  - [ ] Grep file: `Select-String -Pattern "kategori"` returns ≥2 matches.
  - [ ] Schema JSON example exist dengan minimal 8 field documented.

  **QA Scenarios**:

  ```
  Scenario: Happy path - all fields cross-validated
    Tool: Bash (PowerShell)
    Steps:
      1. Select-String -Path "STRUKTUR_LINK_FILE.md" -Pattern "page_number|synced_at|kategori|from_esakip" | Measure-Object  (expect ≥4)
      2. (Get-Content "STRUKTUR_LINK_FILE.md" | Measure-Object -Line).Lines  (expect ≥150)
    Expected Result: Schema doc complete.
    Evidence: .sisyphus/evidence/task-9-link-file-audit.txt
  ```

  **Evidence to Capture**:
  - [ ] `.sisyphus/evidence/task-9-link-file-audit.txt`

  **Commit**: NO (Commit 2 batch)

- [ ] 10. Update `TROUBLESHOOTING_SINKRONISASI.md`

  **What to do**:
  - File saat ini 284 baris. Tambah scenarios troubleshooting baru:
    - **OPD baru tidak dapat dokumen** — diagnosa: cek `tahun_mulai_berlaku` set, cek `predecessor_opd_id` set, cek esakip_opd_id predecessor masih valid di eSAKIP API.
    - **Dokumen N-1 kosong** — diagnosa: cek `bukti_dukung.is_n_minus_1` true, cek API eSAKIP punya dokumen tahun X-1, cek `tahun_mulai_berlaku` constraint.
    - **Dokumen Pemkab (shared) tidak masuk** — diagnosa: cek `esakip_opd_id` dari Pemkab (selalu 1), cek API endpoint shared documents.
  - TAMBAH SQL query baru di Diagnosis Checklist:
    ```sql
    -- Cek OPD reorganisasi
    SELECT id, nama, esakip_opd_id, tahun_mulai_berlaku, predecessor_opd_id
    FROM opd
    WHERE tahun_mulai_berlaku IS NOT NULL OR predecessor_opd_id IS NOT NULL
    ORDER BY tahun_mulai_berlaku DESC;
    
    -- Cek bukti_dukung N-1
    SELECT id, nama, esakip_document_type, is_n_minus_1, tahun_id
    FROM bukti_dukung
    WHERE is_n_minus_1 = 1
    ORDER BY tahun_id DESC;
    ```
  - VERIFY existing SQL queries (Cek Penilaian, Cek Role, Cek Bukti Dukung Mapping, Cek OPD Mapping, Cek Riwayat Sinkronisasi) masih valid dengan schema sekarang.
  - PERTAHANKAN format troubleshooting (Diagnosis Checklist → Action Required).

  **Must NOT do**:
  - JANGAN sentuh file lain.
  - JANGAN add troubleshooting feature yang tidak ada di codebase aktual.

  **Recommended Agent Profile**:
  - **Category**: `writing`
  - **Skills**: []

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 2
  - **Blocks**: Task 12
  - **Blocked By**: None

  **References**:

  **Pattern References**:
  - `app/Services/EsakipSyncService.php:355-410` — N-1 + predecessor logic untuk diagnostic queries.
  - `app/Services/EsakipSyncService.php:530-600` — shared documents flow.
  - `database/seeders/OpdSeeder.php:59-90` — contoh OPD baru reorganisasi.
  - Existing `TROUBLESHOOTING_SINKRONISASI.md` — preserve format dan style SQL queries.

  **WHY Each Reference Matters**:
  - Troubleshooting harus actionable — beri SQL query yang user bisa langsung run di SQLite.

  **Acceptance Criteria**:

  - [ ] File ≥320 baris (tambah dari 284).
  - [ ] Grep file: `Select-String -Pattern "is_n_minus_1|predecessor_opd_id|tahun_mulai_berlaku"` returns ≥3 matches.
  - [ ] At least 2 new SQL query examples added (untuk OPD reorganisasi + bukti N-1).
  - [ ] At least 3 new troubleshooting scenarios added (OPD baru, N-1 kosong, shared dokumen).

  **QA Scenarios**:

  ```
  Scenario: Happy path - new troubleshooting added
    Tool: Bash (PowerShell)
    Steps:
      1. (Get-Content "TROUBLESHOOTING_SINKRONISASI.md" | Measure-Object -Line).Lines  (expect ≥320)
      2. Select-String -Path "TROUBLESHOOTING_SINKRONISASI.md" -Pattern "is_n_minus_1|predecessor_opd_id|tahun_mulai_berlaku" | Measure-Object  (expect ≥3)
      3. Select-String -Path "TROUBLESHOOTING_SINKRONISASI.md" -Pattern "OPD baru|reorganisasi|shared|bersama" | Measure-Object  (expect ≥3)
    Expected Result: All thresholds met.
    Evidence: .sisyphus/evidence/task-10-troubleshooting-audit.txt
  ```

  **Evidence to Capture**:
  - [ ] `.sisyphus/evidence/task-10-troubleshooting-audit.txt`

  **Commit**: NO (Commit 2 batch)

- [ ] 11. Write `.sisyphus/docs/SYNC_FLOW_ANALYSIS.md` (analisis flow + issues + rekomendasi)

  **What to do**:
  - Buat file baru `.sisyphus/docs/SYNC_FLOW_ANALYSIS.md` (mirip format `.sisyphus/docs/KNOWN_BUGS.md`).
  - Section 1: **Method Map** — full call graph dari `previewSync` & `processSync` sampai leaf method. Format: nama method, line range, fungsi 1 baris, called by, calls.
  - Section 2: **Feature Handling** — peta lengkap implementasi 3 fitur (`is_n_minus_1`, `predecessor_opd_id`, `tahun_mulai_berlaku`):
    - 2A: `is_n_minus_1` — di method mana di-cek, bagaimana adjust `sourceYear`, special case shared documents.
    - 2B: `predecessor_opd_id` — di method mana di-cek, kondisi trigger (`sourceYear < tahun_mulai_berlaku`), apa yang dilookup, fallback kalau predecessor tidak set.
    - 2C: `tahun_mulai_berlaku` — di method mana di-cek, bagaimana memengaruhi sourceEsakipOpdId.
  - Section 3: **Edge Cases yang Sudah Di-handle** — listing dengan citation file:line.
  - Section 4: **Issues Found** — minimal 10 issues dengan format yang sama seperti `KNOWN_BUGS.md`:
    - SYNC-001: Issue title
      - Gejala
      - Lokasi (file:line)
      - Akar penyebab
      - Dampak (severity)
      - Rekomendasi fix
    - Contoh issues yang harus ada (saya identifikasi dari audit):
      - SYNC-001: `previewSync` TIDAK trace OPD reorganisasi flow yang sama dengan `processSync` — preview bisa show data berbeda dari hasil aktual sync (lihat `previewSync` line 35-200 vs `syncDocumentForOpd` line 331-490).
      - SYNC-002: `predecessor_opd_id` adalah `unsignedBigInteger` tapi semantic-nya adalah ID di eSAKIP (BUKAN FK aplikasi). Migration `2026_05_10_120200_fix_predecessor_opd_id_to_esakip_id.php` no-op. Doc/comment menjelaskan tapi nama kolom bisa bingungkan.
      - SYNC-003: Tidak ada validation kalau `predecessor_opd_id` invalid (e.g. esakip_opd_id yang sudah dihapus di eSAKIP). API call akan gagal silently.
      - SYNC-004: `tahun_mulai_berlaku` cek `< $opd->tahun_mulai_berlaku` (strict less). Apakah benar untuk tahun YANG SAMA dengan `tahun_mulai_berlaku` harus dari predecessor? Logic ambiguous.
      - SYNC-005: `is_n_minus_1` selalu `sourceYear - 1` tanpa cap. Kalau OPD baru tahun 2026 (`tahun_mulai_berlaku = 2026`) dengan `is_n_minus_1=true`, sourceYear = 2025 < 2026 → ambil dari predecessor. Tapi apakah logic ini benar untuk semua kombinasi (e.g. dokumen Renstra N-1 tahun 2024 untuk OPD baru 2026)?
      - SYNC-006: `syncSharedDocument` HARDCODE `sourceEsakipOpdId = 1` untuk Pemkab. Kalau Pemkab pernah re-org, hard-code bisa break.
      - SYNC-007: `clearRiwayat()` di `SinkronData::clearRiwayat()` pakai `truncate()` tanpa konfirmasi UI (ini sudah disebut di KNOWN_BUGS.md BUG-007 — referensi cross).
      - SYNC-008: Sync run synchronously dalam Livewire action. Untuk multi-OPD/multi-document_type, satu request bisa hit timeout. No queue dispatch fallback.
      - SYNC-009: Race condition kalau dua admin trigger sync simultan untuk OPD/tahun sama — `DB::beginTransaction` tidak melindungi dari double-update karena tidak ada row-level lock pada Penilaian.
      - SYNC-010: `documentExists()` dedup pakai URL primary + timestamp secondary. Kalau eSAKIP re-upload dengan URL berbeda dan timestamp filename baru, akan dianggap dokumen baru → duplikasi di link_file.
      - SYNC-011: `previewSync` panggil `fetchDocumentsFromEsakip` (3 sumber: per-OPD + lainnya + Pemkab shared) — kalau 1 dari 3 fail, exception bubble up. Preview gagal total walau 2 sumber lain success.
      - SYNC-012: Tidak ada cancellation mechanism. Sync besar (semua OPD × semua document_types) bisa makan menit. User tidak bisa abort.
      - SYNC-013: `RiwayatSinkron` row dibuat per (opd × document_type). Untuk full sync 30 OPD × 20 doctypes = 600 row per sync run. Tabel ini tidak punya retention policy.
      - SYNC-014: `Penilaian.source` enum hanya `'upload' | 'esakip'`. Tidak ada source untuk dokumen yang DIPADUKAN (mix manual + esakip merged). Skema ini hilang nuance.
      - SYNC-015: Auto-verification untuk role verifikator — `createAutoVerifiedPenilaian` (line 763) ambil `bukti_dukung.role_id`. Kalau bukti dukung punya role_id penjamin/penilai (bukan verifikator), dia tetap di-auto-verify. Apakah benar penjamin/penilai harus auto-verify dari sync?
  - Section 5: **Recommendations Prioritized** — minimal 5 recommendations dengan severity matrix (HIGH/MEDIUM/LOW + Effort + Impact).
  - Section 6: **References** — link ke file aktual + line number.
  - Format = telegraphic, tabular jika perlu, code citation Markdown link `[file.php:N](file:///...)`.

  **Must NOT do**:
  - JANGAN bikin issues tanpa citation (file:line).
  - JANGAN paraphrase code — quote actual line saat memungkinkan.
  - JANGAN sebut DEPRECATED MODELS sebagai issue (sudah ada di DEAD_CODE.md).
  - JANGAN translate ke English.
  - JANGAN edit `EsakipSyncService.php` (analisis only, no fix).
  - JANGAN tambah issue yang spekulatif (every issue MUST have file:line citation).

  **Recommended Agent Profile**:
  - **Category**: `deep`
    - Reason: Deep code analysis dengan multi-step reasoning. Butuh trace call graph + identifikasi pattern issue.
  - **Skills**: []

  **Parallelization**:
  - **Can Run In Parallel**: YES dengan T6-T10
  - **Parallel Group**: Wave 2
  - **Blocks**: Task 12
  - **Blocked By**: None

  **References**:

  **Pattern References**:
  - `app/Services/EsakipSyncService.php` — full file, 1336 lines.
  - `app/Livewire/Dashboard/SinkronData.php` — entry point.
  - `database/migrations/2026_*` — all sync-related migrations.
  - `database/seeders/OpdSeeder.php:59-90` — OPD reorganisasi data.
  - `database/seeders/MappingSeeder.php` — `is_n_minus_1` setting per bukti dukung.
  - `.sisyphus/docs/KNOWN_BUGS.md` — format reference (gunakan template Bug-XXX).

  **API/Type References**:
  - `processSync($tahunId, $opdId, $documentType, $progressCallback)` signature.
  - `previewSync($tahunId, $opdId, $documentType)` signature.
  - `syncDocumentForOpd($documentType, $documentLabel, $tahun, $opd)` signature.
  - `syncSharedDocument($documentType, $documentLabel, $tahun, $opdList)` signature.

  **WHY Each Reference Matters**:
  - Setiap issue HARUS punya citation file:line. Format `KNOWN_BUGS.md` adalah canonical template — ikuti.

  **Acceptance Criteria**:

  - [ ] File `.sisyphus/docs/SYNC_FLOW_ANALYSIS.md` exist.
  - [ ] File ≥300 baris.
  - [ ] Section 1 (Method Map) ada — minimal list 15 method dengan line numbers.
  - [ ] Section 2 (Feature Handling) ada — 2A, 2B, 2C terisi dengan citation.
  - [ ] Section 3 (Edge Cases Handled) ada.
  - [ ] Section 4 (Issues Found) ada — minimal 10 issue (SYNC-001 sampai SYNC-010+) dengan severity.
  - [ ] Section 5 (Recommendations) ada — minimal 5 rekomendasi.
  - [ ] Section 6 (References) ada.
  - [ ] Setiap issue punya file:line citation.
  - [ ] Format markdown valid (heading, table, code block).

  **QA Scenarios**:

  ```
  Scenario: Happy path - analysis is comprehensive
    Tool: Bash (PowerShell)
    Steps:
      1. Test-Path ".sisyphus/docs/SYNC_FLOW_ANALYSIS.md"  (expect True)
      2. (Get-Content ".sisyphus/docs/SYNC_FLOW_ANALYSIS.md" | Measure-Object -Line).Lines  (expect ≥300)
      3. Select-String -Path ".sisyphus/docs/SYNC_FLOW_ANALYSIS.md" -Pattern "## (Section|Method Map|Feature Handling|Issues Found|Recommendations|References)" | Measure-Object  (expect ≥6)
      4. Select-String -Path ".sisyphus/docs/SYNC_FLOW_ANALYSIS.md" -Pattern "SYNC-\d+" | Measure-Object  (expect ≥10)
      5. Select-String -Path ".sisyphus/docs/SYNC_FLOW_ANALYSIS.md" -Pattern "EsakipSyncService\.php:\d+" | Measure-Object  (expect ≥10 — citation per issue)
    Expected Result: All thresholds met.
    Evidence: .sisyphus/evidence/task-11-analysis-audit.txt

  Scenario: Negative - issues are not speculative (each has citation)
    Tool: Bash (PowerShell)
    Steps:
      1. Get-Content ".sisyphus/docs/SYNC_FLOW_ANALYSIS.md" | Select-String -Pattern "^### SYNC-" -Context 0,15  (look 15 lines after each issue heading)
      2. For each issue, verify ada line yang match pattern `\.php:\d+` dalam 15 line setelah heading.
    Expected Result: Setiap SYNC-XXX issue punya citation dalam blok-nya.
    Evidence: .sisyphus/evidence/task-11-citation-coverage.txt
  ```

  **Evidence to Capture**:
  - [ ] `.sisyphus/evidence/task-11-analysis-audit.txt`
  - [ ] `.sisyphus/evidence/task-11-citation-coverage.txt`

  **Commit**: NO (Commit 2 batch)

- [ ] 12. Cross-validate 5 sync docs konsisten

  **What to do**:
  - Setelah T6-T11 selesai, cross-check 5 dokumen + 1 analysis untuk konsistensi internal:
    1. Tidak ada lagi mention `SinkronDokumen` di 5 dokumen sync (T6-T10).
    2. Tidak ada lagi mention `sync_mode` di 5 dokumen.
    3. Mention `is_n_minus_1` ada di minimal 3 dari 6 file (T6, T7, T11 sebagai minimum).
    4. Mention `predecessor_opd_id` ada di minimal 3 dari 6 file.
    5. Mention `tahun_mulai_berlaku` ada di minimal 3 dari 6 file.
    6. Schema kolom `bukti_dukung`, `penilaian`, `riwayat_sinkron`, `opd` di `DATABASE_SINKRONISASI.md` (T7) match dengan migrations (no contradicts).
    7. Schema `link_file` JSON di `STRUKTUR_LINK_FILE.md` (T9) match dengan implementasi di `EsakipSyncService::buildFileObject` dan `LembarKerja::uploadBuktiDukung`.
    8. Smart merge logic di `SMART_SYNC_STRATEGY.md` (T8) match dengan `EsakipSyncService::smartMergeDocuments` actual signature.
    9. Issues di `SYNC_FLOW_ANALYSIS.md` (T11) tidak duplikat dengan `KNOWN_BUGS.md` (kecuali cross-reference yang dengan jelas mention).
    10. Cross-link working: doc satu menyebut doc lain dengan path benar.
  - Kalau ada inkonsistensi, FIX di file yang relevan (T12 boleh edit kembali T6-T11 outputs).
  - Tulis ringkasan validation result ke `.sisyphus/evidence/task-12-cross-validation.md`.

  **Must NOT do**:
  - JANGAN edit `EsakipSyncService.php` atau source code lain.
  - JANGAN tambah issue baru di `SYNC_FLOW_ANALYSIS.md` — hanya cross-validate.

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
    - Reason: Validation across multiple files — butuh effort signifikan tapi non-trivial.
  - **Skills**: []

  **Parallelization**:
  - **Can Run In Parallel**: NO (depends on Wave 1 + Wave 2 selesai)
  - **Parallel Group**: Wave 3 (single task)
  - **Blocks**: F1-F4
  - **Blocked By**: T1-T11

  **References**:

  **Pattern References**:
  - All 5 sync docs (PANDUAN, DATABASE, SMART_SYNC, STRUKTUR_LINK_FILE, TROUBLESHOOTING).
  - `.sisyphus/docs/SYNC_FLOW_ANALYSIS.md` (T11 output).
  - `.sisyphus/docs/KNOWN_BUGS.md` (existing).
  - `app/Services/EsakipSyncService.php`.
  - `database/migrations/2026_*`.

  **WHY Each Reference Matters**:
  - Cross-validation tidak boleh assume — setiap claim di 5 docs harus verifiable ke source.

  **Acceptance Criteria**:

  - [ ] File `.sisyphus/evidence/task-12-cross-validation.md` exist.
  - [ ] File contains: validation result per checkpoint (10 checkpoints listed above).
  - [ ] Setiap checkpoint marked `PASS` or `FAIL` (jika FAIL, diikuti fix description + diff).
  - [ ] Total `FAIL` count = 0 setelah fix selesai.

  **QA Scenarios**:

  ```
  Scenario: Happy path - all 10 checkpoints PASS
    Tool: Bash (PowerShell)
    Steps:
      1. Read .sisyphus/evidence/task-12-cross-validation.md
      2. Count "FAIL" markers - expect 0
      3. Count "PASS" markers - expect ≥10
    Expected Result: All checkpoints PASS.
    Evidence: .sisyphus/evidence/task-12-cross-validation.md

  Scenario: Negative - manual sanity check (open one doc, ctrl-F for SinkronDokumen)
    Tool: Bash (PowerShell)
    Steps:
      1. Select-String -Path "PANDUAN_SINKRONISASI_ESAKIP.md","DATABASE_SINKRONISASI.md","SMART_SYNC_STRATEGY.md","STRUKTUR_LINK_FILE.md","TROUBLESHOOTING_SINKRONISASI.md" -Pattern "SinkronDokumen" | Measure-Object  (expect 0)
      2. Select-String -Path "PANDUAN_SINKRONISASI_ESAKIP.md","DATABASE_SINKRONISASI.md","SMART_SYNC_STRATEGY.md","STRUKTUR_LINK_FILE.md","TROUBLESHOOTING_SINKRONISASI.md" -Pattern "sync_mode" | Measure-Object  (expect 0)
    Expected Result: Both 0.
    Evidence: .sisyphus/evidence/task-12-stale-refs.txt
  ```

  **Evidence to Capture**:
  - [ ] `.sisyphus/evidence/task-12-cross-validation.md`
  - [ ] `.sisyphus/evidence/task-12-stale-refs.txt`

  **Commit**: YES (Commit 3 setelah T12 selesai)
  - Message: `docs(sync): cross-validate sync documentation consistency, ensure no stale refs`
  - Files staged: file edit yang muncul dari fix di T12.
  - Pre-commit: re-run cross-validation — 10/10 PASS.

---

## Final Verification Wave (MANDATORY — after ALL implementation tasks)

> 4 review agents run in PARALLEL. ALL must APPROVE. Present consolidated results to user and get explicit "okay" before completing.
>
> **Do NOT auto-proceed after verification. Wait for user's explicit approval before marking work complete.**
> **Never mark F1-F4 as checked before getting user's okay.** Rejection or user feedback -> fix -> re-run -> present again -> wait for okay.

- [ ] F1. **Plan Compliance Audit** — `oracle`
  Read this plan end-to-end. For each "Must Have": verify implementation exists. For each "Must NOT Have": search codebase for forbidden patterns — reject with file:line if found. Check evidence files exist in `.sisyphus/evidence/`. Compare deliverables against plan's "Concrete Deliverables" section.
  Output: `Must Have [N/N] | Must NOT Have [N/N] | Tasks [N/N] | VERDICT: APPROVE/REJECT`

- [ ] F2. **Code Quality + Integrity Review** — `unspecified-high`
  Run `composer dev` (background-start, then kill after smoke). Verify `php artisan migrate:fresh --seed` exit 0. Run `php artisan route:list` and grep — sinkron-data PRESENT, sinkron-dokumen ABSENT. Grep entire `app/` + `resources/views/` for `SinkronDokumen` (string), `sync_mode` (string), `JenisNilai\b` (only seeder file ref allowed). Inspect `.gitignore` — `/tmp/*` entry present.
  Output: `Migrate [PASS/FAIL] | Routes [PASS/FAIL] | Refs [N clean/N issues] | Gitignore [PASS/FAIL] | VERDICT`

- [ ] F3. **Real Manual QA via Playwright + curl** — `unspecified-high` (+ `playwright` skill)
  Start fresh `php artisan serve`. Login as admin (admin@sakip.com / password). Navigate to `/sinkron-data` — verify page renders, no JS console error, dropdown OPD populated, dropdown tahun populated. Try GET `/sinkron-dokumen` directly — expect 404 (route not registered). Verify by curl: `curl -I http://localhost:8000/sinkron-dokumen` should return 4xx or redirect (NOT 200). Run sync preview against a real OPD/tahun (mock or real). Save screenshot.
  Output: `SinkronData renders [PASS/FAIL] | SinkronDokumen 404 [PASS/FAIL] | Sync preview works [PASS/FAIL] | VERDICT`

- [ ] F4. **Scope Fidelity Check** — `deep`
  For each task in this plan: read "What to do", read actual diff (git diff --stat for moved/deleted files, git diff for edited docs). Verify 1:1 — everything in spec was built, nothing beyond spec was built. Check "Must NOT do" compliance: NO migration changes, NO PHP source edits in `app/Services/` or `app/Livewire/`, NO AGENTS.md edits, NO new dependencies, NO routes/web.php edits. Detect cross-task contamination (e.g. T6 also editing `DATABASE_SINKRONISASI.md` which is T7's territory).
  Output: `Tasks [N/N compliant] | Contamination [CLEAN/N issues] | Forbidden changes [CLEAN/N issues] | VERDICT`

---

## Commit Strategy

> **PERHATIAN GIT REALITY**: Karena `tmp/*` di-gitignore, "move" 14 scratch scripts dari git's perspective adalah **14 deletions dari root**. File fisik tetap ada di disk (di `tmp/`) tapi git tidak track. Commit message refleksikan ini.

Single commit per logical group, in dependency order:

- **Commit 1** (after Wave 1): `chore: remove dead code (scratch scripts, orphan seeder, dead Livewire SinkronDokumen)`
  - Files staged (git's perspective):
    - **DELETED** (14 files): `check-kriteria.php`, `check-linkfile-structure.php`, `check_schema.php`, `debug_rpjmd.php`, `syncPenilaian_NEW_TEMPLATE.php`, `test-is-perubahan.php`, `test-preview-shared.php`, `test-skip-upload.php`, `test-sync-shared.php`, `test-sync.php`, `test-validate-rpjmd.php`, `validate-is-perubahan.php`, `validate-multiple-files.php`, `validate-sync.php`
    - **DELETED** (3 files): `database/seeders/JenisNilai.php`, `app/Livewire/Dashboard/SinkronDokumen.php`, `resources/views/livewire/dashboard/sinkron-dokumen.blade.php`
    - **MODIFIED**: `.gitignore` (tambah `/tmp/*` dan `!/tmp/.gitkeep`)
    - **ADDED**: `tmp/.gitkeep` (empty file)
  - Files NOT staged (still on disk, gitignored): `tmp/test-*.php`, `tmp/validate-*.php`, `tmp/check-*.php`, `tmp/debug_*.php`, `tmp/syncPenilaian_NEW_TEMPLATE.php`
  - Pre-commit verification: `php artisan migrate:fresh --seed` exit 0 AND `php artisan route:list | Select-String "sinkron-data"` returns ≥1 match AND `git status` shows `tmp/` files as untracked-and-ignored.
  - Commit body example:
    ```
    chore: remove dead code (scratch scripts, orphan seeder, dead Livewire SinkronDokumen)
    
    - Removed 14 root-level scratch PHP scripts (test-*, validate-*, check-*, debug_*).
      Files preserved at tmp/ (gitignored) for local debugging if needed.
    - Removed orphan database/seeders/JenisNilai.php (was empty stub, never called by
      DatabaseSeeder which uses JenisNilaiSeeder.php).
    - Removed dead Livewire component SinkronDokumen + its view (unrouted, replaced
      by SinkronData component which is the active sync UI).
    - Added /tmp/* to .gitignore to allow local scratch directory without polluting
      project root.
    
    Verification: php artisan migrate:fresh --seed passes, route:list shows
    sinkron-data registered, sinkron-dokumen route absent.
    ```
  
- **Commit 2** (after Wave 2): `docs(sync): update 5 sync docs + add SYNC_FLOW_ANALYSIS, document new features (is_n_minus_1, predecessor_opd_id, tahun_mulai_berlaku)`
  - Files staged:
    - **MODIFIED**: `PANDUAN_SINKRONISASI_ESAKIP.md` (full rewrite)
    - **MODIFIED**: `DATABASE_SINKRONISASI.md` (add Tabel opd section + 3 new columns)
    - **MODIFIED**: `SMART_SYNC_STRATEGY.md` (remove $syncMode, add Reorganisasi section)
    - **MODIFIED**: `STRUKTUR_LINK_FILE.md` (verify schema accurate)
    - **MODIFIED**: `TROUBLESHOOTING_SINKRONISASI.md` (add OPD baru + N-1 scenarios)
    - **ADDED**: `.sisyphus/docs/SYNC_FLOW_ANALYSIS.md` (new analysis doc)
  - Pre-commit verification: 
    - `Select-String -Path PANDUAN_SINKRONISASI_ESAKIP.md,DATABASE_SINKRONISASI.md,SMART_SYNC_STRATEGY.md,STRUKTUR_LINK_FILE.md,TROUBLESHOOTING_SINKRONISASI.md -Pattern "SinkronDokumen|sync_mode|\$syncMode" | Measure-Object` returns Count=0
    - `Test-Path .sisyphus/docs/SYNC_FLOW_ANALYSIS.md` returns True
  - Commit body example:
    ```
    docs(sync): update 5 sync docs + add SYNC_FLOW_ANALYSIS, document new features
    
    Old sync docs were written when SinkronDokumen component was active and
    sync_mode (merge/replace/skip) was a thing. Both have been removed/replaced.
    Docs were also missing 3 features added since: is_n_minus_1 (n-1 documents),
    predecessor_opd_id + tahun_mulai_berlaku (OPD reorganisasi).
    
    Updates:
    - PANDUAN_SINKRONISASI_ESAKIP.md: full rewrite. Refers SinkronData (not
      SinkronDokumen). Removes sync_mode picker. Adds OPD reorganisasi section,
      N-1 documents section, smart-merge default behavior.
    - DATABASE_SINKRONISASI.md: adds Tabel opd section. Adds bukti_dukung.is_n_minus_1.
      Refreshes riwayat_sinkron schema (rename dokumen→document_type, add
      no_document status enum).
    - SMART_SYNC_STRATEGY.md: removes $syncMode parameter (no longer in code).
      Adds Source-Based Skip Protection section + OPD Reorganisasi section.
    - STRUKTUR_LINK_FILE.md: cross-validates JSON schema against
      EsakipSyncService::buildFileObject and LembarKerja::uploadBuktiDukung.
    - TROUBLESHOOTING_SINKRONISASI.md: adds 3 new diagnostic scenarios.
    - .sisyphus/docs/SYNC_FLOW_ANALYSIS.md: NEW analysis document with method map,
      feature handling for 3 new features, and minimum 10 issues found with
      file:line citations.
    ```

- **Commit 3** (after Wave 3): `docs(sync): cross-validate sync documentation consistency`
  - Files staged: any incidental fixes from T12 cross-validation.
  - Pre-commit verification: re-run cross-validation script — 10/10 PASS.
  - Hanya buat commit jika T12 menghasilkan diff. Jika T12 tidak menemukan masalah, skip Commit 3.

### Note: AGENTS.md Stale References (FOLLOW-UP BATCH)

Setelah Commit 1, references ke `SinkronDokumen` di `AGENTS.md` (root, line 121) dan `app/Livewire/AGENTS.md` (lines 26, 46) menjadi stale. Plan ini DENGAN SENGAJA tidak update kedua file tersebut karena sebelumnya guardrail ditetapkan tidak edit AGENTS.md di session sebelumnya. **Buat batch terpisah** (after this one merged) untuk:
- Update root AGENTS.md GOTCHAS section, hapus baris tentang SinkronDokumen
- Update app/Livewire/AGENTS.md, hapus mention SinkronDokumen.php

Ini akan dilakukan di Cleanup Batch 2 atau dedicated mini-task.

---

## Success Criteria

### Verification Commands

```powershell
# Root cleanup
(Get-ChildItem -File -Filter *.php -LiteralPath ".").Count           # Expect: 0 (or 1 only if artisan moved here, which it shouldn't be)
Test-Path "tmp/.gitkeep"                                              # Expect: True
(Get-ChildItem -File -LiteralPath "tmp" | Where-Object { $_.Name -ne ".gitkeep" }).Count  # Expect: 14

# Deletions
Test-Path "database/seeders/JenisNilai.php"                          # Expect: False
Test-Path "database/seeders/JenisNilaiSeeder.php"                    # Expect: True (yang benar tetap)
Test-Path "app/Livewire/Dashboard/SinkronDokumen.php"                # Expect: False
Test-Path "resources/views/livewire/dashboard/sinkron-dokumen.blade.php"  # Expect: False

# Code references
Select-String -Path "app/**/*.php","resources/views/**/*.blade.php","routes/*.php" -Pattern "SinkronDokumen" -SimpleMatch | Measure-Object  # Expect: Count = 0

# Migrate + route still works
php artisan migrate:fresh --seed                                     # Expect: exit 0
php artisan route:list | Select-String "sinkron-data"                # Expect: 1 match
php artisan route:list | Select-String "sinkron-dokumen"             # Expect: 0 matches

# Documentation has new features mentioned
Select-String -Path "PANDUAN_SINKRONISASI_ESAKIP.md","DATABASE_SINKRONISASI.md" -Pattern "is_n_minus_1|predecessor_opd_id|tahun_mulai_berlaku" | Measure-Object  # Expect: Count >= 6 (≥2 mentions per file)

# Documentation has no stale references
Select-String -Path "PANDUAN_SINKRONISASI_ESAKIP.md","DATABASE_SINKRONISASI.md","SMART_SYNC_STRATEGY.md","STRUKTUR_LINK_FILE.md","TROUBLESHOOTING_SINKRONISASI.md" -Pattern "SinkronDokumen|\$syncMode|sync_mode" | Measure-Object  # Expect: Count = 0

# Analysis doc exists
Test-Path ".sisyphus/docs/SYNC_FLOW_ANALYSIS.md"                     # Expect: True
((Get-Content ".sisyphus/docs/SYNC_FLOW_ANALYSIS.md" | Measure-Object -Line).Lines) -ge 300  # Expect: True

# Server still starts
# (Manual: composer dev → no error)
```

### Final Checklist

- [ ] All "Must Have" present
- [ ] All "Must NOT Have" absent
- [ ] All file ops verified (Test-Path)
- [ ] migrate:fresh --seed passes
- [ ] route:list correct
- [ ] No code references to deleted classes
- [ ] 5 sync docs updated, 1 analysis doc created
- [ ] User okay'd final review
