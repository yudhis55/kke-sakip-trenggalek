# KKE-SAKIP — PROJECT KNOWLEDGE BASE

**Generated:** 2026-05-24 (Asia/Bangkok)
**Commit:** `5e3c575`
**Branch:** `main`
**Stack:** Laravel 12 · PHP 8.2 · Livewire 3.7 · Tailwind v4 · Vite 7 · SQLite (default)

## OVERVIEW

**KKE-SAKIP** = *Kertas Kerja Evaluasi — Sistem Akuntabilitas Kinerja Instansi Pemerintah*. An Indonesian government performance-accountability evaluation worksheet for **Kabupaten Trenggalek**. OPDs (government agencies) self-assess against scoring criteria (Komponen → SubKomponen → KriteriaKomponen), upload evidence (BuktiDukung), and three reviewer tiers (verifikator → penjamin → penilai) score and approve. Documents sync **into** the system from an external eSAKIP API.

**Architecture is Livewire-first.** There is exactly **one** traditional controller; **everything user-facing is a Livewire 3 component**. Routes map directly to Livewire classes — see [routes/web.php](file:///C:/laragon/www/kke-sakip/routes/web.php).

## DEEP-DIVE DOCS (READ THESE FIRST WHEN ONBOARDING)

| Doc | Read when |
|-----|-----------|
| [.sisyphus/docs/ROLES.md](./.sisyphus/docs/ROLES.md) | Touching auth, role logic, deadline gating |
| [.sisyphus/docs/FLOWS.md](./.sisyphus/docs/FLOWS.md) | Touching ANY feature — end-to-end flow per page |
| [.sisyphus/docs/KNOWN_BUGS.md](./.sisyphus/docs/KNOWN_BUGS.md) | BEFORE writing new code — 15 documented bugs |
| [.sisyphus/docs/DEAD_CODE.md](./.sisyphus/docs/DEAD_CODE.md) | When tempted to extend a model/file |

## STRUCTURE

```
kke-sakip/
├── app/
│   ├── Livewire/        # THE controller layer — 16 components, page-per-class — see app/Livewire/AGENTS.md
│   ├── Models/          # 21 Eloquent models, custom table names (`role`, `penilaian`) — see app/Models/AGENTS.md
│   ├── Services/        # 1 file: EsakipSyncService (1336 lines) — see app/Services/AGENTS.md
│   ├── Http/Middleware/ # EnsureUserHasRole — role-name list as variadic middleware args
│   └── Providers/       # Empty AppServiceProvider — no custom bindings
├── bootstrap/
│   └── app.php          # Registers LivewireUrlsMiddleware in 'web' group
├── config/
│   └── esakip.php       # CUSTOM — external sync API config + document-type registry
├── database/
│   ├── migrations/      # 36 migrations, ORDER MATTERS — see database/AGENTS.md
│   ├── seeders/         # 10 seeders, RoleSeeder + OpdSeeder + TingkatanNilaiSeeder REQUIRED for app to function
│   └── factories/       # Only 1 (UserFactory) — no test infrastructure relies on these
├── resources/
│   ├── views/           # 16 blade files; livewire/ mirrors app/Livewire/ — see resources/views/AGENTS.md
│   ├── js/              # app.js → bootstrap.js (trivial Vite entry)
│   └── css/             # app.css = Tailwind v4 @import
├── routes/
│   ├── web.php          # 47 lines, ALL routes are Livewire classes
│   └── console.php      # Stock
├── tests/               # ONLY stock ExampleTest stubs — no real test coverage exists
├── public/assets/       # 4109 files — bundled vendor libs (icons, fonts, etc.) — DO NOT hand-edit
└── storage/             # Runtime; storage:link required for uploaded files
```

## WHERE TO LOOK

| Task | Location |
|------|----------|
| Add a new dashboard page | [app/Livewire/Dashboard/](file:///C:/laragon/www/kke-sakip/app/Livewire/Dashboard) + [resources/views/livewire/dashboard/](file:///C:/laragon/www/kke-sakip/resources/views/livewire/dashboard) + register in [routes/web.php](file:///C:/laragon/www/kke-sakip/routes/web.php) |
| Change scoring logic | [app/Models/BuktiDukung.php](file:///C:/laragon/www/kke-sakip/app/Models/BuktiDukung.php) (`bobot` accessor, `getNilai()`) and [Penilaian.php](file:///C:/laragon/www/kke-sakip/app/Models/Penilaian.php) |
| Change role/permission | [app/Http/Middleware/EnsureUserHasRole.php](file:///C:/laragon/www/kke-sakip/app/Http/Middleware/EnsureUserHasRole.php) + role names list in [routes/web.php](file:///C:/laragon/www/kke-sakip/routes/web.php) + [database/seeders/RoleSeeder.php](file:///C:/laragon/www/kke-sakip/database/seeders/RoleSeeder.php) |
| Touch eSAKIP sync | [app/Services/EsakipSyncService.php](file:///C:/laragon/www/kke-sakip/app/Services/EsakipSyncService.php) + [config/esakip.php](file:///C:/laragon/www/kke-sakip/config/esakip.php) + [app/Livewire/Dashboard/SinkronData.php](file:///C:/laragon/www/kke-sakip/app/Livewire/Dashboard/SinkronData.php) |
| Add a document type | [config/esakip.php](file:///C:/laragon/www/kke-sakip/config/esakip.php) `document_types` array |
| Add a DB column | [database/migrations/](file:///C:/laragon/www/kke-sakip/database/migrations) — see [database/AGENTS.md](file:///C:/laragon/www/kke-sakip/database/AGENTS.md) for ordering rules |
| Export Word reports | [app/Livewire/Dashboard/EksporLaporan.php](file:///C:/laragon/www/kke-sakip/app/Livewire/Dashboard/EksporLaporan.php) + `phpoffice/phpword` (see root `PANDUAN_TEMPLATE_WORD.md` family) |
| Auth login | [app/Livewire/Auth/Login.php](file:///C:/laragon/www/kke-sakip/app/Livewire/Auth/Login.php) — NOT a controller, NOT Fortify, NOT Breeze |

## DOMAIN CORE

5-level hierarchy underpins scoring:

```
Komponen → SubKomponen → KriteriaKomponen → BuktiDukung → Penilaian
                                                          ↓
                                                  PenilaianHistory (audit trail)
```

- **OPD** = government agency being evaluated. Master data, has `esakip_opd_id` for external mapping.
- **Tahun** = evaluation year. **Almost every query is scoped by `tahun_id`** — forgetting this is the #1 bug source.
- **Role** (custom `role` table, NOT Spatie) — 7 roles: `admin`, `verifikator_bappeda`, `verifikator_bag_organisasi`, `verifikator_inspektorat`, `penjamin`, `penilai`, `opd`.
- **Penilaian** = a single scoring entry. Per-OPD × per-KriteriaKomponen × per-Role.

## CONVENTIONS (project-specific only)

- **Snake_case relation methods** (`kriteria_komponen()`, `bukti_dukung()`) — Laravel convention is camelCase. **Match existing style — do NOT rename.**
- **Singular custom table names**: `role`, `penilaian`, `bukti_dukung`. Eloquent's plural auto-mapping is overridden via `protected $table`.
- **`protected $guarded = ['id']`** used everywhere — NEVER `$fillable`.
- **All comments and identifiers may be in Indonesian** (`tahun`, `penilaian`, `bukti_dukung`, `keterangan`). Don't translate to English.
- **No FormRequest classes** — validation happens inline inside Livewire components.
- **No Policies / Gates** — authorization is route-level (`EnsureUserHasRole`) only.
- **No repository pattern, no action classes** — fat Livewire components + fat Eloquent models is the style.
- **No queues used in practice** despite `QUEUE_CONNECTION=database` — sync runs synchronously inside Livewire actions.

## COMMANDS

```bash
composer setup              # First-time: install + .env + key + migrate + npm install + build
composer dev                # Concurrent: artisan serve + queue:listen + pail + vite — USE THIS for local dev
composer test               # config:clear + php artisan test (PHPUnit, NOT Pest)
php artisan migrate:fresh --seed  # Reset DB; safe in dev — SQLite at database/database.sqlite
php artisan pint            # Lint/format (Laravel Pint, default ruleset)
npm run build               # Production Vite build
php artisan storage:link    # REQUIRED — bukti dukung uploads need public/storage symlink
```

## ANTI-PATTERNS (THIS PROJECT)

- **DO NOT** add files to project root. Root is already polluted with `test-*.php`, `validate-*.php`, `check-*.php`, `debug_*.php` scratch scripts. **They are not part of the app boot path**; they are ad-hoc maintenance scripts run manually via `php <file>`. Add new scratch work to a gitignored `tmp/` directory instead.
- **DO NOT** translate Indonesian column/method/role names to English. Spec, DB, UI, and external eSAKIP API all speak Indonesian.
- **DO NOT** introduce Spatie permissions, Fortify, Breeze, Jetstream, or Sanctum — auth is **fully custom** via `EnsureUserHasRole` middleware. Adding a competing system will create silent bypasses.
- **DO NOT** create a Controller for a new page. **Use a Livewire component.** Routes map directly to Livewire classes.
- **DO NOT** use Eloquent `$fillable` — every model in this codebase uses `$guarded = ['id']`. Mixing is a code-review red flag.
- **DO NOT** assume `FileBuktiDukung` is live. It's deprecated — file storage moved into the `penilaian` table itself. See [app/Models/BuktiDukung.php:51](file:///C:/laragon/www/kke-sakip/app/Models/BuktiDukung.php#L51).
- **DO NOT** run real tests — none exist. `tests/Feature/ExampleTest.php` and `tests/Unit/ExampleTest.php` are stock Laravel stubs. CI does not exist (no `.github/workflows/`).

## GOTCHAS

- **17 Indonesian markdown docs at root** (`PANDUAN_*.md`, `CARA_*.md`, `TROUBLESHOOTING_*.md`, `DATABASE_SINKRONISASI.md`, `SMART_SYNC_STRATEGY.md`, `STRUKTUR_LINK_FILE.md`, `IMPLEMENTATION_STATUS.md`, `API_DOCUMENTATION.md`) — these are the **real** project docs. Read them when touching eSAKIP sync, Word export templates, or table-generation features.
- **`bobot` field on BuktiDukung is a computed accessor**, not a column. It requires `kriteria_komponen` to be eager-loaded AND a `bukti_dukung_count` aggregate to be present, or returns 0 silently. See [BuktiDukung.php:57-78](file:///C:/laragon/www/kke-sakip/app/Models/BuktiDukung.php#L57-L78).
- **`config/esakip.php` reads `env('ESAKIP_API_URL')`** which is **missing from `.env.example`**. Defaults to `https://e-sakip.trenggalekkab.go.id/api`. Document this when onboarding.
- **`public/` has 4109 files** but most are pre-bundled vendor assets in `public/assets/libs/`. Treat as build output — do not hand-edit.
- **Timezone is hard-coded** in [config/app.php](file:///C:/laragon/www/kke-sakip/config/app.php) (Asia/Jakarta expected) — sync timestamps assume this.
- **The route `/sinkron-data` does not equal the route `/sinkron-dokumen`** — both Livewire components exist, but only `/sinkron-data` is registered. `SinkronDokumen` is dead code or WIP.
