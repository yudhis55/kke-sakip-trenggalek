# Plan A — Task 1 Learnings

## RekapPenolakan: Extending role access

- `Auth::user()->role->jenis` is the canonical way to discriminate role types in this codebase (matches `Monitoring.php` pattern).
- Role list relevant to RekapPenolakan: `opd` (own data only), `penjamin`/`penilai` (cross-OPD with optional filter).
- For OPD users, `opd_id = Auth::user()->opd_id` MUST be enforced server-side regardless of any client-set `selected_opd` — preserve original behavior.
- `Opd::orderBy('nama')->get()` returns full collection (suitable for a `<select>` dropdown). For paginated/searchable UIs, see `Monitoring::opdList()` which uses `paginate()` + `searchOpd` filter.
- `bukti_dukung` and `kriteria_komponen` are snake_case relation methods — preserve when eager loading.
- Computed methods using `#[Computed]` re-run when their dependencies (public properties like `selected_opd`) change — no manual cache invalidation needed.
- `updatedSelectedOpd()` hook left as no-op placeholder for future pagination logic if pagination is later added to `rekapPenolakan`.

## Verification

- `php -l` clean on RekapPenolakan.php
- `php artisan migrate:fresh --seed` ran to completion (all migrations + seeders DONE)
- Evidence: `.sisyphus/evidence/plan-a-task-1-php-compile.txt`, `.sisyphus/evidence/plan-a-task-1-migrate.txt`
