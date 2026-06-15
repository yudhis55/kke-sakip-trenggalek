# Queue-Based E-SAKIP Sync

## TL;DR

> **Quick Summary**: Convert synchronous E-SAKIP document sync to a Laravel Queue background job with real-time progress polling, 500ms rate limiting, and cancel support.
> 
> **Deliverables**:
> - `app/Jobs/ProcessEsakipSync.php` — Queue job class
> - `database/migrations/xxxx_create_sync_progress_table.php` — Progress tracking table
> - `app/Models/SyncProgress.php` — Progress model
> - Modified `app/Services/EsakipSyncService.php` — Add delay + cancel check
> - Modified `app/Livewire/Dashboard/SinkronData.php` — Dispatch job + poll progress
> - Modified `resources/views/livewire/dashboard/sinkron-data.blade.php` — Real progress UI
> 
> **Estimated Effort**: Medium (4-6 hours)
> **Parallel Execution**: YES - 2 waves
> **Critical Path**: Task 1 (migration+model) → Task 2 (job class) → Task 3 (Livewire+UI)

---

## Context

### Original Request
Proses sinkronisasi E-SAKIP saat ini berjalan synchronous dalam satu HTTP request Livewire. Dengan 48 OPD × 20 document types = ~1940 API calls, proses ini:
- Memakan waktu ~16+ menit
- Berisiko kena PHP timeout
- Berisiko di-block Cloudflare (rate limit)
- Browser menunggu tanpa progress update yang real

### Interview Summary
**Key Discussions**:
- User memilih Queue-based (background job) approach
- Progress tracking via Livewire wire:poll (setiap 2-3 detik)
- Rate limiting 500ms antar API request
- Cancel support: user bisa membatalkan sync yang sedang berjalan

**Research Findings**:
- `QUEUE_CONNECTION=database` sudah dikonfigurasi
- Migration `jobs` table sudah ada (Laravel default)
- `queue:listen` sudah ada di `composer dev`
- `app/Jobs/` kosong — belum ada Job class
- EsakipSyncService sudah punya `$progressCallback` mechanism
- Per-step transactions (bukan 1 giant transaction) — aman untuk queue
- Progress bar di blade saat ini cosmetic (tidak real-time karena synchronous)

### Metis Review
**Identified Gaps** (addressed):
- Concurrency: Default hanya 1 sync aktif secara global (reject jika sudah ada yang running)
- Job granularity: Single job dengan internal loop (bukan batch/chunked jobs)
- Failure handling: Skip failed step, lanjut, report di akhir (sudah behavior existing)
- Preview tetap synchronous (lebih ringan, tidak perlu queue)
- Job retry: 1 try saja (internal retry per API call sudah ada via `->retry(5, 200)`)

---

## Work Objectives

### Core Objective
Pindahkan proses sync dari synchronous Livewire action ke background queue job dengan progress tracking real-time, rate limiting 500ms, dan kemampuan cancel.

### Concrete Deliverables
- Migration + Model untuk `sync_progress` table
- Job class `ProcessEsakipSync`
- Modified EsakipSyncService dengan delay + cancel check
- Modified SinkronData Livewire component (dispatch + poll)
- Updated blade view dengan real progress bar + cancel button

### Definition of Done
- [ ] User klik "Proses Sinkronisasi" → job di-dispatch ke queue
- [ ] Progress bar update setiap 2-3 detik via wire:poll
- [ ] Delay 500ms antar API request ke e-SAKIP
- [ ] User bisa cancel sync yang sedang berjalan
- [ ] Hanya 1 sync aktif secara global (reject jika sudah ada)
- [ ] Hasil sync tetap tersimpan di RiwayatSinkron seperti sebelumnya

### Must Have
- Background job execution (tidak blocking browser)
- Real progress tracking dari database
- 500ms delay antar API request
- Cancel button yang menghentikan job
- Mutex/lock: hanya 1 sync aktif
- Error handling: failed steps di-skip, dilaporkan di akhir

### Must NOT Have (Guardrails)
- JANGAN ubah business logic sync (matching, merge, auto-verify tetap sama)
- JANGAN gunakan Laravel Broadcasting/Pusher/Soketi (polling saja)
- JANGAN ubah previewSync() — tetap synchronous
- JANGAN buat multiple job classes (1 job class saja)
- JANGAN hapus progressCallback dari EsakipSyncService — tetap support keduanya
- JANGAN tambah package baru (composer/npm)

---

## Verification Strategy (MANDATORY)

> **ZERO HUMAN INTERVENTION** - ALL verification is agent-executed. No exceptions.

### Test Decision
- **Infrastructure exists**: NO (stock stubs only)
- **Automated tests**: None
- **Framework**: N/A

### QA Policy
Every task MUST include agent-executed QA scenarios.
Evidence saved to `.sisyphus/evidence/task-{N}-{scenario-slug}.{ext}`.

- **Backend/Queue**: Use Bash (php artisan) — dispatch job, check progress table, verify completion
- **Frontend/UI**: Use Playwright — navigate to sinkron-data, trigger sync, observe progress, cancel

---

## Execution Strategy

### Parallel Execution Waves

```
Wave 1 (Start Immediately - foundation):
├── Task 1: Migration + Model sync_progress [quick]
└── Task 2: Job class ProcessEsakipSync [unspecified-high]

Wave 2 (After Wave 1 - integration):
├── Task 3: Modify EsakipSyncService (delay + cancel check) [unspecified-high]
└── Task 4: Modify SinkronData + Blade (dispatch + poll + cancel UI) [unspecified-high]

Wave FINAL (After ALL tasks):
├── Task F1: Plan compliance audit (oracle)
├── Task F2: Code quality review (unspecified-high)
├── Task F3: Real manual QA (unspecified-high)
└── Task F4: Scope fidelity check (deep)
-> Present results -> Get explicit user okay
```

### Dependency Matrix

| Task | Depends On | Blocks | Wave |
|------|-----------|--------|------|
| 1 | - | 2, 3, 4 | 1 |
| 2 | 1 | 3, 4 | 1 |
| 3 | 1, 2 | 4 | 2 |
| 4 | 1, 2, 3 | F1-F4 | 2 |

### Agent Dispatch Summary

- **Wave 1**: 2 tasks — T1 → `quick`, T2 → `unspecified-high`
- **Wave 2**: 2 tasks — T3 → `unspecified-high`, T4 → `unspecified-high`
- **FINAL**: 4 tasks — F1 → `oracle`, F2 → `unspecified-high`, F3 → `unspecified-high`, F4 → `deep`

---

## TODOs

- [x] 1. Migration + Model: sync_progress table

  **What to do**:
  - Buat migration `create_sync_progress_table` dengan kolom:
    - `id` (bigIncrements)
    - `tahun_id` (foreignId, nullable)
    - `opd_id` (foreignId, nullable) — null jika sync semua OPD
    - `document_type` (string, nullable) — null jika sync semua tipe
    - `status` (enum: `pending`, `processing`, `completed`, `failed`, `cancelled`)
    - `current_step` (integer, default 0)
    - `total_steps` (integer, default 0)
    - `current_message` (text, nullable) — pesan progress saat ini
    - `results` (json, nullable) — hasil akhir sync (success_count, failed_count, dll)
    - `error_message` (text, nullable) — jika gagal total
    - `started_at` (timestamp, nullable)
    - `completed_at` (timestamp, nullable)
    - `cancelled_at` (timestamp, nullable)
    - `dispatched_by` (foreignId → users, nullable) — siapa yang trigger
    - `timestamps`
  - Buat model `SyncProgress` dengan:
    - `protected $table = 'sync_progress'`
    - `protected $guarded = ['id']`
    - `protected $casts = ['results' => 'array', 'started_at' => 'datetime', 'completed_at' => 'datetime', 'cancelled_at' => 'datetime']`
    - Relasi: `tahun()`, `opd()`, `user()` (dispatched_by)
    - Scope: `scopeActive($query)` → where status in ['pending', 'processing']
    - Method: `isRunning()`, `isCancelled()`, `markAsProcessing()`, `markAsCompleted($results)`, `markAsFailed($error)`, `markAsCancelled()`, `updateProgress($step, $total, $message)`
    - Helper: `getProgressPercentage()` → round(current_step / total_steps * 100)

  **Must NOT do**:
  - JANGAN gunakan `$fillable` — gunakan `$guarded = ['id']`
  - JANGAN buat migration yang mengubah tabel existing

  **Recommended Agent Profile**:
  - **Category**: `quick`
    - Reason: Straightforward migration + model, follows existing patterns
  - **Skills**: []
  - **Skills Evaluated but Omitted**:
    - None needed — standard Laravel migration/model

  **Parallelization**:
  - **Can Run In Parallel**: YES (with Task 2 partially — Task 2 needs the model)
  - **Parallel Group**: Wave 1
  - **Blocks**: Tasks 2, 3, 4
  - **Blocked By**: None (can start immediately)

  **References**:

  **Pattern References** (existing code to follow):
  - `app/Models/RiwayatSinkron.php` — Existing sync history model, follow same pattern ($guarded, $casts, relasi)
  - `app/Models/Penilaian.php` — Example of model with json cast and datetime casts
  - `database/migrations/2025_05_07_044832_create_riwayat_sinkron_table.php` — Migration pattern for sync-related table

  **API/Type References**:
  - `app/Models/Tahun.php` — foreignId target for tahun_id
  - `app/Models/Opd.php` — foreignId target for opd_id

  **Acceptance Criteria**:
  - [ ] `php artisan migrate` berhasil tanpa error
  - [ ] `php artisan tinker --execute="echo (new App\Models\SyncProgress)->getTable();"` → `sync_progress`
  - [ ] Model memiliki semua method: `isRunning()`, `isCancelled()`, `markAsProcessing()`, `markAsCompleted()`, `markAsFailed()`, `markAsCancelled()`, `updateProgress()`

  **QA Scenarios (MANDATORY):**

  ```
  Scenario: Migration creates table successfully
    Tool: Bash (php artisan)
    Preconditions: Fresh database state
    Steps:
      1. Run `php artisan migrate:fresh --seed`
      2. Run `php artisan tinker --execute="echo Schema::hasTable('sync_progress') ? 'YES' : 'NO';"`
      3. Assert output is "YES"
    Expected Result: Table sync_progress exists with all columns
    Failure Indicators: Migration error or table not found
    Evidence: .sisyphus/evidence/task-1-migration-create.txt

  Scenario: Model CRUD operations work
    Tool: Bash (php artisan tinker)
    Preconditions: Migration applied
    Steps:
      1. Run tinker: `$sp = App\Models\SyncProgress::create(['status' => 'pending', 'total_steps' => 100]); echo $sp->id;`
      2. Run tinker: `$sp = App\Models\SyncProgress::first(); $sp->updateProgress(50, 100, 'Testing'); echo $sp->getProgressPercentage();`
      3. Assert output is "50"
      4. Run tinker: `$sp->markAsCancelled(); echo $sp->status;`
      5. Assert output is "cancelled"
    Expected Result: All CRUD and helper methods work correctly
    Failure Indicators: Exception or unexpected return values
    Evidence: .sisyphus/evidence/task-1-model-crud.txt
  ```

  **Commit**: YES (groups with Task 2)
  - Message: `feat(sync): add queue job infrastructure for background sync`
  - Files: `database/migrations/xxxx_create_sync_progress_table.php`, `app/Models/SyncProgress.php`
  - Pre-commit: `php -l app/Models/SyncProgress.php`

- [x] 2. Job Class: ProcessEsakipSync

  **What to do**:
  - Buat `app/Jobs/ProcessEsakipSync.php` yang:
    - Implements `ShouldQueue`
    - Properties: `$syncProgressId`, `$tahunId`, `$opdId` (nullable), `$documentType` (nullable)
    - `$tries = 1` (internal retry sudah ada di EsakipSyncService)
    - `$timeout = 3600` (1 jam max — 48 OPD × 20 types × 500ms = ~16 menit, beri margin)
    - Constructor: terima `$syncProgressId`, `$tahunId`, `$opdId`, `$documentType`
    - `handle(EsakipSyncService $service)`:
      1. Load SyncProgress record
      2. Set status = 'processing', started_at = now()
      3. Call `$service->processSync()` dengan custom progressCallback yang:
         - Update SyncProgress record di DB (current_step, total_steps, current_message)
         - Check `$syncProgress->fresh()->isCancelled()` — jika true, throw `SyncCancelledException`
         - Sleep 500ms (usleep(500000)) setelah setiap step untuk rate limiting
      4. On success: markAsCompleted($results)
      5. On exception: markAsFailed($e->getMessage())
      6. On SyncCancelledException: markAsCancelled() (jangan re-throw)
    - `failed(Throwable $exception)`: Update SyncProgress status = 'failed'
  - Buat custom exception `app/Exceptions/SyncCancelledException.php` (simple, extends RuntimeException)

  **Must NOT do**:
  - JANGAN ubah signature processSync() di EsakipSyncService
  - JANGAN buat multiple job classes
  - JANGAN gunakan Laravel Bus/Batch (overkill untuk ini)

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
    - Reason: Needs careful integration with existing service, cancel logic, error handling
  - **Skills**: []

  **Parallelization**:
  - **Can Run In Parallel**: NO (needs SyncProgress model from Task 1)
  - **Parallel Group**: Wave 1 (after Task 1)
  - **Blocks**: Tasks 3, 4
  - **Blocked By**: Task 1

  **References**:

  **Pattern References**:
  - `app/Services/EsakipSyncService.php:211-328` — processSync() method signature dan progressCallback usage
  - `app/Services/EsakipSyncService.php:252` — totalSteps calculation: `$opdList->count() * count($documentTypes) + count($documentTypes)`
  - `app/Services/EsakipSyncService.php:98-104` — progressCallback signature: `function ($current, $total, $message)`
  - `app/Livewire/Dashboard/SinkronData.php:81-133` — Current synchronous processSync() call pattern

  **API/Type References**:
  - `app/Models/SyncProgress.php` (from Task 1) — updateProgress(), isCancelled(), markAsCompleted(), markAsFailed(), markAsCancelled()

  **External References**:
  - Laravel Queue Jobs: https://laravel.com/docs/12.x/queues#creating-jobs

  **Acceptance Criteria**:
  - [ ] `php -l app/Jobs/ProcessEsakipSync.php` → No syntax errors
  - [ ] `php -l app/Exceptions/SyncCancelledException.php` → No syntax errors
  - [ ] Job class implements ShouldQueue
  - [ ] Job has $timeout = 3600 and $tries = 1
  - [ ] progressCallback updates SyncProgress in DB
  - [ ] Cancel check happens inside progressCallback
  - [ ] 500ms delay (usleep) inside progressCallback

  **QA Scenarios (MANDATORY):**

  ```
  Scenario: Job can be instantiated and serialized
    Tool: Bash (php artisan tinker)
    Preconditions: Task 1 migration applied, Job class exists
    Steps:
      1. Run tinker: `$sp = App\Models\SyncProgress::create(['status' => 'pending', 'total_steps' => 0]); $job = new App\Jobs\ProcessEsakipSync($sp->id, 1, null, null); echo get_class($job);`
      2. Assert output contains "ProcessEsakipSync"
      3. Run tinker: `echo serialize($job) ? 'OK' : 'FAIL';`
      4. Assert output is "OK"
    Expected Result: Job instantiates and serializes without error
    Failure Indicators: Class not found, serialization error
    Evidence: .sisyphus/evidence/task-2-job-instantiate.txt

  Scenario: Job handles cancel exception gracefully
    Tool: Bash (php artisan tinker)
    Preconditions: Job class and SyncCancelledException exist
    Steps:
      1. Run tinker: `$e = new App\Exceptions\SyncCancelledException('User cancelled'); echo $e->getMessage();`
      2. Assert output is "User cancelled"
    Expected Result: Exception class works correctly
    Failure Indicators: Class not found
    Evidence: .sisyphus/evidence/task-2-cancel-exception.txt
  ```

  **Commit**: YES (groups with Task 1)
  - Message: `feat(sync): add queue job infrastructure for background sync`
  - Files: `app/Jobs/ProcessEsakipSync.php`, `app/Exceptions/SyncCancelledException.php`
  - Pre-commit: `php -l app/Jobs/ProcessEsakipSync.php`

- [x] 3. Modify EsakipSyncService: Add delay between API calls

  **What to do**:
  - Di method `fetchDocumentsFromEsakipByOpdId()` (line 916) dan `fetchSharedDocumentsFromEsakip()` (line ~820), tambahkan `usleep(500000)` (500ms) SEBELUM HTTP call.
  - Alternatif yang lebih bersih: tambah property `$delayBetweenRequests = 500` (ms) dan method `protected function rateLimitDelay()` yang memanggil `usleep($this->delayBetweenRequests * 1000)`.
  - Panggil `rateLimitDelay()` di awal setiap `fetchDocumentsFromEsakipByOpdId()` dan `fetchSharedDocumentsFromEsakip()`.
  - Tambah config key di `config/esakip.php` → `'sync.delay_between_requests' => env('ESAKIP_SYNC_DELAY', 500)` (dalam ms).
  - Constructor EsakipSyncService baca config: `$this->delayBetweenRequests = config('esakip.sync.delay_between_requests', 500);`
  - **PENTING**: Delay hanya berlaku saat dipanggil dari queue job. Untuk preview (synchronous), delay tidak perlu. Solusi: tambah parameter `$enableDelay = true` di constructor atau set via method `setDelayEnabled(bool $enabled)`. Job memanggil `$service->setDelayEnabled(true)` sebelum processSync(). Default = false (backward compatible).

  **Must NOT do**:
  - JANGAN ubah logic fetch/parse response
  - JANGAN ubah retry config yang sudah ada (`->retry(5, 200)`)
  - JANGAN tambah delay di previewSync() path

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
    - Reason: Modifying critical service file (1336 lines), needs careful placement
  - **Skills**: []

  **Parallelization**:
  - **Can Run In Parallel**: NO (depends on Task 2 for integration context)
  - **Parallel Group**: Wave 2
  - **Blocks**: Task 4
  - **Blocked By**: Tasks 1, 2

  **References**:

  **Pattern References**:
  - `app/Services/EsakipSyncService.php:916-970` — `fetchDocumentsFromEsakipByOpdId()` — tempat tambah delay
  - `app/Services/EsakipSyncService.php:820-905` — `fetchSharedDocumentsFromEsakip()` — tempat tambah delay
  - `app/Services/EsakipSyncService.php:20-25` — Constructor pattern (baca config)
  - `config/esakip.php:76-91` — Existing sync config section

  **Acceptance Criteria**:
  - [ ] `php -l app/Services/EsakipSyncService.php` → No syntax errors
  - [ ] Config `esakip.sync.delay_between_requests` ada dan default 500
  - [ ] `setDelayEnabled(true)` mengaktifkan delay
  - [ ] `setDelayEnabled(false)` (default) tidak ada delay — backward compatible
  - [ ] Delay terjadi sebelum setiap HTTP call ke e-SAKIP

  **QA Scenarios (MANDATORY):**

  ```
  Scenario: Delay config exists and is readable
    Tool: Bash (php artisan tinker)
    Preconditions: Config updated
    Steps:
      1. Run: `php artisan tinker --execute="echo config('esakip.sync.delay_between_requests');"`
      2. Assert output is "500"
    Expected Result: Config returns 500 (ms)
    Failure Indicators: null or missing key
    Evidence: .sisyphus/evidence/task-3-config-delay.txt

  Scenario: Service delay toggle works
    Tool: Bash (php artisan tinker)
    Preconditions: EsakipSyncService modified
    Steps:
      1. Run tinker: `$s = app(App\Services\EsakipSyncService::class); $s->setDelayEnabled(true); echo 'OK';`
      2. Assert no exception thrown
      3. Run tinker: `$s = app(App\Services\EsakipSyncService::class); echo $s->isDelayEnabled() ? 'YES' : 'NO';`
      4. Assert output is "NO" (default disabled)
    Expected Result: Delay toggle works, default is disabled
    Failure Indicators: Method not found, exception
    Evidence: .sisyphus/evidence/task-3-delay-toggle.txt
  ```

  **Commit**: YES (groups with Task 4)
  - Message: `feat(sync): integrate queue-based sync with progress UI and cancel`
  - Files: `app/Services/EsakipSyncService.php`, `config/esakip.php`
  - Pre-commit: `php -l app/Services/EsakipSyncService.php`

- [x] 4. Modify SinkronData Livewire + Blade: Dispatch job, poll progress, cancel UI

  **What to do**:

  **A. SinkronData.php changes:**
  - Tambah property: `public $activeSyncId = null` (ID dari SyncProgress yang sedang berjalan)
  - Tambah property: `public $pollProgress = null` (data progress untuk UI)
  - Modifikasi `processSync()`:
    1. Cek apakah ada sync aktif: `SyncProgress::active()->exists()` → jika ya, flash error "Sudah ada sinkronisasi yang sedang berjalan"
    2. Buat SyncProgress record (status=pending, tahun_id, opd_id, document_type, dispatched_by=auth()->id())
    3. Dispatch job: `ProcessEsakipSync::dispatch($syncProgress->id, $tahunId, $opdId, $documentType)`
    4. Set `$this->activeSyncId = $syncProgress->id`
    5. Set `$this->syncing = true` (untuk UI state)
    6. JANGAN panggil `$this->esakipService->processSync()` lagi
  - Tambah method `pollSyncProgress()`:
    1. Jika `$this->activeSyncId` null, cek apakah ada sync aktif di DB (untuk refresh halaman)
    2. Load SyncProgress by ID
    3. Update `$this->syncProgress` dan `$this->syncMessage` dari record
    4. Jika status = 'completed': set results, syncing=false, flash success
    5. Jika status = 'failed': flash error, syncing=false
    6. Jika status = 'cancelled': flash info, syncing=false
  - Tambah method `cancelSync()`:
    1. Load SyncProgress by activeSyncId
    2. Set status = 'cancelled', cancelled_at = now()
    3. Flash info "Sinkronisasi dibatalkan. Proses akan berhenti pada langkah berikutnya."
  - Modifikasi `mount()`: Cek apakah ada sync aktif → jika ya, set activeSyncId dan syncing=true (untuk handle page refresh)

  **B. sinkron-data.blade.php changes:**
  - Tambah `wire:poll.3s="pollSyncProgress"` pada div progress bar (hanya aktif saat `$syncing`)
  - Ganti progress bar section:
    ```blade
    @if ($syncing)
        <div wire:poll.3s="pollSyncProgress" class="card">
            ...progress bar dengan $syncProgress dan $syncMessage...
            <button wire:click="cancelSync" class="btn btn-danger btn-sm mt-2">
                <i class="mdi mdi-stop-circle me-1"></i> Batalkan Sinkronisasi
            </button>
        </div>
    @endif
    ```
  - Tambah info text di bawah progress: "Sinkronisasi berjalan di background. Anda bisa meninggalkan halaman ini dan kembali nanti."
  - Disable tombol "Proses Sinkronisasi" jika `$syncing` true
  - Tampilkan siapa yang memulai sync (jika ada sync aktif saat mount)

  **Must NOT do**:
  - JANGAN hapus `$esakipService` dari boot() — masih dipakai untuk previewSync()
  - JANGAN ubah previewSync() logic
  - JANGAN gunakan wire:poll secara global (hanya saat syncing)
  - JANGAN tambah package JS baru

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
    - Reason: Complex integration — Livewire component + blade + job dispatch + polling logic
  - **Skills**: []

  **Parallelization**:
  - **Can Run In Parallel**: NO (depends on all previous tasks)
  - **Parallel Group**: Wave 2 (after Task 3)
  - **Blocks**: F1-F4
  - **Blocked By**: Tasks 1, 2, 3

  **References**:

  **Pattern References**:
  - `app/Livewire/Dashboard/SinkronData.php:81-133` — Current processSync() method to replace
  - `app/Livewire/Dashboard/SinkronData.php:43-48` — mount() pattern
  - `app/Livewire/Dashboard/SinkronData.php:26-29` — Existing progress properties
  - `resources/views/livewire/dashboard/sinkron-data.blade.php:226-244` — Current progress bar section to replace

  **API/Type References**:
  - `app/Models/SyncProgress.php` (Task 1) — active() scope, isRunning(), updateProgress()
  - `app/Jobs/ProcessEsakipSync.php` (Task 2) — dispatch() signature

  **External References**:
  - Livewire wire:poll: https://livewire.laravel.com/docs/wire-poll

  **Acceptance Criteria**:
  - [ ] `php -l app/Livewire/Dashboard/SinkronData.php` → No syntax errors
  - [ ] Klik "Proses Sinkronisasi" → job di-dispatch (cek `jobs` table)
  - [ ] Progress bar update setiap 3 detik
  - [ ] Cancel button visible saat syncing
  - [ ] Cancel mengubah status di DB
  - [ ] Page refresh saat sync berjalan → tetap tampil progress (mount() detect active sync)
  - [ ] Jika sudah ada sync aktif → flash error, tidak dispatch baru

  **QA Scenarios (MANDATORY):**

  ```
  Scenario: Happy path - dispatch sync job
    Tool: Playwright
    Preconditions: migrate:fresh --seed, queue worker NOT running (to observe job in table)
    Steps:
      1. Login sebagai admin
      2. Navigate to /sinkron-data
      3. Select tahun dari dropdown (first available)
      4. Click "Preview" button
      5. Wait for preview results to appear
      6. Click "Proses Sinkronisasi" button
      7. Assert progress bar section appears with wire:poll
      8. Assert cancel button visible
      9. Check database: `php artisan tinker --execute="echo App\Models\SyncProgress::where('status', 'pending')->orWhere('status', 'processing')->count();"`
      10. Assert count >= 1
    Expected Result: Job dispatched, progress UI shown, SyncProgress record created
    Failure Indicators: No progress bar, no record in sync_progress table, error flash
    Evidence: .sisyphus/evidence/task-4-dispatch-sync.png

  Scenario: Reject duplicate sync
    Tool: Playwright
    Preconditions: A sync is already in 'processing' status in sync_progress table
    Steps:
      1. Insert fake active sync: `php artisan tinker --execute="App\Models\SyncProgress::create(['status' => 'processing', 'total_steps' => 100, 'current_step' => 50]);"`
      2. Login sebagai admin, navigate to /sinkron-data
      3. Select tahun, click Preview, click "Proses Sinkronisasi"
      4. Assert error flash message appears containing "sudah ada sinkronisasi"
      5. Assert NO new job dispatched
    Expected Result: Duplicate sync rejected with clear error message
    Failure Indicators: Second job dispatched, no error message
    Evidence: .sisyphus/evidence/task-4-reject-duplicate.png

  Scenario: Cancel running sync
    Tool: Playwright + Bash
    Preconditions: A sync is in 'processing' status
    Steps:
      1. Insert active sync: `php artisan tinker --execute="App\Models\SyncProgress::create(['status' => 'processing', 'total_steps' => 100, 'current_step' => 30, 'current_message' => 'Sync renstra...']);"`
      2. Login, navigate to /sinkron-data
      3. Assert progress bar visible (mount detects active sync)
      4. Click "Batalkan Sinkronisasi" button
      5. Assert flash message "dibatalkan"
      6. Check DB: `php artisan tinker --execute="echo App\Models\SyncProgress::first()->status;"`
      7. Assert status is "cancelled"
    Expected Result: Sync cancelled, status updated, UI reflects cancellation
    Failure Indicators: Status not changed, no flash message, button not working
    Evidence: .sisyphus/evidence/task-4-cancel-sync.png

  Scenario: Page refresh during active sync shows progress
    Tool: Playwright
    Preconditions: Active sync in DB with progress
    Steps:
      1. Insert: `App\Models\SyncProgress::create(['status' => 'processing', 'total_steps' => 100, 'current_step' => 65, 'current_message' => 'Sync IKU untuk Dinas Pendidikan...'])`
      2. Login, navigate to /sinkron-data
      3. Assert progress bar visible immediately (not just after clicking sync)
      4. Assert progress shows ~65%
      5. Assert message shows "Sync IKU untuk Dinas Pendidikan..."
    Expected Result: Page refresh correctly detects and displays active sync
    Failure Indicators: No progress bar on fresh page load, wrong percentage
    Evidence: .sisyphus/evidence/task-4-refresh-progress.png
  ```

  **Commit**: YES (groups with Task 3)
  - Message: `feat(sync): integrate queue-based sync with progress UI and cancel`
  - Files: `app/Livewire/Dashboard/SinkronData.php`, `resources/views/livewire/dashboard/sinkron-data.blade.php`
  - Pre-commit: `php -l app/Livewire/Dashboard/SinkronData.php`

---

## Final Verification Wave

- [x] F1. **Plan Compliance Audit** — `oracle`
  Read the plan end-to-end. For each "Must Have": verify implementation exists. For each "Must NOT Have": search codebase for forbidden patterns. Check evidence files exist in .sisyphus/evidence/. Compare deliverables against plan.
  Output: `Must Have [N/N] | Must NOT Have [N/N] | Tasks [N/N] | VERDICT: APPROVE/REJECT`

- [x] F2. **Code Quality Review** — `unspecified-high`
  Run `php -l` on all changed files. Review for: empty catches, console.log in prod, commented-out code, unused imports. Check AI slop: excessive comments, over-abstraction.
  Output: `Syntax [PASS/FAIL] | Files [N clean/N issues] | VERDICT`

- [x] F3. **Real Manual QA** — `unspecified-high` (+ `playwright` skill for UI)
  Start from clean state (`migrate:fresh --seed`). Navigate to /sinkron-data. Select tahun + OPD. Preview. Trigger sync. Observe progress bar updating. Try cancel. Verify results appear. Check RiwayatSinkron populated.
  Output: `Scenarios [N/N pass] | VERDICT`

- [x] F4. **Scope Fidelity Check** — `deep`
  For each task: read "What to do", read actual diff. Verify 1:1 — everything in spec was built, nothing beyond spec was built. Check "Must NOT do" compliance.
  Output: `Tasks [N/N compliant] | VERDICT`

---

## Commit Strategy

- **Commit 1** (after Task 1+2): `feat(sync): add queue job infrastructure for background sync`
- **Commit 2** (after Task 3+4): `feat(sync): integrate queue-based sync with progress UI and cancel`

---

## Success Criteria

### Verification Commands
```bash
php artisan migrate          # sync_progress table created
php artisan queue:work --once # Job processes successfully
php artisan tinker --execute="echo App\Models\SyncProgress::count();"  # Progress records exist after sync
```

### Final Checklist
- [ ] Job dispatched to queue (not synchronous)
- [ ] Progress updates visible in UI every 2-3 seconds
- [ ] 500ms delay between API calls observed in logs
- [ ] Cancel stops the job within next iteration
- [ ] Only 1 sync can run at a time
- [ ] RiwayatSinkron populated after completion
- [ ] No changes to sync business logic (matching, merge, auto-verify)
