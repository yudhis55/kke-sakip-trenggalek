# app/Livewire — Page-Per-Class Controllers

**This is the controller layer of the application.** 16 Livewire 3 components, each bound 1:1 to a route in [routes/web.php](file:///C:/laragon/www/kke-sakip/routes/web.php). Blade views mirror this tree at [resources/views/livewire/](file:///C:/laragon/www/kke-sakip/resources/views/livewire).

## STRUCTURE

```
Livewire/
├── Auth/
│   ├── Login.php              # /login — Auth::attempt + session::regenerate
│   └── Logout.php             # POST logout
└── Dashboard/
    ├── Dashboard.php          # /dashboard — landing page after login
    ├── Mapping.php            # /mapping — OPD ↔ Kriteria assignment
    ├── Monitoring.php         # /monitoring — top-level supervision view
    ├── Monitoring/
    │   ├── KriteriaKomponen.php          # /monitoring/sub-komponen/{id}/kriteria-komponen
    │   └── KriteriaKomponen/
    │       └── BuktiDukung.php           # /monitoring/.../bukti-dukung
    ├── LembarKerja.php        # /lembar-kerja — worksheet (renamed from old name; do NOT confuse with /monitoring)
    ├── RekapPenolakan.php     # /rekap-penolakan — rejection summary
    ├── RekapPerbaikan.php     # /rekap-perbaikan — improvement summary
    ├── EksporLaporan.php      # /ekspor-laporan — Word export via phpoffice/phpword
    ├── Pengaturan.php         # /pengaturan — settings (admin only by convention, NOT enforced)
    ├── SinkronData.php        # /sinkron-data — invokes EsakipSyncService
    ├── SinkronDokumen.php     # ⚠️ NOT routed — dead/WIP, not in web.php
    ├── TahunDropdown.php      # nested component — global year selector
    └── CountdownTimer.php     # nested component — used in dashboard
```

## CONVENTIONS

- **One class = one full page.** Components that are embedded (not routed) are `TahunDropdown` and `CountdownTimer` — recognize them by their lack of a route entry.
- **All routes apply [EnsureUserHasRole](file:///C:/laragon/www/kke-sakip/app/Http/Middleware/EnsureUserHasRole.php) at the group level** with the full 7-role list. There is **no per-component authorization**. If you need finer access (e.g. admin-only `Pengaturan`), enforce inside the component's `mount()` / actions.
- **Mount injects route params**: e.g. `BuktiDukung::mount($sub_komponen_id, $kriteria_komponen_id)`. Mirror the URL placeholder names exactly.
- **`#[Layout('components.layouts.app')]` attribute** is used to pick the layout (or `auth` for Login). No global default.
- **State held as public properties.** Filters (`$tahunId`, `$opdId`, `$search`) are public. Use `#[Url]` from `ralphjsmit/livewire-urls` when filters must persist in querystring.
- **Validation is inline** inside actions via `$this->validate([...])`. NO FormRequest classes anywhere.
- **`flash()->success(...)` / `flash()->error(...)`** from `php-flasher/flasher-laravel` for toasts. NOT `session()->flash()`.

## ANTI-PATTERNS

- **DO NOT** add a Controller class. New page → new Livewire component + new route entry in the existing role-protected group.
- **DO NOT** call the eSAKIP API directly from a component. Inject / instantiate [EsakipSyncService](file:///C:/laragon/www/kke-sakip/app/Services/EsakipSyncService.php).
- **DO NOT** access `Auth::user()->role->nama` without the role relation eager-loaded — it WILL cause N+1 on table views. Use `User::with('role')->find(...)` or rely on the request lifecycle's eager load.
- **DO NOT** replicate logic from `SinkronDokumen.php`. It is unrouted — treat as dead code unless explicitly reviving it.
- **DO NOT** create new top-level subdirectories under `Livewire/`. Everything either goes under `Auth/` or `Dashboard/`. Nested feature dirs (e.g. `Dashboard/Monitoring/`) follow the URL hierarchy.
