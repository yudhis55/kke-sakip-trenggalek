# KNOWN_BUGS — Bug, Inkonsistensi, & Footgun yang Sudah Diketahui

> **TUJUAN**: Mendokumentasikan masalah yang ditemukan saat audit kode supaya developer tidak menabrak masalah yang sama atau ikut-ikutan menulis pola buggy.

> Setiap entry: **lokasi**, **gejala**, **akar penyebab**, **dampak**, **perbaikan yang direkomendasikan**.

---

## BUG-001: `penilaian_di` dirujuk dari `sub_komponen` di 3 tempat (silent null)

### Gejala
Code Monitoring meng-cek `$kriteria->sub_komponen->penilaian_di === 'kriteria'`, padahal kolom `penilaian_di` ada di tabel `kriteria_komponen` (bukan `sub_komponen`).

### Akar Penyebab
Migration [2025_12_01_032635_create_kriteria_komponens_table.php:22](file:///C:/laragon/www/kke-sakip/database/migrations/2025_12_01_032635_create_kriteria_komponens_table.php#L22) define `penilaian_di` di tabel `kriteria_komponen`. Tabel `sub_komponen` ([migration 2025_12_01_032630](file:///C:/laragon/www/kke-sakip/database/migrations/2025_12_01_032630_create_sub_komponens_table.php)) **TIDAK** punya kolom `penilaian_di`.

Kemungkinan dulu kolom ini ada di `sub_komponen`, lalu dipindah ke `kriteria_komponen`. Komponen `LembarKerja.php` sudah update ([komentar di line 523](file:///C:/laragon/www/kke-sakip/app/Livewire/Dashboard/LembarKerja.php#L523): `// Sekarang penilaian_di ada di kriteria_komponen, bukan sub_komponen`), TAPI komponen Monitoring belum:

### Lokasi yang masih buggy
| File | Line | Code |
|------|------|------|
| [Monitoring/KriteriaKomponen.php](file:///C:/laragon/www/kke-sakip/app/Livewire/Dashboard/Monitoring/KriteriaKomponen.php#L25) | 25 | `return $this->subKomponen?->penilaian_di === 'kriteria';` |
| [Monitoring/KriteriaKomponen/BuktiDukung.php](file:///C:/laragon/www/kke-sakip/app/Livewire/Dashboard/Monitoring/KriteriaKomponen/BuktiDukung.php#L87) | 87 | `return $kriteria->sub_komponen->penilaian_di === 'kriteria';` |
| [Monitoring.php](file:///C:/laragon/www/kke-sakip/app/Livewire/Dashboard/Monitoring.php#L119) | 119 | `if ($kriteria->penilaian_di == 'kriteria')` ← INI BENAR (kriteria_komponen langsung) |
| [Monitoring.php](file:///C:/laragon/www/kke-sakip/app/Livewire/Dashboard/Monitoring.php#L148) | 148 | `if ($kriteria->penilaian_di == 'kriteria')` ← INI BENAR |
| [resources/views/livewire/dashboard/monitoring/kriteria-komponen/bukti-dukung.blade.php](file:///C:/laragon/www/kke-sakip/resources/views/livewire/dashboard/monitoring/kriteria-komponen/bukti-dukung.blade.php) | 173 | `$this->kriteriaKomponen->sub_komponen->penilaian_di == 'bukti'` |

### Dampak
- Property accessor `$subKomponen->penilaian_di` adalah **dynamic property** (tidak ada di tabel/cast). Eloquent return `null` silently.
- Kondisi `=== 'kriteria'` selalu `false`, sehingga **mode `'bukti'` selalu yang aktif** untuk page Monitoring.
- Akibatnya:
  - Mode kriteria di Monitoring tidak pernah render UI yang seharusnya
  - Page yang mestinya menampilkan upload+score level kriteria malah jalan dengan logika level bukti
  - Worksheet di Monitoring tampak inkonsisten dengan LembarKerja (LembarKerja sudah benar)

### Perbaikan yang Direkomendasikan
Ganti 4 occurence:

```php
// BEFORE (BUGGY):
$this->subKomponen?->penilaian_di === 'kriteria'
$kriteria->sub_komponen->penilaian_di === 'kriteria'
$this->kriteriaKomponen->sub_komponen->penilaian_di == 'bukti'

// AFTER (FIXED):
$this->kriteriaKomponen?->penilaian_di === 'kriteria'   // di KriteriaKomponen.php
$kriteria->penilaian_di === 'kriteria'                   // di BuktiDukung.php (kriteria sudah di-load)
$this->kriteriaKomponen->penilaian_di == 'bukti'         // di blade
```

### Test setelah fix
1. Login sebagai admin.
2. Buka /monitoring → pilih OPD → pilih komponen yang punya kriteria dengan `penilaian_di='kriteria'`.
3. Pastikan UI render mode kriteria (1 input scoring per kriteria), BUKAN per bukti dukung.

---

## BUG-002: `BuktiDukung::bobot` accessor silently return 0

### Gejala
Skor (`Penilaian::tingkatan_nilai * BuktiDukung::bobot`) sering bernilai 0 di Dashboard/Monitoring/Ekspor walaupun OPD sudah scoring.

### Akar Penyebab
Accessor di [BuktiDukung.php:57-78](file:///C:/laragon/www/kke-sakip/app/Models/BuktiDukung.php#L57-L78):

```php
protected function bobot(): Attribute
{
    return Attribute::make(
        get: function () {
            // Check if kriteria_komponen relationship is loaded
            if (!$this->relationLoaded('kriteria_komponen') || !$this->kriteria_komponen) {
                return 0;   // ← FAIL SILENTLY
            }
            // Check if bukti_dukung_count exists
            if (!isset($this->kriteria_komponen->bukti_dukung_count) ||
                $this->kriteria_komponen->bukti_dukung_count == 0) {
                return 0;   // ← FAIL SILENTLY
            }
            $kriteriaBobot = $this->kriteria_komponen->bobot;
            return round($kriteriaBobot / $this->kriteria_komponen->bukti_dukung_count, 2);
        }
    );
}
```

### Dampak
Bobot bukti dukung butuh 2 hal supaya non-zero:
1. Relasi `kriteria_komponen` harus eager-loaded (via `with()`)
2. KriteriaKomponen harus punya aggregate `bukti_dukung_count` (via `withCount('bukti_dukung')`)

Tanpa keduanya: nilai 0 dikembalikan tanpa exception/warning. **Skor akhir OPD jadi 0.**

### Perbaikan yang Direkomendasikan
Setiap query yang memerlukan `bobot` accessor HARUS:

```php
// PASTIKAN:
$buktiDukungs = BuktiDukung::with([
    'kriteria_komponen' => fn($q) => $q->withCount('bukti_dukung')
])->get();

// Lalu baru:
foreach ($buktiDukungs as $bukti) {
    $bobot = $bukti->bobot;  // sekarang correct
}
```

**Pattern yang sudah aktif** di [Mapping.php::fullMapping()](file:///C:/laragon/www/kke-sakip/app/Livewire/Dashboard/Mapping.php#L70):
```php
Komponen::with([
    'sub_komponen' => fn($q) => $q->withCount('kriteria_komponen')->with([
        'kriteria_komponen' => fn($q) => $q->withCount('bukti_dukung')->with([...])
    ])
])->get();
```

### Recommendation jangka panjang
- Tambahkan **default global scope** di model BuktiDukung untuk auto-load `kriteria_komponen` dengan `withCount('bukti_dukung')`. Tradeoff: heavier query untuk semua call.
- ATAU tambahkan **assertion** di accessor untuk throw exception kalau relasi tidak loaded — fail loud daripada fail silent (development env).

---

## BUG-003: Middleware role check inkonsisten antara `nama` dan `jenis`

### Gejala
Logic role di komponen Livewire pakai `Auth::user()->role->jenis` (coarse), tapi middleware [EnsureUserHasRole](file:///C:/laragon/www/kke-sakip/app/Http/Middleware/EnsureUserHasRole.php) cek `Auth::user()->role->nama` (fine).

### Akar Penyebab
Design choice yang sengaja: ada **3 verifikator subtypes** yang terdaftar di tabel `role` dengan `nama` berbeda (`verifikator_bappeda`, `verifikator_bag_organisasi`, `verifikator_inspektorat`) tapi `jenis` sama (`verifikator`). Middleware butuh granular per nama untuk allow-list, komponen butuh coarse per jenis untuk shared UI logic.

### Dampak (potensial bug saat development)
1. Tambah role baru di [RoleSeeder.php](file:///C:/laragon/www/kke-sakip/database/seeders/RoleSeeder.php) → **WAJIB** juga update allow-list di [routes/web.php:39](file:///C:/laragon/www/kke-sakip/routes/web.php#L39):
   ```php
   Route::middleware([EnsureUserHasRole::class . ':admin,verifikator_bappeda,...,role_baru'])->group(...)
   ```
   Kalau lupa: user role baru tidak bisa akses dashboard sama sekali.

2. Pakai `role->nama` di komponen → **WAJIB** handle 3 verifikator subtypes secara individual:
   ```php
   // SALAH:
   if (Auth::user()->role->nama == 'verifikator') { ... }   // string ini tidak pernah match
   
   // BENAR (versi fine):
   if (in_array(Auth::user()->role->nama, ['verifikator_bappeda', 'verifikator_bag_organisasi', 'verifikator_inspektorat'])) { ... }
   
   // BENAR (versi coarse, REKOMENDASI):
   if (Auth::user()->role->jenis == 'verifikator') { ... }
   ```

### Perbaikan yang Direkomendasikan
- **GUNAKAN `role->jenis` di SEMUA logic komponen** kecuali kalau memang butuh diferensiasi per subtype (e.g. filter `bukti_dukung.role_id = Auth::user()->role_id` di [LembarKerja.php:454](file:///C:/laragon/www/kke-sakip/app/Livewire/Dashboard/LembarKerja.php#L454) — di sini `role_id` integer dipakai, bukan string nama).
- **`role->nama`** hanya untuk: middleware allow-list dan tampilan profile di topbar ([app.blade.php:197](file:///C:/laragon/www/kke-sakip/resources/views/components/layouts/app.blade.php#L197)).

---

## BUG-004: Visibility ≠ Authorization — sidebar gating tidak menjamin route block

### Gejala
Hidden di sidebar tidak berarti diblokir saat akses langsung URL.

### Akar Penyebab
Middleware [EnsureUserHasRole](file:///C:/laragon/www/kke-sakip/app/Http/Middleware/EnsureUserHasRole.php) di [routes/web.php:39](file:///C:/laragon/www/kke-sakip/routes/web.php#L39) memberi allow-list 7 role:

```php
Route::middleware([EnsureUserHasRole::class . ':admin,verifikator_bappeda,verifikator_bag_organisasi,verifikator_inspektorat,penjamin,penilai,opd'])->group(function () {
    Route::get('/dashboard', ...);
    Route::get('/mapping', ...);
    Route::get('/pengaturan', ...);
    // ... semua route satu group sama
});
```

**Setiap role di-allow ke setiap route.** Yang membatasi akses adalah hanya conditional `@if (Auth::user()->role->jenis == 'admin')` di sidebar untuk hide menu, dan logic internal komponen.

### Dampak
- User OPD bisa GET `/mapping` langsung dan masuk ke komponen Mapping. Komponen tidak gate akses, jadi user OPD bisa _view_ dan _edit_ master data Komponen jika mereka tahu URL.
- Sama untuk `/pengaturan`, `/sinkron-data`.
- Akses langsung ke `/rekap-penolakan` oleh role non-OPD akan render page kosong (computed `rekapPenolakan` return `collect()`), tapi UI tetap bisa diakses.

### Perbaikan yang Direkomendasikan

**Option A (terbaik): split route group per role bracket**
```php
// Admin-only routes
Route::middleware([EnsureUserHasRole::class . ':admin'])->group(function () {
    Route::get('/mapping', Mapping::class)->name('mapping');
    Route::get('/pengaturan', Pengaturan::class)->name('pengaturan');
});

// All authenticated roles
Route::middleware([EnsureUserHasRole::class . ':admin,verifikator_bappeda,...,opd'])->group(function () {
    Route::get('/dashboard', Dashboard::class)->name('dashboard');
    // ...
});
```

**Option B (in-component check)**
```php
// Pengaturan::mount()
public function mount() {
    if (Auth::user()->role->jenis !== 'admin') {
        abort(403);
    }
    // ...
}
```

---

## BUG-005: `JenisNilai.php` (tanpa suffix Seeder) — orphan seeder

### Gejala
Ada dua file mirip nama: `database/seeders/JenisNilai.php` dan `database/seeders/JenisNilaiSeeder.php`. Yang dipanggil `DatabaseSeeder` adalah `JenisNilaiSeeder` (yang benar).

### Akar Penyebab
Convention naming Laravel adalah `<Name>Seeder.php`. File `JenisNilai.php` (tanpa suffix) adalah peninggalan rename. Bodynya kosong (`run()` cuma `//`).

### Dampak
Tidak break apa pun (tidak dipanggil), tapi **bingungkan IDE & developer baru** (autocomplete munculkan dua opsi).

### Perbaikan yang Direkomendasikan
Hapus file [database/seeders/JenisNilai.php](file:///C:/laragon/www/kke-sakip/database/seeders/JenisNilai.php) (yang kosong). Jangan hapus `JenisNilaiSeeder.php`.

---

## BUG-006: `config/esakip.php` butuh env vars tidak terdaftar di `.env.example`

### Gejala
Sync ke production tidak jalan jika `.env` tidak punya `ESAKIP_API_URL` atau `ESAKIP_SYNC_TIMEOUT` / `ESAKIP_SYNC_RETRY`.

### Akar Penyebab
[config/esakip.php](file:///C:/laragon/www/kke-sakip/config/esakip.php) baca:
```php
'api_base_url' => env('ESAKIP_API_URL', 'https://e-sakip.trenggalekkab.go.id/api'),
'sync.timeout' => env('ESAKIP_SYNC_TIMEOUT', 60),
'sync.retry_count' => env('ESAKIP_SYNC_RETRY', 3),
```

`.env.example` **tidak** mencantumkan satu pun dari ketiganya. Default value ada (Trenggalek production URL), tapi developer baru tidak tahu setting ini bisa di-override.

### Dampak
- Project clone baru langsung pakai URL production untuk testing.
- Tidak ada tanda di onboarding bahwa setting ini configurable.
- Test sync di lokal bisa hit production server eSAKIP secara tidak sengaja.

### Perbaikan yang Direkomendasikan
Update [.env.example](file:///C:/laragon/www/kke-sakip/.env.example) tambah:
```
# eSAKIP Sync Configuration
ESAKIP_API_URL=https://e-sakip.trenggalekkab.go.id/api
ESAKIP_SYNC_TIMEOUT=60
ESAKIP_SYNC_RETRY=3
```

---

## BUG-007: `SinkronData::clearRiwayat()` pakai `truncate()` tanpa konfirmasi UI

### Gejala
Tombol "Hapus semua riwayat" akan langsung TRUNCATE seluruh tabel `riwayat_sinkron` tanpa konfirmasi.

### Akar Penyebab
[SinkronData.php:165](file:///C:/laragon/www/kke-sakip/app/Livewire/Dashboard/SinkronData.php#L165):
```php
public function clearRiwayat()
{
    try {
        RiwayatSinkron::truncate();
        flash()->success('Riwayat sinkronisasi berhasil dibersihkan');
    }
    // ...
}
```

`truncate()` di SQLite/MySQL = irreversible. Tidak ada `confirm()` JS atau modal.

### Dampak
- One-click data loss dari riwayat sinkron.
- Tidak ada audit log siapa yang clear.

### Perbaikan yang Direkomendasikan
- Tambah modal konfirmasi: `wire:click="clearRiwayat()" wire:confirm="..."` (Livewire 3 native).
- ATAU minimal soft-delete dengan `where('synced_at', '<', now()->subMonths(6))` untuk archive.

---

## BUG-008: Empty migrations no-op (cosmetic)

### Gejala
3 migration files punya `up()` body kosong (`//`):

| File | Line |
|------|------|
| [2026_01_13_121635_add_esakip_columns_to_bukti_dukung_table.php](file:///C:/laragon/www/kke-sakip/database/migrations/2026_01_13_121635_add_esakip_columns_to_bukti_dukung_table.php) | 14-16 |
| [2026_02_01_114203_add_status_perbaikan_to_penilaian_history_table.php](file:///C:/laragon/www/kke-sakip/database/migrations/2026_02_01_114203_add_status_perbaikan_to_penilaian_history_table.php) | 14-16 |
| [2026_05_10_120200_fix_predecessor_opd_id_to_esakip_id.php](file:///C:/laragon/www/kke-sakip/database/migrations/2026_05_10_120200_fix_predecessor_opd_id_to_esakip_id.php) | 13-17 (eksplisit komentar no-op) |

### Akar Penyebab
Restrukturisasi migration: dua file dengan timestamp dekat, yang satu jadi no-op setelah isinya pindah ke yang lain.

### Dampak
- Tidak break apa pun di runtime.
- Bingung developer baru — file migration kosong tampak seperti incomplete work.
- `migrate:fresh` jalan tanpa masalah.

### Perbaikan yang Direkomendasikan
Tetap biarkan (DB existing punya record di `migrations` table). Jika cleanup, **JANGAN HAPUS** file-nya — itu akan break re-migration history. Tambahkan komentar eksplisit di file: `// no-op (logic moved to <other file>)`.

---

## BUG-009: `welcome.blade.php` tidak terhapus walau tidak dipakai

### Gejala
File [resources/views/welcome.blade.php](file:///C:/laragon/www/kke-sakip/resources/views/welcome.blade.php) ada tapi tidak pernah dirender. GET `/` redirect langsung ke `/login` (lihat [routes/web.php:23-28](file:///C:/laragon/www/kke-sakip/routes/web.php#L23-L28)).

### Dampak
Cosmetic only.

### Perbaikan
Hapus file. Hapus juga komentar [routes/web.php:19-21](file:///C:/laragon/www/kke-sakip/routes/web.php#L19-L21).

---

## BUG-010: `EksporLaporan` kontradiksi UPDATE-OR-CREATE vs DELETE-AND-INSERT

### Gejala
Saat user save laporan, `deskripsi` pakai `updateOrCreate`, tapi `catatan` & `rekomendasi` pakai DELETE-then-INSERT.

### Akar Penyebab
[EksporLaporan.php:220](file:///C:/laragon/www/kke-sakip/app/Livewire/Dashboard/EksporLaporan.php#L220) (deskripsi):
```php
KontenLaporan::updateOrCreate(
    ['type'=>'deskripsi', 'komponen_id'=>$id, 'sub_komponen_id'=>$id, 'opd_id'=>$id, 'tahun_id'=>$id],
    ['konten'=>$konten, 'urutan'=>0]
);
```

[EksporLaporan.php:248-270](file:///C:/laragon/www/kke-sakip/app/Livewire/Dashboard/EksporLaporan.php#L248-L270) (catatan):
```php
KontenLaporan::where(...)->delete();   // hapus dulu
foreach ($catatanList as $konten) {
    KontenLaporan::create([...]);       // insert ulang
}
```

### Dampak
- **Race condition**: jika dua user save bersamaan (atau page reload yang setengah jalan), data catatan/rekomendasi bisa hilang.
- **Audit trail jelek**: `id` row yang berbeda setiap save, sulit untuk track perubahan history.
- **Auto-increment ID di tabel `konten_laporan` cepat boost** karena setiap save catatan/rekomendasi insert ulang.

### Perbaikan yang Direkomendasikan
Konsistenkan ke `updateOrCreate` dengan `urutan` sebagai key:
```php
foreach ($catatanList as $urutan => $konten) {
    KontenLaporan::updateOrCreate(
        ['type'=>'catatan', 'komponen_id'=>$id, 'opd_id'=>$id, 'tahun_id'=>$id, 'urutan'=>$urutan + 1],
        ['konten'=>$konten]
    );
}
// Hapus row dengan urutan > count($catatanList) (jika user kurangi item)
KontenLaporan::where(...)->where('urutan', '>', count($catatanList))->delete();
```

---

## BUG-011: `EksporLaporan` form fields exposed via web tanpa auth check

### Gejala
Page `/ekspor-laporan` allow semua role. Yang menentukan OPD adalah `$opd_selected_id` di form, BUKAN `Auth::user()->opd_id`.

### Akar Penyebab
[EksporLaporan.php:20-21](file:///C:/laragon/www/kke-sakip/app/Livewire/Dashboard/EksporLaporan.php#L20-L21):
```php
public $opd_selected_id;   // user pilih dari dropdown
public $tahun_id;
```

Save methods pakai `$opd_selected_id` apa adanya:
```php
KontenLaporan::updateOrCreate(['opd_id' => $this->opd_selected_id, ...]);
```

### Dampak
User OPD (role `opd`) bisa pilih `$opd_selected_id` ke OPD lain dan **menulis konten laporan** untuk OPD orang lain.

### Perbaikan yang Direkomendasikan
Di mount() / save methods: kalau `Auth::user()->role->jenis == 'opd'`, force `$opd_selected_id = Auth::user()->opd_id` dan jangan biarkan diubah dari UI:
```php
public function mount() {
    if (Auth::user()->role->jenis == 'opd') {
        $this->opd_selected_id = Auth::user()->opd_id;
    }
    // ...
}

private function saveCatatanToDatabase() {
    if (Auth::user()->role->jenis == 'opd' && $this->opd_selected_id != Auth::user()->opd_id) {
        abort(403);
    }
    // ...
}
```

---

## BUG-012: TahunDropdown reload pakai `window.location.reload()`

### Gejala
Saat user ganti tahun di dropdown, page di-reload paksa pakai JS `window.location.reload()` daripada Livewire navigate.

### Akar Penyebab
[TahunDropdown.php:42-48](file:///C:/laragon/www/kke-sakip/app/Livewire/Dashboard/TahunDropdown.php#L42-L48):
```php
$currentRoute = Url::currentRoute();
if (str_starts_with($currentRoute, 'lembar-kerja')) {
    return $this->redirectRoute('lembar-kerja');
}
$this->js('window.location.reload()');
```

### Dampak
- Hard reload kehilangan SPA experience.
- Form data yang belum disimpan akan hilang.
- Network round-trip lengkap (semua asset + page render).

### Perbaikan yang Direkomendasikan
Pakai `$this->dispatch('refresh')` atau `wire:navigate` pattern di Livewire 3, dengan event listener di komponen halaman untuk re-fetch data berdasarkan `tahun_session` baru.

---

## BUG-013: `LembarKerja` propagate dokumen ke OPD lain (auto-copy heuristic)

### Gejala
Saat OPD A upload dokumen ke bukti dukung X, sistem **otomatis copy** dokumen itu ke bukti dukung X di OPD B, C, ... yang belum punya dokumen.

### Akar Penyebab
[LembarKerja.php:1080-1110](file:///C:/laragon/www/kke-sakip/app/Livewire/Dashboard/LembarKerja.php#L1080-L1110):
```php
// Loop bukti_dukung serupa di OPD lain (sama nama, sama kriteria)
// Jika belum punya link_file → COPY
```

### Dampak
- **Surprise behavior**: OPD A upload, OPD B tiba-tiba punya dokumen yang sama.
- Kalau dokumen tidak relevan untuk OPD B, mereka harus delete manual.
- Dokumen yang sama terhitung dua kali untuk audit trail.
- Berpotensi menyalin dokumen yang sensitif/spesifik OPD A ke OPD B.

### Apa ini intentional atau bug?
Tidak jelas dari kode. Kemungkinan ini adalah **shortcut untuk dokumen "bersama Pemkab"** yang seharusnya di-handle via `EsakipSyncService::syncSharedDocument()` (yang fetch dari Pemkab `esakip_opd_id=1`). Logic auto-copy ini mungkin redundant dan rawan menyalakan duplikasi.

### Perbaikan yang Direkomendasikan
- Audit kapan logic ini dijalankan (heuristic match-by-name).
- Pastikan ada `bukti_dukung.is_shared` flag (kalau memang dokumen meant-to-be-shared, mark eksplisit).
- Atau hapus auto-copy dan andalkan pure user upload + EsakipSyncService shared document flow.

---

## BUG-014: `RoleSeeder` hard-coded ID 1-7 dipakai di `UserSeeder`

### Gejala
[UserSeeder.php:20-62](file:///C:/laragon/www/kke-sakip/database/seeders/UserSeeder.php#L20-L62) hardcode `role_id => 1` (admin), `role_id => 2` (verifikator), dst, mengandalkan auto-increment `RoleSeeder` insert urutan.

### Akar Penyebab
RoleSeeder menggunakan `DB::table('role')->insert(...)` tanpa explicit ID. Auto-increment selalu start dari 1 di SQLite kalau table baru. Tapi kalau ada migration drop+re-add atau seed di DB existing, ID bisa shift.

### Dampak
- Migrate fresh: aman (1-7 selalu cocok).
- Migrate without fresh: kalau ada `DELETE FROM role` + re-seed, auto-increment lanjut dari max+1, **bukan reset ke 1**. UserSeeder akan reference role_id yang tidak ada.
- Production reseed scenario rawan break.

### Perbaikan yang Direkomendasikan
**Option A**: ambil role ID dynamically:
```php
$adminRoleId = DB::table('role')->where('nama', 'admin')->value('id');
DB::table('users')->insert([..., 'role_id' => $adminRoleId, ...]);
```

**Option B**: explicit ID di RoleSeeder:
```php
DB::table('role')->insert([
    ['id' => 1, 'nama' => 'admin', 'jenis' => 'admin'],
    ['id' => 2, 'nama' => 'verifikator_bappeda', 'jenis' => 'verifikator'],
    // ...
]);
```

---

## BUG-015: `Penilaian` cast `link_file` array dipakai inkonsisten — array vs JSON string

### Gejala
[Penilaian.php:14](file:///C:/laragon/www/kke-sakip/app/Models/Penilaian.php#L14) cast `link_file => 'array'`. Tapi di beberapa tempat, code akses `$penilaian->link_file` sebagai array dengan check `is_array()`:

[LembarKerja.php:660-663](file:///C:/laragon/www/kke-sakip/app/Livewire/Dashboard/LembarKerja.php#L660-L663):
```php
$files = $penilaian->link_file;
if (!is_array($files)) {
    return [];
}
```

### Akar Penyebab
Defensive coding karena ada legacy data yang mungkin punya `link_file` raw JSON string (sebelum cast diterapkan), atau null. Cast Laravel return `null` jika kolom NULL, return array jika valid JSON.

### Dampak
Tidak break, tapi **mengindikasikan ketidakpercayaan terhadap cast**. Jika developer baru menulis path yang tidak `is_array()`-check, bisa fatal jika data legacy ada.

### Perbaikan yang Direkomendasikan
Audit data: kalau semua row sekarang valid array, hapus defensive checks. Atau standardize ke `$files = $penilaian->link_file ?? []` (PHP 8+).

---

## SUMMARY MATRIKS PRIORITAS

| Bug | Severity | Effort fix | Impact bila tidak diperbaiki |
|-----|----------|------------|-------------------------------|
| BUG-001 (penilaian_di sub_komponen) | HIGH | low | Monitoring page render salah mode |
| BUG-002 (bobot accessor silent 0) | HIGH | medium | Skor akhir OPD jadi 0 di banyak tempat |
| BUG-003 (nama vs jenis) | MEDIUM | low | Onboarding gotcha |
| BUG-004 (visibility ≠ authorization) | HIGH (security) | medium | Akses fitur admin oleh non-admin |
| BUG-005 (orphan seeder) | LOW | trivial | confusion saja |
| BUG-006 (env not in example) | LOW | trivial | onboarding friction |
| BUG-007 (truncate riwayat tanpa confirm) | MEDIUM | low | data loss |
| BUG-008 (empty migrations) | LOW | none (biarkan) | cosmetic |
| BUG-009 (welcome.blade) | LOW | trivial | trivial |
| BUG-010 (delete-and-insert konten) | MEDIUM | medium | race condition |
| BUG-011 (EksporLaporan opd cross-write) | HIGH (security) | low | OPD nulis konten OPD lain |
| BUG-012 (window.reload TahunDropdown) | LOW | medium | UX degradation |
| BUG-013 (auto-copy across OPD) | MEDIUM | medium | data integrity surprise |
| BUG-014 (hardcoded role IDs) | LOW | low | reseed risk |
| BUG-015 (link_file defensive checks) | LOW | low | code smell |

**Yang DIPRIORITAS perbaiki SEBELUM development feature baru**:
1. BUG-001 (penilaian_di) — fix dulu, ini break Monitoring
2. BUG-002 (bobot accessor) — fix kalau bisa, ini banyak menyentuh skor akhir
3. BUG-004 (route allow-list) — security
4. BUG-011 (EksporLaporan opd cross-write) — security

Yang lain bisa di-tackle sambil menggarap fitur lain.
