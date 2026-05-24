# app/Models — Domain Schema

21 Eloquent models. Domain is a **5-level scoring hierarchy** with three reviewer tiers and a full audit trail.

## DOMAIN HIERARCHY

```
Tahun ────┐
          │
Komponen ─→ SubKomponen ─→ KriteriaKomponen ─→ BuktiDukung ─→ Penilaian
                                                              │
                                                              ↓
                                                       PenilaianHistory
OPD ─────────────────────────────────────────────────────→ (FK on Penilaian)
Role ────────────────────────────────────────────────────→ (FK on Penilaian — which reviewer tier scored)
```

## ENTITIES (grouped by role)

| Model | Table | Role |
|------|-------|------|
| [User.php](file:///C:/laragon/www/kke-sakip/app/Models/User.php) | `users` | Standard Laravel auth + `role_id` FK |
| [Role.php](file:///C:/laragon/www/kke-sakip/app/Models/Role.php) | `role` (singular!) | 7 fixed roles seeded via [RoleSeeder](file:///C:/laragon/www/kke-sakip/database/seeders/RoleSeeder.php) |
| [Opd.php](file:///C:/laragon/www/kke-sakip/app/Models/Opd.php) | `opds` | Government agency; `esakip_opd_id` maps to external API |
| [Tahun.php](file:///C:/laragon/www/kke-sakip/app/Models/Tahun.php) | `tahuns` | Evaluation year — scope EVERY query by this |
| [Komponen.php](file:///C:/laragon/www/kke-sakip/app/Models/Komponen.php) | `komponens` | Top-level scoring component |
| [SubKomponen.php](file:///C:/laragon/www/kke-sakip/app/Models/SubKomponen.php) | `sub_komponens` | Mid-level |
| [KriteriaKomponen.php](file:///C:/laragon/www/kke-sakip/app/Models/KriteriaKomponen.php) | `kriteria_komponens` | Leaf criterion; holds `bobot` (weight) |
| [BuktiDukung.php](file:///C:/laragon/www/kke-sakip/app/Models/BuktiDukung.php) | `bukti_dukung` (singular!) | Evidence row; `bobot` is **computed accessor**, not a column |
| [Penilaian.php](file:///C:/laragon/www/kke-sakip/app/Models/Penilaian.php) | `penilaian` (singular!) | The scoring entry. Stores `link_file` (json), `is_perubahan`, `page_number`, `esakip_synced_at` |
| [PenilaianHistory.php](file:///C:/laragon/www/kke-sakip/app/Models/PenilaianHistory.php) | `penilaian_history` | Audit trail; written via `Penilaian::recordHistory()` |
| [PenilaianMandiri / PenilaianVerifikator / PenilaianPenjamin / PenilaianPenilai](file:///C:/laragon/www/kke-sakip/app/Models) | … | Per-tier views/wrappers over Penilaian |
| [TingkatanNilai.php](file:///C:/laragon/www/kke-sakip/app/Models/TingkatanNilai.php) | `tingkatan_nilais` | Score levels (A/B/C/D/E with bobot multipliers) |
| [JenisNilai.php](file:///C:/laragon/www/kke-sakip/app/Models/JenisNilai.php) | `jenis_nilais` | Scoring type metadata |
| [RiwayatSinkron.php](file:///C:/laragon/www/kke-sakip/app/Models/RiwayatSinkron.php) | `riwayat_sinkrons` | eSAKIP sync log |
| [TemplateLaporan / KontenLaporan](file:///C:/laragon/www/kke-sakip/app/Models) | … | Word-export template + content blocks |
| [Setting.php](file:///C:/laragon/www/kke-sakip/app/Models/Setting.php) | `settings` | Key-value config |
| [FileBuktiDukung.php](file:///C:/laragon/www/kke-sakip/app/Models/FileBuktiDukung.php) | **DEPRECATED** | File storage moved into `penilaian.link_file` (json). Do not extend. |

## CONVENTIONS

- **Snake-case relation methods**: `kriteria_komponen()`, `bukti_dukung()`, `sub_komponen()`. Calling `$model->kriteriaKomponen` will return null silently.
- **Singular table override**: `role`, `penilaian`, `bukti_dukung`, `penilaian_history`. Always declare with `protected $table = '...';` on new models that don't follow Laravel's plural rule.
- **`protected $guarded = ['id']`** is the universal pattern. Never `$fillable`.
- **Casts go in `protected $casts`**: `link_file => array`, `is_perubahan => boolean`, `esakip_synced_at => datetime`. Add new cast keys here, not in accessors.
- **`recordHistory()` is the only blessed way to write `PenilaianHistory`** — see [Penilaian.php:59](file:///C:/laragon/www/kke-sakip/app/Models/Penilaian.php#L59). Never `PenilaianHistory::create(...)` directly from Livewire.

## ANTI-PATTERNS

- **DO NOT** rename `bukti_dukung()` etc. to camelCase. Every call-site relies on the existing names; Eloquent magic accessor `$model->bukti_dukung` resolves to the snake_case method.
- **DO NOT** add a relation to `FileBuktiDukung` on `Penilaian` or `BuktiDukung`. The commented-out blocks at [Penilaian.php:25-29](file:///C:/laragon/www/kke-sakip/app/Models/Penilaian.php#L25-L29) and [BuktiDukung.php:51-55](file:///C:/laragon/www/kke-sakip/app/Models/BuktiDukung.php#L51-L55) document the deprecation. Files live in `penilaian.link_file`.
- **DO NOT** access `$buktiDukung->bobot` without eager-loading `kriteria_komponen` AND aggregating `withCount('bukti_dukung')` on it — the accessor returns 0 otherwise (see [BuktiDukung.php:62-72](file:///C:/laragon/www/kke-sakip/app/Models/BuktiDukung.php#L62-L72)).
- **DO NOT** introduce Spatie or other RBAC packages. Role membership is a single `role_id` column on `users`, checked by name string via [EnsureUserHasRole](file:///C:/laragon/www/kke-sakip/app/Http/Middleware/EnsureUserHasRole.php).
