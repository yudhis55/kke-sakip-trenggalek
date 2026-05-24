# app/Services — eSAKIP Sync (Only Service)

This directory contains **a single 1336-line class**: [EsakipSyncService.php](file:///C:/laragon/www/kke-sakip/app/Services/EsakipSyncService.php). It is the only "Service" pattern in the codebase. Do not interpret its existence as a project-wide convention — fat Livewire components and fat Models are the norm. This service exists because eSAKIP integration is genuinely cross-cutting.

## WHAT IT DOES

Bidirectional bridge between this app and the external **eSAKIP API** at `https://e-sakip.trenggalekkab.go.id/api` (overridable via `ESAKIP_API_URL`). On a sync run:

1. Reads OPD list filtered to those with `esakip_opd_id` set.
2. For each (OPD × document_type) pair declared in [config/esakip.php](file:///C:/laragon/www/kke-sakip/config/esakip.php), fetches remote document metadata.
3. Maps remote docs onto local `BuktiDukung` rows via fuzzy match.
4. Writes/updates rows in `penilaian.link_file` (json) and toggles `is_auto_verified`.
5. Records every sync attempt to `riwayat_sinkrons`.

## ENTRY POINTS (public methods to know)

- `previewSync($tahunId, $opdId = null, $documentType = null)` — dry-run, returns counts + planned changes for UI confirmation.
- `executeSync(...)` — performs the writes.
- Configuration lookups all go through `config('esakip.*')` — never hardcode endpoint paths in callers.

## WHERE IT'S CALLED FROM

- [app/Livewire/Dashboard/SinkronData.php](file:///C:/laragon/www/kke-sakip/app/Livewire/Dashboard/SinkronData.php) — the **only** routed entry point (`/sinkron-data`).
- Root-level scratch scripts (`test-sync.php`, `validate-sync.php`, `test-skip-upload.php`) instantiate the service manually for ops debugging. They are NOT autoloaded — run with `php test-sync.php`.

## CONVENTIONS

- **Config-driven**: every endpoint, timeout, retry count, and document-type label is in [config/esakip.php](file:///C:/laragon/www/kke-sakip/config/esakip.php). Add a new document type by extending the `document_types` array there — **never** in this service.
- **Timeout and retry come from env**: `ESAKIP_SYNC_TIMEOUT`, `ESAKIP_SYNC_RETRY`. **Both are missing from `.env.example`** — add them when documenting deployment.
- **Synchronous execution.** Despite `QUEUE_CONNECTION=database`, sync runs inline inside the Livewire action. A full multi-OPD sync can take minutes; the UI must show progress. Do not "fix" this by dispatching a job without coordinating with the SinkronData component.

## ANTI-PATTERNS

- **DO NOT** split this file by extracting "helpers" into separate classes for tidiness alone. The 1336-line size is intentional — call paths are easier to trace in one file than across 10. Only split if a new feature genuinely warrants it.
- **DO NOT** call the eSAKIP API outside this service. No `Http::get('https://e-sakip...')` anywhere else in the codebase. All requests funnel through this class so that retry/timeout/logging is uniform.
- **DO NOT** add a second service here without justification. The codebase deliberately avoids the "Services as a default folder" pattern.
- **DO NOT** rely on this returning structured DTOs — return values are loosely-typed associative arrays. Match the existing shape (see `previewSync` return for the canonical schema).
