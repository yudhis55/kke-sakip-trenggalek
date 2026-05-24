# Documentation Index — `.sisyphus/docs/`

Audit menyeluruh KKE-SAKIP, ditulis sebagai pencegahan kesalahan saat development.

## File-file di sini

| Doc | Size | Untuk |
|-----|------|-------|
| [ROLES.md](./ROLES.md) | ~8.9 KB | Permission matrix per role × fitur, deadline gating, OPD scoping |
| [FLOWS.md](./FLOWS.md) | ~29.7 KB | End-to-end flow per fitur (login, mapping, lembar kerja, monitoring, sync, ekspor, dll) |
| [DEAD_CODE.md](./DEAD_CODE.md) | ~23.3 KB | Inventory model/tabel/kolom/komponen yang sudah deprecated — JANGAN extend |
| [KNOWN_BUGS.md](./KNOWN_BUGS.md) | ~24.3 KB | 15 bug/inkonsistensi yang ditemukan saat audit, dengan severity & rekomendasi fix |

## Urutan baca rekomendasi

1. **ROLES.md** — pahami siapa bisa apa
2. **DEAD_CODE.md** — tahu yang harus dihindari
3. **KNOWN_BUGS.md** — tahu masalah yang sudah ada
4. **FLOWS.md** — gunakan sebagai referensi tiap kali sentuh fitur

## Pemetaan ke AGENTS.md hierarchy

Dokumen ini melengkapi (bukan menggantikan) AGENTS.md di root + 5 subdirektori:
- [/AGENTS.md](file:///C:/laragon/www/kke-sakip/AGENTS.md) — overview project
- [/app/Livewire/AGENTS.md](file:///C:/laragon/www/kke-sakip/app/Livewire/AGENTS.md) — controller layer
- [/app/Models/AGENTS.md](file:///C:/laragon/www/kke-sakip/app/Models/AGENTS.md) — domain schema
- [/app/Services/AGENTS.md](file:///C:/laragon/www/kke-sakip/app/Services/AGENTS.md) — eSAKIP sync service
- [/database/AGENTS.md](file:///C:/laragon/www/kke-sakip/database/AGENTS.md) — migration/seeder ordering
- [/resources/views/AGENTS.md](file:///C:/laragon/www/kke-sakip/resources/views/AGENTS.md) — Blade + Livewire templates

AGENTS.md = peta navigasi (cepat, kering). Doc di `.sisyphus/docs/` = detail mendalam.

## Top-3 bugs untuk fix DULU sebelum development feature baru

1. **BUG-001** — `penilaian_di` query salah di Monitoring (3 file). Mode kriteria gagal render.
2. **BUG-002** — `BuktiDukung::bobot` accessor diam-diam return 0 jika relasi tidak eager-loaded. Skor akhir OPD jadi 0.
3. **BUG-004** — Semua role bisa akses semua URL via direct navigation (sidebar visibility ≠ route protection).

Detail di [KNOWN_BUGS.md](./KNOWN_BUGS.md).

## Top recommendations cleanup (low-risk)

1. Pindah 14 root scratch PHP scripts ke `tmp/` (gitignored)
2. Hapus `database/seeders/JenisNilai.php` (orphan, bukan `JenisNilaiSeeder.php`)
3. Hapus `app/Livewire/Dashboard/SinkronDokumen.php` + view (unrouted)
4. Update `.env.example` tambah `ESAKIP_API_URL`, `ESAKIP_SYNC_TIMEOUT`, `ESAKIP_SYNC_RETRY`
5. Hapus `welcome.blade.php` (tidak pernah dirender)
6. Hapus `IMPLEMENTATION_STATUS.md` (status sudah selesai)

Detail di [DEAD_CODE.md](./DEAD_CODE.md) section 1-9.
