# ROLES — Permission & Capability Matrix

> Read this BEFORE touching any feature. Every Livewire component branches on `Auth::user()->role->jenis`. Forgetting a branch = silently breaking a role.

## The 7 Roles (immutable, seeded by [RoleSeeder](file:///C:/laragon/www/kke-sakip/database/seeders/RoleSeeder.php))

| `role.id` | `role.nama` (login auth key) | `role.jenis` (logic key) | Description |
|-----------|------------------------------|--------------------------|-------------|
| 1 | `admin` | `admin` | System administrator. No deadline gate. Sees ALL OPDs. Configures Mapping, Pengaturan, Sinkron. |
| 2 | `verifikator_bappeda` | `verifikator` | Verifies criteria assigned via `bukti_dukung.role_id = 2`. |
| 3 | `verifikator_bag_organisasi` | `verifikator` | Verifies criteria assigned via `bukti_dukung.role_id = 3`. |
| 4 | `verifikator_inspektorat` | `verifikator` | Verifies criteria assigned via `bukti_dukung.role_id = 4`. |
| 5 | `penjamin` | `penjamin` | Quality assurance pass after verifikator. Re-scores. |
| 6 | `penilai` | `penilai` | Final evaluator. Locks the score. |
| 7 | `opd` | `opd` | Government agency self-assessor. Uploads evidence + own score. Tied to ONE `opd_id`. |

### CRITICAL: `nama` vs `jenis` — both are real keys, different uses

- **`role.nama`** → string-checked by [EnsureUserHasRole](file:///C:/laragon/www/kke-sakip/app/Http/Middleware/EnsureUserHasRole.php) middleware against the **route-level allow-list** in [routes/web.php:39](file:///C:/laragon/www/kke-sakip/routes/web.php#L39). Each verifikator subtype is a SEPARATE allowed name.
- **`role.jenis`** → coarser bucket used everywhere INSIDE Livewire components (`Auth::user()->role->jenis == 'verifikator'`). Lumps the 3 verifikator subtypes into one branch.

**Implication**: middleware allows access by exact name; UI/business logic groups them. The 3 verifikator users see the SAME features. They are differentiated only by **which `bukti_dukung` rows are assigned to their `role_id`** (column `bukti_dukung.role_id`).

---

## Per-Feature Capability Matrix

> ✅ = full access  ·  👁️ = view-only  ·  🔒 = blocked  ·  ⏰ = gated by [Setting](file:///C:/laragon/www/kke-sakip/app/Models/Setting.php) deadline window  ·  🎯 = scoped to own `opd_id` / `role_id`

| Feature / Route | admin | verifikator_* | penjamin | penilai | opd |
|----------------|-------|---------------|----------|---------|-----|
| `/login` | guest | guest | guest | guest | guest |
| `/dashboard` | ✅ all OPDs | ✅ own progress | ✅ own progress | ✅ own progress | 🎯 own OPD only |
| `/mapping` (sidebar shown via `app.blade.php:287`) | ✅ | 🔒 hidden | 🔒 hidden | 🔒 hidden | 🔒 hidden |
| `/lembar-kerja` (sidebar always visible) | ✅ all OPDs | ✅ filtered to bukti_dukung.role_id == own | ✅ all OPDs | ✅ all OPDs | 🎯 own OPD pre-selected, others hidden |
| `/monitoring` | ✅ all OPDs | ✅ all OPDs (read-only) | ✅ all OPDs | ✅ all OPDs | 🎯 own OPD only |
| `/monitoring/.../bukti-dukung` upload + score | ✅ ⏰ none | ✅ ⏰ verifikator window | ✅ ⏰ penjamin window | ✅ ⏰ penilai window | ✅ ⏰ mandiri window 🎯 own OPD |
| `/rekap-penolakan` | 🔒 hidden | 🔒 hidden | 🔒 hidden | 🔒 hidden | ✅ 🎯 own OPD only |
| `/rekap-perbaikan` | 🔒 hidden | ✅ 🎯 own role_id | ✅ 🎯 own role_id | ✅ 🎯 own role_id | 🔒 hidden |
| `/ekspor-laporan` (Word export) | ✅ | ✅ | ✅ | ✅ | ✅ 🎯 own OPD |
| `/sinkron-data` (eSAKIP sync) | ✅ | ✅ | ✅ | ✅ | ✅ |
| `/pengaturan` (deadlines, users, tahun, etc.) | ✅ | 🔒 hidden | 🔒 hidden | 🔒 hidden | 🔒 hidden |

> **Visibility vs. Authorization gap**: hidden in sidebar ≠ blocked. Routes `/mapping`, `/pengaturan`, `/rekap-penolakan`, `/rekap-perbaikan` rely on UI-only conditional `@if (Auth::user()->role->jenis == ...)`. The middleware [routes/web.php:39](file:///C:/laragon/www/kke-sakip/routes/web.php#L39) allows ALL 7 roles into ALL routes. **Direct URL access bypasses the sidebar gate.** Do not rely on sidebar visibility for security; if a feature is truly admin-only, also enforce inside the component's `mount()` or actions.

---

## Deadline Gating (`Setting` table per `tahun_id`)

Each year has 8 datetime columns: `buka_*` and `tutup_*` for each of `mandiri`, `verifikator`, `penjamin`, `penilai`. The active window dictates whether the role can submit/upload/score that year.

```
tahun → setting → 8 deadline columns
                       ↓
       cekAksesWaktu($jenis) → checks Carbon::now() vs setting.{buka,tutup}_penilaian_{jenis}
                       ↓
       Returns ['allowed'=>bool, 'message'=>string]
```

- **admin bypasses ALL deadlines** ([BuktiDukung.php:309](file:///C:/laragon/www/kke-sakip/app/Livewire/Dashboard/Monitoring/KriteriaKomponen/BuktiDukung.php#L309)).
- Used in [LembarKerja](file:///C:/laragon/www/kke-sakip/app/Livewire/Dashboard/LembarKerja.php) and [Monitoring/.../BuktiDukung](file:///C:/laragon/www/kke-sakip/app/Livewire/Dashboard/Monitoring/KriteriaKomponen/BuktiDukung.php) before EVERY upload, score-save, verify action.
- [CountdownTimer](file:///C:/laragon/www/kke-sakip/app/Livewire/Dashboard/CountdownTimer.php) shows the active deadline in the topbar, except for admin.

**`role.jenis = 'verifikator'` (the 3 verifikator subtypes) all share `tutup_penilaian_verifikator`.** There is no per-subtype deadline.

---

## OPD Scoping (the silent permission gate)

For `role.jenis = 'opd'`:
- `Auth::user()->opd_id` is **non-null** (FK to `opd.id`)
- `LembarKerja::opdList()` returns only their own OPD: `Opd::where('id', Auth::user()->opd_id)->get()`
- `Monitoring::opdList()` returns `collect([])` (empty — OPDs don't browse other OPDs at all on /monitoring)
- All scoring queries filter `where('opd_id', Auth::user()->opd_id)`
- Their `User` row sets `opd_id` (see [UserSeeder](file:///C:/laragon/www/kke-sakip/database/seeders/UserSeeder.php))

For all other roles, `users.opd_id IS NULL`. They iterate over OPDs.

---

## Role-to-bukti_dukung mapping (verifikator scoping)

`bukti_dukung.role_id` (set in Mapping by admin) determines **which verifikator subtype scores it**. So:

- **Verifikator Bappeda** (`role_id=2`) sees only `bukti_dukung` where `role_id = 2`. Other bukti dukung are filtered out at every layer ([LembarKerja.php:452-455](file:///C:/laragon/www/kke-sakip/app/Livewire/Dashboard/LembarKerja.php#L452-L455)).
- Same pattern for Bag Organisasi (3) and Inspektorat (4).
- **Penjamin and Penilai see ALL bukti_dukung** regardless of `role_id`. They aren't filtered by this column.

This is the ONLY structural reason verifikator splits into 3 subtypes. From the user's perspective, a verifikator only sees their slice of the worksheet.

---

## Action permissions (who can do what on `penilaian` rows)

| Action | OPD | Verifikator (own slice) | Penjamin | Penilai | Admin |
|--------|-----|------------------------|----------|---------|-------|
| Upload file (`link_file`, role-specific row) | ✅ as `role_id=opd` | 🔒 (cannot upload) | 🔒 | 🔒 | 🔒 (no UI path) |
| Set `tingkatan_nilai_id` (the score) | ✅ on own row | ✅ on own row | ✅ on own row | ✅ on own row | 🔒 (no UI path) |
| Set `is_verified=true/false` (verify/reject) | 🔒 | ✅ on rejected verifikator row | ✅ on penjamin row | 🔒 (penilai doesn't verify, only scores) | 🔒 |
| Mark `is_perubahan=true` (revision) | ✅ auto when re-uploading | ✅ auto | ✅ auto | ✅ auto | — |
| Sync from eSAKIP | ✅ | ✅ | ✅ | ✅ | ✅ |
| Edit Mapping (Komponen tree) | 🔒 | 🔒 | 🔒 | 🔒 | ✅ |
| Set `Setting` deadlines | 🔒 | 🔒 | 🔒 | 🔒 | ✅ |
| Create users / OPDs / Tahun | 🔒 | 🔒 | 🔒 | 🔒 | ✅ |

> **Penalty rule**: a `penilaian` row is keyed by `(kriteria_komponen_id, opd_id, role_id, bukti_dukung_id?)`. **Each role writes to its own row.** The OPD's row contains the file upload (`link_file`). The verifikator/penjamin/penilai rows contain that role's `tingkatan_nilai_id`. The OPD does NOT score on the verifikator's row.

---

## Anti-patterns to avoid

- **DO NOT** use `Auth::user()->role->nama` for business logic. Use `jenis`. The 3 verifikator subtypes will all be missed otherwise.
- **DO NOT** assume `opd_id` is set for non-OPD users. They write `null`.
- **DO NOT** forget the deadline check `cekAksesWaktu()` when adding a new write action. Without it, you've just bypassed the year's window.
- **DO NOT** add a role to the seeded list without also: (1) adding it to the route allow-list in [routes/web.php:39](file:///C:/laragon/www/kke-sakip/routes/web.php#L39), (2) handling its `jenis` in every `switch`/`if` chain in components, (3) considering whether it needs a deadline column in `setting`.
- **DO NOT** rely on sidebar conditionals for security. URL access is unprotected. Add component-level checks if needed.
