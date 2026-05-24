# resources/views — Blade + Livewire Templates

16 blade files. Structure mirrors [app/Livewire/](file:///C:/laragon/www/kke-sakip/app/Livewire) one-to-one.

## STRUCTURE

```
views/
├── welcome.blade.php           # UNUSED — stock Laravel landing; '/' redirects to /login
├── components/
│   └── layouts/
│       ├── app.blade.php       # Main authenticated layout — loads Vite assets, sidebar, flasher
│       └── auth.blade.php      # Login layout — no nav, centered card
└── livewire/
    ├── auth/
    │   └── login.blade.php
    └── dashboard/              # Mirrors app/Livewire/Dashboard/ — one .blade.php per component
        ├── dashboard.blade.php
        ├── ekspor-laporan.blade.php
        ├── lembar-kerja.blade.php
        ├── mapping.blade.php
        ├── monitoring.blade.php
        ├── pengaturan.blade.php
        ├── rekap-penolakan.blade.php
        ├── rekap-perbaikan.blade.php
        ├── sinkron-data.blade.php
        ├── sinkron-dokumen.blade.php    # ⚠️ component unrouted — view exists but is unreachable
        └── monitoring/
            └── kriteria-komponen/
                ├── kriteria-komponen.blade.php
                └── bukti-dukung.blade.php
```

## CONVENTIONS

- **Two layouts only**: `components.layouts.app` (authenticated dashboard) and `components.layouts.auth` (login). New pages should reuse them via Livewire's `#[Layout('components.layouts.app')]` attribute, not via `@extends`.
- **Vite assets** loaded once in `app.blade.php` via `@vite(['resources/css/app.css', 'resources/js/app.js'])`. Do not add `@vite` calls in individual livewire views.
- **Tailwind v4** with `@import "tailwindcss";` in [resources/css/app.css](file:///C:/laragon/www/kke-sakip/resources/css/app.css). No `tailwind.config.js` — v4 is config-via-CSS. Custom utilities/themes go in the CSS file.
- **Vendor UI assets** (icons, fonts, third-party JS) live in [public/assets/libs/](file:///C:/laragon/www/kke-sakip/public/assets/libs) and are referenced from `app.blade.php` with `asset('assets/libs/...')`. They are pre-bundled — not part of the Vite pipeline.
- **Flasher toasts** appear automatically via `@php(flasher()->render())` (or similar) in the layout. Trigger them from Livewire actions with `flash()->success(...)` / `flash()->error(...)`.
- **Livewire view files are kebab-case** (`kriteria-komponen.blade.php`) while components are PascalCase (`KriteriaKomponen.php`). Livewire auto-resolves the mapping.
- **`wire:model.live`** is fine for filter/search inputs that should trigger re-render. Use `wire:model.blur` or `wire:model.lazy` for textareas to avoid roundtrips per keystroke.

## ANTI-PATTERNS

- **DO NOT** delete `welcome.blade.php` even though `/` redirects to `/login`. Some maintenance scripts may still reference it; removal is a separate cleanup task.
- **DO NOT** load CSS/JS via `<link>`/`<script>` tags in the layout when the asset is in `resources/`. Use `@vite()`. Only `public/assets/libs/` vendor files use direct `asset()` references.
- **DO NOT** introduce a new layout for one-off pages. Restyle within the existing two.
- **DO NOT** put logic in views. Computed values come from Livewire `#[Computed]` properties on the component class.
- **DO NOT** translate UI strings to English. UI is Indonesian end-to-end (matches the spec and external eSAKIP API labels).
