# database — Migrations, Seeders, Factories

36 migrations, 10 seeders, 1 factory (UserFactory only). SQLite is the default driver (`database/database.sqlite`). The app **does not boot usefully** without the role + opd + tingkatan_nilai seeders.

## MIGRATIONS — ORDER IS LOAD-BEARING

Filenames use **non-standard date prefixes** to force ordering:

- `0000_12_01_*` — OPDs and Roles (must exist before users)
- `0001_01_01_*` — stock Laravel (users, cache, jobs)
- `2025_12_01_*` — domain hierarchy (tahuns → komponens → sub_komponens → jenis_nilais → tingkatan_nilais → ...)
- `2026_01_*` — feature additions (konten_laporan, template_laporan, riwayat_sinkrons)
- `2026_01_13_*` and later — **`add_*` migrations layering columns onto existing tables**. Examples: `add_esakip_columns_to_bukti_dukung_table`, `add_page_number_to_penilaian_table`, `add_status_perbaikan_to_penilaian_history_table`.
- **Two migrations share the same minute timestamp** in some cases (`2026_01_13_121635` and `_121648` both add esakip columns to bukti_dukung). One is the original and one is a fix. Do not delete either — they form an idempotent layered schema.

### Rules when adding a migration

- New columns on existing tables → `add_<col>_to_<table>_table` style. Mirror the pattern.
- Touch nothing in `2025_*` or `0000_*` files — those are the baseline schema. Modify via a new `add_*` or `change_*` migration.
- **Always provide `down()`** even though `migrate:fresh` is the dominant workflow — pint and reviewers expect it.
- After altering an enum (e.g. `update_riwayat_sinkron_status_enum`), re-run [database/seeders/RoleSeeder.php](file:///C:/laragon/www/kke-sakip/database/seeders/RoleSeeder.php) or the relevant seeder if values changed.

## SEEDERS — DEPENDENCY ORDER

[DatabaseSeeder.php](file:///C:/laragon/www/kke-sakip/database/seeders/DatabaseSeeder.php) orchestrates. Real dependency chain:

```
RoleSeeder            ─→ creates the 7 roles in `role` table
OpdSeeder             ─→ master list of agencies (with esakip_opd_id mappings)
UserSeeder            ─→ depends on Role + Opd
TahunSeeder           ─→ evaluation years
JenisNilaiSeeder      ─→ then TingkatanNilaiSeeder (depends on jenis_nilai)
MappingSeeder         ─→ seeds Komponen/SubKomponen/KriteriaKomponen hierarchy
SettingSeeder         ─→ key-value app config
```

- `JenisNilai.php` (no Seeder suffix) is an **older duplicate** of `JenisNilaiSeeder.php`. The duplicate is not invoked by `DatabaseSeeder`. Do not call it; leaving it for now to preserve history.

## CONVENTIONS

- **`$guarded = ['id']`** on every model — seeders use mass-assignment via `Model::create([...])` everywhere. This is safe.
- **Foreign keys use `constrained()`** in newer migrations; older ones declare `unsignedBigInteger` + `foreign(...)->references('id')` manually. Match whichever style the file you're editing uses — do not mix within one migration.
- **No `softDeletes()`** anywhere in the schema. If you need to "delete" with audit, write a `PenilaianHistory` row instead.

## FACTORIES

Only [UserFactory.php](file:///C:/laragon/www/kke-sakip/database/factories/UserFactory.php) exists. No tests rely on factories — they are aspirational. Do not invest in a factory unless you are also writing the tests that will use it.

## ANTI-PATTERNS

- **DO NOT** modify the `0000_*` or `2025_12_01_*` migrations. The schema baseline must be reproducible from `migrate:fresh` on any clone.
- **DO NOT** add seed data inside migrations (`DB::table(...)->insert(...)`). Put seed data in `database/seeders/`.
- **DO NOT** drop and re-add a column in a single migration to "rename" it. Use `Schema::table(...)->renameColumn(...)` so historic data is preserved on prod.
- **DO NOT** run `php artisan migrate:fresh` against any non-local DB. SQLite at `database/database.sqlite` is fine to nuke; anything else is not.
