# Plan A — Rekap Features Extension

## TL;DR

> **Quick Summary**: Extend fitur Rekap Penolakan + Rekap Perbaikan untuk role `penjamin` & `penilai`, tambah filter OPD di kedua rekap, dan buat menu BARU "Rekap Verifikasi" untuk role `verifikator` (lihat dokumen yang sudah diverifikasi vs belum).
>
> **Deliverables**:
> - `RekapPenolakan` extended ke role `penjamin` & `penilai`, plus filter OPD
> - `RekapPerbaikan` tambah filter OPD
> - NEW: `RekapVerifikasi` Livewire component + view + route + sidebar link
> - Sidebar update untuk role-based visibility
> - Badge count update di sidebar untuk role baru
>
> **Estimated Effort**: Medium
> **Parallel Execution**: YES — 2 wave (3 task wave 1 paralel, 2 task wave 2 sequential)
> **Critical Path**: Wave 1 component creation → Wave 2 sidebar/route wiring → Final verification

---

## Context

### Original Request

User minta:
1. **Fitur Rekap Penolakan**: tampilkan juga rekap perbaikan dan penolakan untuk role `penilai` & `penjamin` (saat ini hanya OPD)
2. **Filter OPD** di Rekap Penolakan + Rekap Perbaikan (memudahkan pencarian)
3. **NEW MENU "Rekap Verifikasi"**: untuk verifikator melihat status bukti dukung yang sudah diupload OPD — yang sudah diverifikasi vs belum diverifikasi olehnya

### Interview Summary

**Decisions confirmed**:
- Split 2 plan terpisah agar safer (Plan A: fitur baru, Plan B: bug fix history)
- Set halaman (aduan #6) SKIP — tidak terkonfirmasi sebagai bug
- Dilakukan dengan hati-hati tanpa mengganggu flow existing

**Investigasi findings**:
- `RekapPenolakan` ([line 41](file:///C:/laragon/www/kke-sakip/app/Livewire/Dashboard/RekapPenolakan.php#L41)): hanya OPD, query filter `verifikator + penjamin` roles → perbaikan: extend ke include semua role yang butuh visibilitas
- `RekapPerbaikan` ([line 44](file:///C:/laragon/www/kke-sakip/app/Livewire/Dashboard/RekapPerbaikan.php#L44)): sudah include `verifikator + penjamin + penilai` (hanya melihat penolakan SENDIRI) — yang perlu cuma tambah filter OPD
- Sidebar di [app.blade.php:305-365](file:///C:/laragon/www/kke-sakip/resources/views/components/layouts/app.blade.php#L305-L365) gate menu berdasarkan role
- TIDAK ADA komponen `RekapVerifikasi` saat ini

**Semantic clarification untuk Aduan #1**:

User bilang "rekap penolakan untuk penjamin dan penilai". Interpretasi paling logis:
- **OPD** lihat "penolakan" yang **mereka terima** (dari verifikator/penjamin/penilai). Sudah ada.
- **Penjamin** lihat "penolakan" yang **mereka buat** (terhadap OPD) — sudah ada via RekapPerbaikan setelah OPD perbaiki. Tapi user mungkin minta visibility lebih lebar: penjamin lihat penolakan dari **DIRI SENDIRI + dari verifikator** (sebagai overview kerja).
- **Penilai** sama dengan penjamin.

**Keputusan**: Saya interpretasikan sebagai "**Penjamin & penilai juga butuh page Rekap Penolakan dengan filter OPD untuk monitoring penolakan terhadap OPD**". Filter role di query: 
- OPD → filter penolakan TERHADAP opd_id mereka
- Penjamin/penilai → filter penolakan dari role lebih bawah (verifikator) ATAU dari semua role yang relevan (lihat semua aktivitas)
- Filter OPD: dropdown opsional untuk narrow data

### Metis Review

**Identified Gaps**:
- ⚠️ Klarifikasi semantic: "rekap penolakan untuk penjamin/penilai" bisa multi-interpretasi. Saya pilih interpretasi paling logis (visibility wider) tapi tetap konfirmasi via question tool BEFORE deploy.
- ⚠️ Filter OPD: untuk role OPD (yang opd-nya fixed), filter OPD tidak relevan. Hide untuk OPD.
- ⚠️ Badge count di sidebar: untuk role baru yang sekarang akses Rekap Penolakan, perlu badge logic.
- ⚠️ Rekap Verifikasi scope: "yang sudah diverifikasi vs belum" — perlu jelas: belum diverifikasi = OPD sudah upload tapi verifikator belum action? Atau termasuk yang OPD belum upload sama sekali? **Saya pilih: hanya yang sudah diupload OPD** (filter `link_file IS NOT NULL`).

---

## Work Objectives

### Core Objective

Tambah role-based visibility untuk fitur rekap, dengan filter OPD yang konsisten, dan buat menu baru untuk verifikator agar bisa monitor pekerjaan verifikasinya.

### Concrete Deliverables

**Modifikasi 2 Livewire Components**:
- `app/Livewire/Dashboard/RekapPenolakan.php` — extend role allowed (tambah `penjamin`, `penilai`), tambah `selected_opd` filter property + computed
- `app/Livewire/Dashboard/RekapPerbaikan.php` — tambah `selected_opd` filter property + computed

**NEW Livewire Component**:
- `app/Livewire/Dashboard/RekapVerifikasi.php` — komponen baru untuk verifikator
- `resources/views/livewire/dashboard/rekap-verifikasi.blade.php` — view baru

**Modifikasi Existing**:
- `resources/views/livewire/dashboard/rekap-penolakan.blade.php` — tambah filter OPD dropdown (untuk role non-OPD), opsional show OPD column
- `resources/views/livewire/dashboard/rekap-perbaikan.blade.php` — tambah filter OPD dropdown
- `resources/views/components/layouts/app.blade.php` — update sidebar gate untuk Rekap Penolakan + tambah link Rekap Verifikasi
- `routes/web.php` — register `/rekap-verifikasi` route

### Definition of Done

- [ ] Login sebagai role `penjamin` → bisa akses `/rekap-penolakan` dan lihat data
- [ ] Login sebagai role `penilai` → bisa akses `/rekap-penolakan` dan lihat data
- [ ] Login sebagai role `opd` → masih bisa akses `/rekap-penolakan` dengan data OPD-nya saja
- [ ] Filter OPD muncul di Rekap Penolakan (untuk role non-OPD) dan Rekap Perbaikan
- [ ] Filter OPD bekerja: pilih OPD → list ter-filter
- [ ] Login sebagai role `verifikator_*` → muncul menu "Rekap Verifikasi" di sidebar
- [ ] Klik Rekap Verifikasi → tampil list bukti dukung yang OPD sudah upload, dengan kolom status (sudah/belum diverifikasi oleh user)
- [ ] Filter OPD bekerja di Rekap Verifikasi
- [ ] Badge count di sidebar untuk Rekap Penolakan tetap akurat untuk semua role
- [ ] `php artisan migrate:fresh --seed` masih jalan (tidak ada DB change diharapkan)
- [ ] Existing flow OPD di RekapPenolakan TIDAK berubah behavior (regression test)

### Must Have

- Backward compatible: behavior existing untuk role OPD tidak berubah
- Filter OPD WAJIB untuk role non-OPD (untuk OPD: hide karena hanya 1 OPD)
- Visual consistency: filter UI mirip dengan dropdown OPD di Lembar Kerja / Monitoring
- Pagination kalau data > 20 row (gunakan WithPagination trait)

### Must NOT Have (Guardrails)

- **JANGAN** ubah skema database. Tidak ada migration di plan ini.
- **JANGAN** edit `app/Services/EsakipSyncService.php` atau model.
- **JANGAN** edit komponen lain (LembarKerja, Mapping, Pengaturan, Monitoring).
- **JANGAN** ubah `getActionDescription()` atau `recordHistory()` di history (itu Plan B).
- **JANGAN** sentuh fitur Set Halaman atau Upload (out of scope).
- **JANGAN** delete file existing (hanya tambah/edit yang relevan).
- **JANGAN** introduce new dependency (composer/npm).
- **JANGAN** ubah AGENTS.md atau dokumen di `.sisyphus/docs/`.
- **JANGAN** edit `EsakipSyncService.php` atau migration manapun.
- **JANGAN** rubah behavior route protection di middleware level.

---

## Verification Strategy

> **ZERO HUMAN INTERVENTION** — semua verifikasi agent-executable.

### Test Decision

- **Infrastructure exists**: YES (PHPUnit, but only stub).
- **Automated tests**: NO (project tidak punya real test suite).
- **Strategy**: Verify via `migrate:fresh --seed` + `route:list` + Playwright manual page render dengan login per-role + DB seed test data.

### QA Policy

Setiap task wajib include QA Scenarios — agent verifies via:
- File existence/non-existence (Test-Path)
- Code reference checks (Select-String for specific patterns)
- DB state via php artisan tinker
- Page render via Playwright (login as different roles, navigate, screenshot)

Evidence saved ke `.sisyphus/evidence/plan-a-task-{N}-{slug}.{ext}`.

---

## Execution Strategy

### Parallel Execution Waves

```
Wave 1 (Component creation, MAX PARALLEL — 3 independent components):
├── Task 1: Modify RekapPenolakan.php (extend role + filter OPD)             [unspecified-high]
├── Task 2: Modify RekapPerbaikan.php (add filter OPD)                       [quick]
└── Task 3: Create RekapVerifikasi.php + view (NEW component)                [unspecified-high]

Wave 2 (View + Route wiring, sequential — depends Wave 1):
├── Task 4: Update views rekap-penolakan + rekap-perbaikan (filter OPD UI)   [writing]   (depends T1, T2)
└── Task 5: Update routes/web.php + sidebar app.blade.php                    [unspecified-high]   (depends T1, T2, T3)

Wave FINAL (3 parallel reviews + user okay):
├── Task F1: Plan compliance audit (oracle)                                  
├── Task F2: Multi-role manual QA via Playwright (admin, opd, penjamin, penilai, verifikator)
└── Task F3: Scope fidelity check (deep)
-> Present results -> Get user okay
```

### Dependency Matrix

- **T1**: — Depends: none. Blocks: T4, T5.
- **T2**: — Depends: none. Blocks: T4, T5.
- **T3**: — Depends: none. Blocks: T5.
- **T4**: — Depends: T1, T2. Blocks: F1-F3.
- **T5**: — Depends: T1, T2, T3. Blocks: F1-F3.
- **F1-F3**: — Depends: T1-T5. Blocks: user okay.

### Agent Dispatch Summary

- **Wave 1**: **3 tasks** — T1, T3 → `unspecified-high`, T2 → `quick`
- **Wave 2**: **2 tasks** — T4 → `writing`, T5 → `unspecified-high`
- **FINAL**: **3 tasks** — F1 → `oracle`, F2 → `unspecified-high`, F3 → `deep`

---

## TODOs

- [x] 1. Modify `app/Livewire/Dashboard/RekapPenolakan.php` (extend role + filter OPD)

  **What to do**:
  - Open `app/Livewire/Dashboard/RekapPenolakan.php` (lines ~1-110).
  - **Extend role access**: ubah computed `rekapPenolakan()` line 37-68 dan `badgeCount()` line 70-100:
    - HAPUS guard `if (Auth::user()->role->jenis !== 'opd') return collect();` di line 41
    - Ganti dengan branching per-role:
      ```php
      $jenis = Auth::user()->role->jenis;
      if (!in_array($jenis, ['opd', 'penjamin', 'penilai'])) {
          return collect();
      }
      ```
    - Untuk role `opd`: filter `where('opd_id', Auth::user()->opd_id)` (existing behavior preserved)
    - Untuk role `penjamin`/`penilai`: TIDAK filter `opd_id` by default, tapi filter berdasarkan `$this->selected_opd` jika set
    - Filter `whereIn('role_id', $roleIds)` tetap ambil verifikator + penjamin (untuk `opd`), atau bisa lihat semua role penolakan (untuk `penjamin`/`penilai`)
  - **Tambah filter OPD property + listener**:
    - Property baru: `public $selected_opd = null;` setelah `$selectedKeterangan` (line 17)
    - Property `public $searchOpd = '';` untuk search di dropdown
    - Computed `opdList()` returning `Opd::orderBy('nama')->get()` untuk dropdown options
    - Method `updatedSelectedOpd()` untuk reset state saat filter berubah
  - **Update query** untuk apply filter `selected_opd`:
    - Jika role `opd` → `opd_id = Auth::user()->opd_id` (forced)
    - Jika role lain DAN `selected_opd` tidak null → `where('opd_id', $this->selected_opd)`
    - Jika role lain DAN `selected_opd` null → tidak filter OPD (lihat semua)
  - **PENTING — preserve existing behavior**:
    - Behavior untuk role `opd` 100% sama dengan sebelumnya (data ter-filter sesuai user's opd_id)
    - Tahun filter via `tahun_session` masih ada
    - `status_perbaikan IN ['belum_diperbaiki', 'sudah_diperbaiki']` filter tetap

  **Must NOT do**:
  - JANGAN ubah method `showKeterangan()` (sudah benar)
  - JANGAN ubah session keys atau navigation
  - JANGAN ubah view di task ini (itu T4)
  - JANGAN edit komponen lain

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
    - Reason: Logic edit dengan multiple branching + role gating + backward compat. Bukan trivial.
  - **Skills**: []

  **Parallelization**:
  - **Can Run In Parallel**: YES dengan T2, T3
  - **Parallel Group**: Wave 1
  - **Blocks**: T4, T5
  - **Blocked By**: None

  **References**:

  **Pattern References**:
  - `app/Livewire/Dashboard/Monitoring.php:74-87` — pattern dropdown OPD untuk role non-opd
  - `app/Livewire/Dashboard/LembarKerja.php:365-377` — pattern `opdList()` computed
  - `app/Livewire/Dashboard/RekapPerbaikan.php:40-66` — existing rekapPerbaikan logic (untuk konsistensi)
  - `.sisyphus/docs/ROLES.md` — permission matrix untuk role mapping

  **WHY Each Reference Matters**:
  - Monitoring.php sudah punya pattern role-based opd filter — ikut pola yang sama agar konsisten.
  - RekapPerbaikan structure mirip — gunakan referensi untuk maintain symmetry.

  **Acceptance Criteria**:

  - [ ] File `RekapPenolakan.php` masih syntactically valid PHP
  - [ ] Property `selected_opd` exists (grep `selected_opd`)
  - [ ] Computed `opdList` exists
  - [ ] Computed `rekapPenolakan` punya branching `in_array($jenis, ['opd','penjamin','penilai'])`
  - [ ] Computed `rekapPenolakan` apply filter berdasarkan `selected_opd` jika set
  - [ ] `php artisan migrate:fresh --seed` exit 0 (test compile)
  - [ ] Login as OPD → `/rekap-penolakan` masih render data OPD-nya saja (regression)

  **QA Scenarios**:

  ```
  Scenario: Happy path - role penjamin login akses rekap penolakan
    Tool: Bash (PowerShell + tinker)
    Preconditions: User penjamin exists (UserSeeder line 53), kondisi PenilaianHistory dengan is_verified=0 ada
    Steps:
      1. composer dump-autoload
      2. php artisan tinker --execute="App\Models\User::where('email', 'evaluator@inspektorat.com')->first()" (verify exists)
      3. php artisan route:list | grep rekap-penolakan
    Expected Result: Route registered, no syntax error
    Evidence: .sisyphus/evidence/plan-a-task-1-php-compile.txt

  Scenario: Negative - filter OPD tidak diterapkan untuk role opd
    Tool: Bash (PowerShell)
    Steps:
      1. Read RekapPenolakan.php
      2. Verify code path: if jenis=='opd' → opd_id = Auth::user()->opd_id (forced, ignore $selected_opd)
      3. Verify code path: if jenis!='opd' AND $selected_opd → opd_id = $selected_opd
    Expected Result: Code paths exist as described
    Evidence: .sisyphus/evidence/plan-a-task-1-code-review.txt
  ```

  **Evidence to Capture**:
  - [ ] `.sisyphus/evidence/plan-a-task-1-php-compile.txt`
  - [ ] `.sisyphus/evidence/plan-a-task-1-code-review.txt`

  **Commit**: NO (akan masuk Commit 1 setelah Wave 2)

- [x] 2. Modify `app/Livewire/Dashboard/RekapPerbaikan.php` (add filter OPD)

  **What to do**:
  - Open `app/Livewire/Dashboard/RekapPerbaikan.php` (lines ~1-110).
  - **Tambah filter OPD property**:
    - Property baru: `public $selected_opd = null;` setelah `$selectedPenolakan` (line 16)
    - Property `public $searchOpd = '';`
  - **Tambah computed `opdList()`**:
    - Return `Opd::orderBy('nama')->get()` (untuk dropdown).
    - Pastikan ada `use App\Models\Opd;` di top of file
  - **Method `updatedSelectedOpd()`** untuk handler perubahan filter
  - **Update computed `rekapPerbaikan()` line 40-66**:
    - Tambah filter: jika `$this->selected_opd` not null → `where('opd_id', $this->selected_opd)`
  - **Update computed `badgeCount()` line 68-100**:
    - Tambah filter: jika `$this->selected_opd` not null → `where('opd_id', $this->selected_opd)`
  - **PRESERVE existing behavior**:
    - Filter `where('role_id', Auth::user()->role_id)` tetap (user lihat penolakan SENDIRI)
    - Filter `status_perbaikan = 'sudah_diperbaiki'` tetap
    - Filter `tahun_id` via tahun_session tetap

  **Must NOT do**:
  - JANGAN ubah `allowedRoles` array (sudah benar: verifikator, penjamin, penilai)
  - JANGAN ubah session keys
  - JANGAN ubah view (itu T4)

  **Recommended Agent Profile**:
  - **Category**: `quick`
    - Reason: Tambah filter property + condition di query. Trivial.
  - **Skills**: []

  **Parallelization**:
  - **Can Run In Parallel**: YES dengan T1, T3
  - **Parallel Group**: Wave 1
  - **Blocks**: T4
  - **Blocked By**: None

  **References**:

  **Pattern References**:
  - `app/Livewire/Dashboard/RekapPenolakan.php` (after T1) — pattern filter OPD yang akan di-implement
  - `app/Livewire/Dashboard/Monitoring.php:74-87` — pattern dropdown OPD

  **Acceptance Criteria**:

  - [ ] Property `selected_opd` exists (grep)
  - [ ] Computed `opdList` exists
  - [ ] Filter OPD diterapkan di `rekapPerbaikan` dan `badgeCount`
  - [ ] `php artisan migrate:fresh --seed` exit 0
  - [ ] Tahun filter masih ada (regression)

  **QA Scenarios**:

  ```
  Scenario: Happy path - filter OPD bekerja
    Tool: Bash (PowerShell)
    Steps:
      1. Read RekapPerbaikan.php
      2. Verify property `selected_opd` declared
      3. Verify computed apply where('opd_id', $this->selected_opd) when not null
    Expected Result: All checks pass
    Evidence: .sisyphus/evidence/plan-a-task-2-code-review.txt
  ```

  **Evidence to Capture**:
  - [ ] `.sisyphus/evidence/plan-a-task-2-code-review.txt`

  **Commit**: NO

- [x] 3. Create NEW `app/Livewire/Dashboard/RekapVerifikasi.php` + view

  **What to do**:
  - **Buat file baru**: `app/Livewire/Dashboard/RekapVerifikasi.php`
  - Class structure:
    ```php
    namespace App\Livewire\Dashboard;
    
    use App\Models\Penilaian;
    use App\Models\Opd;
    use App\Models\Role;
    use App\Models\BuktiDukung;
    use Illuminate\Support\Facades\Auth;
    use Livewire\Attributes\Computed;
    use Livewire\Attributes\Session;
    use Livewire\Component;
    use Livewire\WithPagination;
    use Livewire\WithoutUrlPagination;
    
    class RekapVerifikasi extends Component
    {
        use WithPagination, WithoutUrlPagination;
        protected $paginationTheme = 'bootstrap';
        
        #[Session(key: 'tahun_session')]
        public $tahun_session;
        
        public $selected_opd = null;
        public $filter_status = 'semua'; // 'semua' | 'sudah' | 'belum'
        public $perPage = 20;
        
        public function updatedSelectedOpd() { $this->resetPage(); }
        public function updatedFilterStatus() { $this->resetPage(); }
        
        #[Computed]
        public function opdList() {
            return Opd::orderBy('nama')->get();
        }
        
        #[Computed]
        public function rekapVerifikasi() {
            // Hanya verifikator
            if (Auth::user()->role->jenis !== 'verifikator') {
                return collect();
            }
            
            $verifikatorRoleId = Auth::user()->role_id;
            $opdRoleId = Role::where('jenis', 'opd')->first()?->id;
            
            // Bukti dukung yang assigned ke verifikator ini (role_id match)
            $buktiDukungIds = BuktiDukung::where('role_id', $verifikatorRoleId)
                ->where('tahun_id', $this->tahun_session)
                ->pluck('id');
            
            // Penilaian OPD yang punya file di bukti dukung tersebut
            $query = Penilaian::with(['opd', 'bukti_dukung.kriteria_komponen.sub_komponen.komponen'])
                ->whereIn('bukti_dukung_id', $buktiDukungIds)
                ->where('role_id', $opdRoleId)
                ->whereNotNull('link_file');
            
            if ($this->selected_opd) {
                $query->where('opd_id', $this->selected_opd);
            }
            
            $opdPenilaianList = $query->get();
            
            // Map status: untuk setiap OPD penilaian, cek apakah ada Penilaian verifikator dengan is_verified
            $result = $opdPenilaianList->map(function($p) use ($verifikatorRoleId) {
                $verifPenilaian = Penilaian::where('kriteria_komponen_id', $p->kriteria_komponen_id)
                    ->where('opd_id', $p->opd_id)
                    ->where('bukti_dukung_id', $p->bukti_dukung_id)
                    ->where('role_id', $verifikatorRoleId)
                    ->whereNotNull('is_verified')
                    ->first();
                
                $p->verifikasi_status = $verifPenilaian
                    ? ($verifPenilaian->is_verified ? 'disetujui' : 'ditolak')
                    : 'belum_diverifikasi';
                $p->verifikasi_keterangan = $verifPenilaian?->keterangan;
                $p->verifikasi_tanggal = $verifPenilaian?->updated_at;
                
                return $p;
            });
            
            // Apply filter status
            if ($this->filter_status === 'sudah') {
                $result = $result->filter(fn($p) => in_array($p->verifikasi_status, ['disetujui', 'ditolak']));
            } elseif ($this->filter_status === 'belum') {
                $result = $result->filter(fn($p) => $p->verifikasi_status === 'belum_diverifikasi');
            }
            
            return $result->values();
        }
        
        #[Computed]
        public function statsCount() {
            $all = $this->rekapVerifikasi;
            return [
                'total' => count($all),
                'sudah' => $all->filter(fn($p) => $p->verifikasi_status !== 'belum_diverifikasi')->count(),
                'belum' => $all->filter(fn($p) => $p->verifikasi_status === 'belum_diverifikasi')->count(),
            ];
        }
        
        public function render() {
            return view('livewire.dashboard.rekap-verifikasi');
        }
    }
    ```
  - **Buat file view baru**: `resources/views/livewire/dashboard/rekap-verifikasi.blade.php`
    - Struktur mirip dengan `rekap-penolakan.blade.php` dan `rekap-perbaikan.blade.php`
    - Tambah filter OPD dropdown + filter status (semua/sudah/belum) di card-header
    - Stats cards di atas tabel (total, sudah verifikasi, belum verifikasi)
    - Tabel kolom: No, OPD, Komponen, Sub Komponen, Kriteria, Bukti Dukung, Status Verifikasi (badge), Tanggal, Keterangan
    - Status badge:
      - "disetujui" → green badge
      - "ditolak" → red badge
      - "belum_diverifikasi" → yellow/warning badge

  **Must NOT do**:
  - JANGAN edit komponen lain
  - JANGAN tambah migration
  - JANGAN ubah RekapPenolakan/RekapPerbaikan logic dari task ini

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
    - Reason: Buat komponen baru full + view + logic role gating. Substansial.
  - **Skills**: []

  **Parallelization**:
  - **Can Run In Parallel**: YES dengan T1, T2
  - **Parallel Group**: Wave 1
  - **Blocks**: T5
  - **Blocked By**: None

  **References**:

  **Pattern References**:
  - `app/Livewire/Dashboard/RekapPenolakan.php` — full pattern Livewire component (mount, computed, render)
  - `app/Livewire/Dashboard/Monitoring.php` — pagination + opdList pattern
  - `resources/views/livewire/dashboard/rekap-penolakan.blade.php` — view structure pattern
  - `app/Models/Penilaian.php` — query model
  - `app/Models/BuktiDukung.php:role_id` — kolom yang menentukan verifikator subtype owns scoring it
  - `.sisyphus/docs/ROLES.md` — verifikator role behavior

  **WHY Each Reference Matters**:
  - Pattern view + component harus mirip dengan rekap lain agar konsisten visual
  - `bukti_dukung.role_id` filter yang KRITIS — verifikator hanya lihat bukti dukung yang assigned ke role_id-nya

  **Acceptance Criteria**:

  - [ ] File `RekapVerifikasi.php` exist + valid PHP syntax
  - [ ] File `rekap-verifikasi.blade.php` exist
  - [ ] Computed `rekapVerifikasi`, `opdList`, `statsCount` exist
  - [ ] Properties `selected_opd`, `filter_status` exist
  - [ ] Status badge logic ada di view (3 colors untuk 3 status)
  - [ ] `php artisan migrate:fresh --seed` exit 0
  - [ ] `composer dump-autoload` succeeds (kelas baru ke-load)

  **QA Scenarios**:

  ```
  Scenario: Happy path - file dibuat dengan struktur benar
    Tool: Bash (PowerShell)
    Steps:
      1. Test-Path "app/Livewire/Dashboard/RekapVerifikasi.php" (expect True)
      2. Test-Path "resources/views/livewire/dashboard/rekap-verifikasi.blade.php" (expect True)
      3. composer dump-autoload (expect success)
      4. php artisan migrate:fresh --seed (expect exit 0)
      5. Select-String -Path "app/Livewire/Dashboard/RekapVerifikasi.php" -Pattern "rekapVerifikasi|opdList|statsCount" | Measure-Object (expect ≥3)
    Expected Result: All checks pass
    Evidence: .sisyphus/evidence/plan-a-task-3-creation.txt
  ```

  **Evidence to Capture**:
  - [ ] `.sisyphus/evidence/plan-a-task-3-creation.txt`

  **Commit**: NO

- [x] 4. Update views `rekap-penolakan.blade.php` + `rekap-perbaikan.blade.php` (add filter OPD UI)

  **What to do**:
  - **Edit `resources/views/livewire/dashboard/rekap-penolakan.blade.php`**:
    - Di card-header (sekitar line 21-23), tambah filter row di atas tabel
    - Filter dropdown OPD: `<select wire:model.live="selected_opd">` dengan options dari `$this->opdList`
    - Tampilkan filter HANYA untuk role non-OPD: wrap dengan `@if (Auth::user()->role->jenis !== 'opd')`
    - Tambah kolom "OPD" di tabel header + body untuk role non-OPD
  - **Edit `resources/views/livewire/dashboard/rekap-perbaikan.blade.php`**:
    - Mirip dengan rekap-penolakan
    - Tambah filter dropdown OPD di card-header
    - Kolom OPD sudah ada di view existing — pastikan filter `selected_opd` apply
  - **Style consistency**: pakai class Bootstrap yang sama dengan filter di Monitoring/LembarKerja

  **Must NOT do**:
  - JANGAN ubah struktur tabel kolom existing yang tidak related ke filter
  - JANGAN edit view lain

  **Recommended Agent Profile**:
  - **Category**: `writing`
    - Reason: Edit blade views (template HTML/Livewire). Bukan logic.
  - **Skills**: []

  **Parallelization**:
  - **Can Run In Parallel**: NO (depends T1, T2)
  - **Parallel Group**: Wave 2
  - **Blocks**: F1-F3
  - **Blocked By**: T1, T2

  **References**:

  **Pattern References**:
  - `resources/views/livewire/dashboard/monitoring.blade.php` — filter OPD + search pattern
  - `resources/views/livewire/dashboard/lembar-kerja.blade.php` — opdList dropdown pattern
  - Existing views `rekap-penolakan.blade.php` + `rekap-perbaikan.blade.php` — preserve struktur

  **Acceptance Criteria**:

  - [ ] Filter dropdown OPD muncul di kedua view
  - [ ] Conditional `@if Auth::user()->role->jenis !== 'opd'` ada di rekap-penolakan
  - [ ] `wire:model.live="selected_opd"` ada di kedua view
  - [ ] Tabel masih render dengan kolom existing (no regression)

  **QA Scenarios**:

  ```
  Scenario: Filter UI present in both views
    Tool: Bash (PowerShell)
    Steps:
      1. Select-String -Path "resources/views/livewire/dashboard/rekap-penolakan.blade.php" -Pattern "selected_opd" | Measure-Object (expect ≥1)
      2. Select-String -Path "resources/views/livewire/dashboard/rekap-perbaikan.blade.php" -Pattern "selected_opd" | Measure-Object (expect ≥1)
      3. Select-String -Path "resources/views/livewire/dashboard/rekap-penolakan.blade.php" -Pattern "Auth::user.*role.*jenis.*opd" | Measure-Object (expect ≥1)
    Expected Result: All filters added
    Evidence: .sisyphus/evidence/plan-a-task-4-views.txt
  ```

  **Evidence to Capture**:
  - [ ] `.sisyphus/evidence/plan-a-task-4-views.txt`

  **Commit**: NO

- [x] 5. Update `routes/web.php` + sidebar `app.blade.php`

  **What to do**:
  - **Edit `routes/web.php`**:
    - Tambah `use App\Livewire\Dashboard\RekapVerifikasi;` di top
    - Tambah route di group middleware (line 39-51 area):
      ```php
      Route::get('/rekap-verifikasi', RekapVerifikasi::class)->name('rekap-verifikasi');
      ```
  - **Edit `resources/views/components/layouts/app.blade.php`** (sidebar):
    - **Update gate Rekap Penolakan** (line 305): tambah role `penjamin`, `penilai` ke `@if`:
      ```blade
      @if (in_array(Auth::user()->role->jenis, ['opd', 'penjamin', 'penilai']))
      ```
    - **Update badge count Rekap Penolakan**: 
      - Untuk `jenis == 'opd'`: query existing logic (filter `opd_id` = user's opd) — preserve.
      - Untuk `penjamin`/`penilai`: count semua penolakan `belum_diperbaiki` dengan filter `tahun_id` saja (no opd filter karena lihat semua OPD).
    - **TAMBAH section sidebar untuk Rekap Verifikasi** (setelah Rekap Perbaikan):
      - **PENTING — JANGAN raw query di blade**. Pakai `RekapVerifikasi::badgeCount()` static helper atau computed property. Jika tidak ada, embed query SEDERHANA (TIDAK pakai `whereNotIn` subquery yang kompleks).
      - Pendekatan rekomendasi: pakai SIMPLE COUNT yang efisien:
        ```blade
        @if (Auth::user()->role->jenis === 'verifikator')
            <li class="nav-item">
                @php
                    $tahunSession = session('tahun_session');
                    $verifikatorRoleId = Auth::user()->role_id;
                    $opdRoleId = \App\Models\Role::where('jenis', 'opd')->first()?->id;
                    
                    // Count: bukti dukung yang punya file dari OPD tapi BELUM ada penilaian verifikator
                    // Pakai LEFT JOIN style via raw subquery (single query, no N+1)
                    $belumDiverifikasi = 0;
                    if ($verifikatorRoleId && $opdRoleId && $tahunSession) {
                        $belumDiverifikasi = \DB::table('penilaian as p_opd')
                            ->join('bukti_dukung as bd', 'bd.id', '=', 'p_opd.bukti_dukung_id')
                            ->where('p_opd.role_id', $opdRoleId)
                            ->whereNotNull('p_opd.link_file')
                            ->where('bd.role_id', $verifikatorRoleId)
                            ->where('bd.tahun_id', $tahunSession)
                            ->whereNotExists(function($q) use ($verifikatorRoleId) {
                                $q->select(\DB::raw(1))
                                  ->from('penilaian as p_verif')
                                  ->whereColumn('p_verif.kriteria_komponen_id', 'p_opd.kriteria_komponen_id')
                                  ->whereColumn('p_verif.opd_id', 'p_opd.opd_id')
                                  ->whereColumn('p_verif.bukti_dukung_id', 'p_opd.bukti_dukung_id')
                                  ->where('p_verif.role_id', $verifikatorRoleId)
                                  ->whereNotNull('p_verif.is_verified');
                            })
                            ->count();
                    }
                @endphp
                <a wire:current="active" class="nav-link menu-link" href="/rekap-verifikasi" role="button">
                    <i class="mdi mdi-checkbox-multiple-marked-circle-outline"></i>
                    <span>Rekap Verifikasi</span>
                    @if ($belumDiverifikasi > 0)
                        <span class="badge bg-warning rounded-pill ms-1">{{ $belumDiverifikasi }}</span>
                    @endif
                </a>
            </li>
        @endif
        ```
      - Note: `whereNotExists` dengan correlated subquery adalah single query (tidak N+1). Lebih efisien dari `whereNotIn` dengan large IN clause.
  - VERIFIKASI: `php artisan route:list | grep rekap-verifikasi` shows the route.

  **Must NOT do**:
  - JANGAN ubah middleware allow-list (tetap include semua 7 role di route group existing)
  - JANGAN hapus existing routes/menus
  - JANGAN ubah blok admin-only menu (Mapping, Pengaturan)
  - JANGAN tulis raw query yang loop ke DB (N+1) di blade — pakai single query subquery saja
  - JANGAN tambah computed yang berat ke setiap render sidebar (akan slow down semua page) — kalau perlu cache, cache di RekapVerifikasi component, bukan di sidebar

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
    - Reason: Edit critical files (routes + sidebar). Risk regression jika salah. Plus query optimization concern.
  - **Skills**: []

  **Parallelization**:
  - **Can Run In Parallel**: NO (depends T1, T2, T3)
  - **Parallel Group**: Wave 2
  - **Blocks**: F1-F3
  - **Blocked By**: T1, T2, T3

  **References**:

  **Pattern References**:
  - `routes/web.php:39-51` — existing route group dengan middleware
  - `resources/views/components/layouts/app.blade.php:305-365` — pattern sidebar conditional dengan badge
  - `app/Models/Role.php` — role.jenis values
  - `.sisyphus/docs/ROLES.md` — role × feature matrix

  **WHY Each Reference Matters**:
  - Pattern badge count harus mirror existing untuk visual consistency
  - Sidebar conditional harus presisi untuk tidak break existing role visibility
  - `whereNotExists` adalah single SQL query — alternatif `whereNotIn` dengan large set akan slow

  **Acceptance Criteria**:

  - [ ] Route `/rekap-verifikasi` registered: `php artisan route:list | grep rekap-verifikasi` ≥1 match
  - [ ] Sidebar block "Rekap Verifikasi" exists: grep `rekap-verifikasi` di app.blade.php ≥1 match
  - [ ] Sidebar block "Rekap Penolakan" updated dengan `in_array(...['opd','penjamin','penilai'])`
  - [ ] Badge count Rekap Verifikasi pakai `whereNotExists` (single query, NO N+1) — verify dengan grep
  - [ ] Badge count untuk role 'opd' di Rekap Penolakan TIDAK BERUBAH (regression)
  - [ ] `php artisan migrate:fresh --seed` exit 0
  - [ ] `php artisan route:list | grep rekap` shows 3 rekap routes (penolakan, perbaikan, verifikasi)

  **QA Scenarios**:

  ```
  Scenario: Routes + sidebar updated
    Tool: Bash (PowerShell)
    Steps:
      1. php artisan route:list | Select-String "rekap-verifikasi" (expect ≥1)
      2. Select-String -Path "resources/views/components/layouts/app.blade.php" -Pattern "rekap-verifikasi" (expect ≥1)
      3. Select-String -Path "resources/views/components/layouts/app.blade.php" -Pattern "in_array.*opd.*penjamin.*penilai" (expect ≥1)
      4. Select-String -Path "resources/views/components/layouts/app.blade.php" -Pattern "whereNotExists" (expect ≥1, single-query approach)
      5. Select-String -Path "resources/views/components/layouts/app.blade.php" -Pattern "whereNotIn.*function" (expect 0, no nested loop subquery)
      6. php artisan migrate:fresh --seed (expect exit 0)
    Expected Result: All checks pass
    Evidence: .sisyphus/evidence/plan-a-task-5-routes-sidebar.txt

  Scenario: Performance check - sidebar load time
    Tool: Bash (PowerShell + curl)
    Preconditions: php artisan serve running, login as verifikator_bappeda
    Steps:
      1. Time first GET /dashboard (sidebar render)
      2. Verify response time < 2s (sebelum optimization should be similar)
    Expected Result: No significant slowdown vs baseline
    Evidence: .sisyphus/evidence/plan-a-task-5-perf.txt
  ```

  **Evidence to Capture**:
  - [ ] `.sisyphus/evidence/plan-a-task-5-routes-sidebar.txt`
  - [ ] `.sisyphus/evidence/plan-a-task-5-perf.txt`

  **Commit**: YES (Commit 1 setelah T5 selesai)
  - Message: `feat(rekap): extend role access, add OPD filter, new RekapVerifikasi menu`
  - Files staged: `app/Livewire/Dashboard/RekapPenolakan.php`, `RekapPerbaikan.php`, `RekapVerifikasi.php` (NEW), 2 view files (modified), 1 view file (NEW), `resources/views/components/layouts/app.blade.php`, `routes/web.php`
  - Pre-commit verification: `php artisan migrate:fresh --seed` exit 0 + `php artisan route:list | Select-String rekap` shows 3 routes

---

## Final Verification Wave (MANDATORY — after ALL implementation tasks)

> 3 review agents run in PARALLEL. ALL must APPROVE. Present consolidated results to user and get explicit "okay" before completing.
>
> **Do NOT auto-proceed after verification. Wait for user's explicit approval before marking work complete.**

- [x] F1. **Plan Compliance Audit** — `oracle`
  Read this plan end-to-end. For each "Must Have": verify implementation exists. For each "Must NOT Have": search codebase for forbidden patterns. Verify all 3 components exist + work. Check no regression in existing OPD flow.
  Output: `Must Have [N/N] | Must NOT Have [N/N] | VERDICT: APPROVE/REJECT`

- [x] F2. **Multi-Role Manual QA via Playwright** — `unspecified-high` (+ `playwright` skill)
  Start fresh `php artisan serve`. Test login + page navigation untuk 5 role:
  1. admin → akses semua menu
  2. opd → /rekap-penolakan render dengan opd_id own filter, NO filter OPD dropdown
  3. penjamin → /rekap-penolakan render dengan filter OPD dropdown, /rekap-perbaikan render
  4. penilai → /rekap-penolakan render dengan filter OPD dropdown, /rekap-perbaikan render
  5. verifikator_bappeda → /rekap-verifikasi render, filter OPD bekerja, list dokumen + status
  Save screenshot per role.
  Output: `5/5 roles render correct | VERDICT`

- [x] F3. **Scope Fidelity Check** — `deep`
  For each task: read "What to do", read actual diff. Verify 1:1. Check "Must NOT do" compliance: NO migration, NO model edit, NO existing component edit (except 2 modify + 1 view modify).
  Output: `Tasks [N/N compliant] | Forbidden changes [CLEAN/N issues] | VERDICT`

---

## Commit Strategy

Single commit setelah Wave 2 selesai:

- **Commit**: `feat(rekap): extend role access, add OPD filter, new RekapVerifikasi menu`
  - Files staged:
    - **MODIFIED**: `app/Livewire/Dashboard/RekapPenolakan.php`, `app/Livewire/Dashboard/RekapPerbaikan.php`
    - **MODIFIED**: `resources/views/livewire/dashboard/rekap-penolakan.blade.php`, `resources/views/livewire/dashboard/rekap-perbaikan.blade.php`
    - **MODIFIED**: `resources/views/components/layouts/app.blade.php`, `routes/web.php`
    - **ADDED**: `app/Livewire/Dashboard/RekapVerifikasi.php`, `resources/views/livewire/dashboard/rekap-verifikasi.blade.php`
  - Pre-commit verification: `php artisan migrate:fresh --seed` exit 0 + `php artisan route:list | grep rekap` shows 3 routes (penolakan, perbaikan, verifikasi)

---

## Success Criteria

### Verification Commands

```powershell
# All 3 components exist
Test-Path "app/Livewire/Dashboard/RekapPenolakan.php"      # True
Test-Path "app/Livewire/Dashboard/RekapPerbaikan.php"      # True
Test-Path "app/Livewire/Dashboard/RekapVerifikasi.php"     # True

# All 3 views exist
Test-Path "resources/views/livewire/dashboard/rekap-penolakan.blade.php"   # True
Test-Path "resources/views/livewire/dashboard/rekap-perbaikan.blade.php"   # True
Test-Path "resources/views/livewire/dashboard/rekap-verifikasi.blade.php"  # True

# Routes registered
php artisan route:list | Select-String "rekap-(penolakan|perbaikan|verifikasi)"  # Expect 3 matches

# Migrate masih sukses
php artisan migrate:fresh --seed   # Expect exit 0

# Filter OPD muncul (selected_opd property exists)
Select-String -Path "app/Livewire/Dashboard/RekapPenolakan.php" -Pattern "selected_opd|filter_opd"  # ≥1 match
Select-String -Path "app/Livewire/Dashboard/RekapPerbaikan.php" -Pattern "selected_opd|filter_opd"  # ≥1 match
Select-String -Path "app/Livewire/Dashboard/RekapVerifikasi.php" -Pattern "selected_opd|filter_opd"  # ≥1 match

# RekapPenolakan extended ke penjamin/penilai
Select-String -Path "app/Livewire/Dashboard/RekapPenolakan.php" -Pattern "penjamin|penilai"  # ≥2 matches

# Sidebar updated
Select-String -Path "resources/views/components/layouts/app.blade.php" -Pattern "rekap-verifikasi"  # ≥1 match
```
