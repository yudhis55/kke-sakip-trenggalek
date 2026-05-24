# FLOWS — End-to-End Feature Flows

> Setiap fitur ditulis dari **trigger awal** sampai **state akhir database**. Untuk mencegah kesalahan alur saat development.

---

## 0. App Bootstrap & Session State

### Session keys yang dipakai global

| Key | Set by | Cleared by | Purpose |
|-----|--------|-----------|---------|
| `tahun_session` | [TahunDropdown::mount()](file:///C:/laragon/www/kke-sakip/app/Livewire/Dashboard/TahunDropdown.php#L17) → fallback ke `Tahun::is_active=true` atau tahun sekarang | manual `session()->forget` | Tahun aktif yang dilihat user. **Hampir semua query scoped by ini.** |
| `opd_session` | `Monitoring::selectOpd($id)`, `LembarKerja::selectOpd($id)` | `TahunDropdown::updatedTahunSession()` | OPD yang sedang dilihat (untuk role non-OPD) |
| `komponen_session` | `LembarKerja::selectKomponen()` | filter cascade reset di `TahunDropdown` & `LembarKerja::updatedKomponenSession()` | Komponen yang dipilih |
| `sub_komponen_session` | `LembarKerja::selectSubKomponen()` | cascade | Sub-komponen yang dipilih |
| `kriteria_komponen_session` | `LembarKerja::selectKriteriaKomponen()` | cascade | Kriteria yang dipilih |

**ATURAN**: ganti `tahun_session` → semua session lain DIHAPUS otomatis ([TahunDropdown.php:32-40](file:///C:/laragon/www/kke-sakip/app/Livewire/Dashboard/TahunDropdown.php#L32-L40)). Setelah itu page reload (`window.location.reload()`) atau redirect ke `/lembar-kerja` jika sedang di sana.

---

## 1. Login

**Trigger**: User buka `/` (atau langsung `/login`)

```
GET /
  ↓ (group: 'guest' middleware)
redirect → /login
  ↓
GET /login
  ↓ (Livewire mount Auth\Login.php)
[Layout: components.layouts.auth]
User isi email + password → submit
  ↓
Login::login()
  ├── validate(['email' => 'required|email', 'password' => 'required|string'])
  ├── Auth::attempt($credentials)
  │     ├── Berhasil → session()->regenerate() → flash success → redirectIntended('/dashboard')
  │     └── Gagal    → flash error 'Email atau password salah.'
  └── (tidak ada error UI, hanya flash toast)
```

**Catatan**:
- TIDAK ada Fortify/Breeze. TIDAK ada throttle/rate-limit pada login. TIDAK ada CSRF token check (Livewire handle internally).
- TIDAK ada `remember_token` UI (kolomnya ada di `users` table tapi tidak dipakai).
- Setelah login: middleware `EnsureUserHasRole` mengizinkan jika `role.nama` ada di route allow-list. Lihat [ROLES.md](./ROLES.md).

---

## 2. Logout

**Trigger**: Klik dropdown logout di topbar (komponen [Logout](file:///C:/laragon/www/kke-sakip/app/Livewire/Auth/Logout.php) di-render di layout)

```
Logout::logout()
  ├── Auth::logout()
  ├── session()->invalidate()
  ├── session()->regenerateToken()
  └── redirect '/login'
```

---

## 3. Dashboard

**Trigger**: GET `/dashboard` setelah login.

### Behavior per-role:

```
Auth::user()->role->jenis → branching
  ├── 'admin'           → tampilkan semua statistik akumulasi semua OPD
  ├── 'verifikator_*'   → statistik penilaian role sendiri
  ├── 'penjamin'        → statistik penilaian role sendiri
  ├── 'penilai'         → statistik penilaian role sendiri
  └── 'opd'             → statistik penilaian mandiri OPD sendiri saja (filter opd_id)
```

### Computed properties (lazy):
- `tahun()` — return [Tahun](file:///C:/laragon/www/kke-sakip/app/Models/Tahun.php) berdasarkan `tahun_session`
- `jumlahKomponen` / `jumlahSubKomponen` / `jumlahKriteriaKomponen` / `jumlahBuktiDukung` — counts filter `tahun_id`
- `jumlahKriteriaKomponenDinilai` — KRITIS, branching:
  - role `opd` → hitung kriteria yang OPD-nya sudah memberi `tingkatan_nilai_id` (filter `kriteria_komponen.penilaian_di = 'kriteria'`)
  - role `verifikator` → hitung yang sudah `is_verified IS NOT NULL`
  - role `admin` → hitung penilaian mandiri seluruh OPD
  - role `penjamin/penilai` → hitung yang sudah punya `tingkatan_nilai_id`
- `totalKriteriaKomponenDiKriteria` — total kriteria dengan `penilaian_di='kriteria'`, untuk OPD = total saja, untuk role lain = total × jumlah OPD aktif

**Catatan KRITIS**: Dashboard **TIDAK menghitung kriteria yang `penilaian_di='bukti'`**. Hanya level kriteria. Ini intentional (lihat filter `where('kriteria_komponen.penilaian_di', 'kriteria')`).

---

## 4. Mapping (Admin Only)

**Trigger**: GET `/mapping` (sidebar muncul untuk admin saja, tapi route tidak diblokir untuk role lain — gap permission, lihat ROLES.md).

### Hierarki yang dikelola:

```
Komponen (kode, nama, bobot, tahun_id)
  └── SubKomponen (kode, nama, bobot, komponen_id, tahun_id)
        └── KriteriaKomponen (kode, nama, sub_komponen_id, jenis_nilai_id, penilaian_di, tahun_id)
              └── BuktiDukung (nama, kriteria_komponen_id, role_id, is_auto_verified, esakip_document_type, esakip_document_code, is_n_minus_1, tahun_id)
```

### Action: addKomponen
```
Mapping::addKomponen()
  ├── validate kode unique:komponen,kode
  ├── HITUNG total bobot existing untuk tahun_id ini
  │     └── jika (existing + new bobot) > 100 → addError, RETURN
  ├── Komponen::create([kode, nama, bobot, tahun_id])
  └── reset form, unset $this->fullMapping (clear computed cache)
```

### Action: addSubKomponen
```
Mapping::addSubKomponen()
  ├── validate kode unique:sub_komponen,kode + komponen_id exists
  ├── ambil komponen induk
  ├── HITUNG total bobot existing sub_komponen untuk komponen_id ini
  │     └── jika exceed komponen.bobot → addError
  ├── SubKomponen::create([..., komponen_id, tahun_id (dari komponen induk)])
  └── reset form
```

### Action: addKriteriaKomponen
```
Mapping::addKriteriaKomponen()
  ├── validate kode unique + sub_komponen_id + jenis_nilai_id + penilaian_di in:kriteria,bukti
  ├── KriteriaKomponen::create([
  │     kode, nama,
  │     sub_komponen_id,
  │     komponen_id (dari sub_komponen),
  │     jenis_nilai_id,
  │     penilaian_di,         ← penting: 'kriteria' atau 'bukti'
  │     tahun_id
  │   ])
  └── reset form
```

**`penilaian_di` semantics**:
- `'kriteria'` → satu nilai per kriteria. Penilaian disimpan dengan `bukti_dukung_id = NULL`.
- `'bukti'` → nilai per bukti dukung. Penilaian disimpan per `bukti_dukung_id`.
- Default di [Mapping.php:58](file:///C:/laragon/www/kke-sakip/app/Livewire/Dashboard/Mapping.php#L58) = `'kriteria'` jika tidak ada.

### Action: addBuktiDukung
```
Mapping::addBuktiDukung()
  ├── validate nama, role_id_bukti exists:role,id, is_auto_verified, is_n_minus_1, esakip_document_type/code optional
  ├── BuktiDukung::create([
  │     nama,
  │     kriteria_komponen_id,
  │     sub_komponen_id (dari kriteria),
  │     komponen_id (dari kriteria),
  │     role_id (verifikator subtype yang akan menilai),
  │     is_auto_verified (true=otomatis verified setelah sync),
  │     is_n_minus_1 (true=ambil dokumen tahun n-1 dari eSAKIP),
  │     esakip_document_type ('rpjmd', 'renja', dll),
  │     esakip_document_code (kode spesifik kalau ada),
  │     tahun_id
  │   ])
  └── reset form
```

**Konsekuensi `role_id` pada bukti_dukung**: hanya verifikator subtype yang `role_id`-nya match yang akan melihat bukti dukung itu di Lembar Kerja (lihat [LembarKerja::komponenList()](file:///C:/laragon/www/kke-sakip/app/Livewire/Dashboard/LembarKerja.php#L378) `whereHas bukti_dukung where role_id = Auth::user()->role_id`).

---

## 5. Lembar Kerja (Worksheet) — fitur terpenting

**Trigger**: GET `/lembar-kerja`. Komponen: [LembarKerja](file:///C:/laragon/www/kke-sakip/app/Livewire/Dashboard/LembarKerja.php) (1587 lines).

### Phase 1: Cascade selection

```
mount()
  ├── tahun_id = session('tahun_session') || Tahun::where('is_active', true)->first()->id
  └── jika role.jenis == 'opd' → opd_session auto-set ke Auth::user()->opd_id
```

User pilih cascade:
```
selectOpd(id) → reset komponen/sub/kriteria session
selectKomponen(id) → reset sub_komponen, kriteria_komponen
selectSubKomponen(id) → reset kriteria_komponen
selectKriteriaKomponen(id) → final selection
```

`updated*()` hooks juga reset cascade saat user pilih lewat `wire:model.live` di select dropdown ([LembarKerja.php:103-143](file:///C:/laragon/www/kke-sakip/app/Livewire/Dashboard/LembarKerja.php#L103-L143)).

### Phase 2: Filter visibility per role

`opdList()`:
- role `opd` → only own OPD
- lainnya → paginated OPD list dengan search

`komponenList()`:
- role `verifikator` → filter `whereHas('bukti_dukung', fn($q) => $q->where('role_id', Auth::user()->role_id))` ([LembarKerja.php:462](file:///C:/laragon/www/kke-sakip/app/Livewire/Dashboard/LembarKerja.php#L462))
- lainnya → semua komponen di tahun ini
- Cascade serupa untuk `subKomponenList`, `kriteriaKomponenList`.

### Phase 3: Upload bukti dukung (role OPD)

```
uploadBuktiDukung()
  ├── cekAksesWaktu() → cek setting.buka_penilaian_mandiri / tutup_penilaian_mandiri
  │     ├── allowed=false → flash error, RETURN
  │     └── allowed=true  → lanjut
  ├── validate(file_bukti_dukung array, .* mimes:pdf, page_number nullable int)
  ├── jika !bukti_dukung_id → error 'Silakan pilih bukti dukung'
  ├── opdRoleId = Role::where('jenis','opd')->first()->id
  ├── foreach file:
  │     ├── path = $file->store('bukti_dukung', 'public')  ← random filename
  │     └── push uploadedFiles[] = [
  │              path, original_name, is_perubahan (per-file),
  │              kategori ('induk' | 'perubahan'),
  │              keterangan, periode=null, tanggal_publish=now,
  │              from_esakip=false, uploaded_at=now,
  │              page_number=$file_page_numbers[$index] ?? 1
  │           ]
  ├── cari existing Penilaian where(kriteria, bukti_dukung, opd, role_id=opd):
  │     ├── ada → MERGE atau REPLACE (jika $ganti_semua_dokumen)
  │     │           - REPLACE: hapus file lama dari Storage, link_file = uploaded
  │     │           - MERGE  : link_file = array_merge(existing, uploaded)
  │     │         is_perubahan=true (record sebagai revisi)
  │     └── tidak ada → Penilaian::create dengan link_file=uploaded, is_perubahan=false
  ├── PROPAGATE OPD-shared bukti_dukung (heuristic):
  │     foreach bukti_dukung serupa (sama nama, sama kriteria, sama OPD lain):
  │           jika belum punya dokumen → COPY link_file (auto-fill)  ← lihat ~line 1080-1110
  ├── UPDATE PenilaianHistory.status_perbaikan = 'sudah_diperbaiki'
  │     untuk semua penolakan 'belum_diperbaiki' atas bukti_dukung ini
  │     (set tanggal_perbaikan, file_perbaikan_id=null karena file di link_file array)
  ├── recordHistory() → tulis ke penilaian_history (audit trail)
  └── flash success, resetFormStates
```

### Phase 4: Simpan penilaian (semua role yang scoring)

```
simpanPenilaian()
  ├── cekAksesWaktu() → cek deadline role aktif
  ├── validate tingkatan_nilai_id required exists
  ├── jenis = Auth::user()->role->jenis
  ├── roleId = Auth::user()->role_id
  ├── buktiDukungId = penilaianDiKriteria ? null : $this->bukti_dukung_id
  │      (LIHAT KNOWN_BUGS.md — penilaianDiKriteria sekarang bener-bener baca dari kriteria_komponen.penilaian_di)
  ├── cari existing Penilaian where(kriteria, bukti, opd, role_id=user)
  │     ├── ada → update tingkatan_nilai_id + is_perubahan=true
  │     └── tidak ada → create
  ├── recordHistory() → audit
  └── flash success
```

### Phase 5: Verifikasi (role verifikator/penjamin)

Bertujuan: approve/reject penilaian OPD.
```
simpanVerifikasi()
  ├── cekAksesWaktu()
  ├── validate is_verified in:0,1
  ├── update Penilaian where(kriteria, bukti, opd, role_id=user) set:
  │     - is_verified (true=approved, false=rejected)
  │     - keterangan_verifikasi
  ├── jika is_verified=false (REJECTED):
  │     - bagi OPD: muncul di /rekap-penolakan (status_perbaikan='belum_diperbaiki')
  │     - bagi role yang reject: nanti muncul di /rekap-perbaikan setelah OPD upload ulang
  └── recordHistory()
```

### Phase 6: Hapus nilai / hapus file

`hapusNilai()` — set `tingkatan_nilai_id=null`, `is_final=false`, keterangan tetap. recordHistory untuk audit.

`deleteFileBuktiDukung()` — hapus file dari Storage (skip yang `from_esakip=true`), set `link_file=null`, `is_perubahan=false`, recordHistory.

### Phase 7: Navigation antar bukti dukung

`previousBuktiDukung()` / `nextBuktiDukung()` — iterasi `BuktiDukung::where(kriteria_komponen_id)->orderBy('id')->pluck('id')` lalu shift index.

---

## 6. Monitoring (read-mostly + scoring)

**Trigger**: GET `/monitoring`. Berbeda dari LembarKerja — Monitoring **menampilkan progress matrix** bukan worksheet detail.

### View hierarchy:
```
/monitoring                               ← Monitoring.php — list OPD + komponen progress
/monitoring/sub-komponen/{sub_id}/kriteria-komponen
                                          ← KriteriaKomponen.php — list kriteria + progress per role
/monitoring/sub-komponen/{sub_id}/kriteria-komponen/{kriteria_id}/bukti-dukung
                                          ← BuktiDukung.php — actual scoring/upload UI
```

### Monitoring::opdList() (komponen utama)
- role `opd` → return empty collection (OPD tidak browse di /monitoring)
- role lain → paginated OPD list dengan computed progress

### Monitoring computes "items selesai dinilai" per OPD:

```
foreach kriteria in tahun:
  jika kriteria.penilaian_di = 'kriteria':
    totalItems += 1
    selesai jika ada Penilaian (kriteria, opd, role=user, tingkatan_nilai_id NOT NULL)
  else (= 'bukti'):
    totalItems += count(bukti_dukung untuk kriteria)
    selesai = count(Penilaian where bukti_dukung dan tingkatan_nilai_id NOT NULL)

display: totalItemsPerOpd[opdId] / itemsSelesaiPerOpd[opdId]
```

### Monitoring/.../BuktiDukung.php — actual scoring page

Sama seperti LembarKerja phase 3-5, dengan tambahan:
- `getTrackingData()` — return 4-tahap audit per (kriteria, bukti, opd):
  ```
  [
    'opd'          => Penilaian|null with role+tingkatan+history,
    'verifikator'  => Penilaian|null,
    'penjamin'     => Penilaian|null,
    'penilai'      => Penilaian|null,
  ]
  ```
- Modal "Tracking" menampilkan timeline lengkap dari `penilaian_history` per role.

`canDoPenilaian()` di [BuktiDukung.php:167](file:///C:/laragon/www/kke-sakip/app/Livewire/Dashboard/Monitoring/KriteriaKomponen/BuktiDukung.php#L167):
- Mode `kriteria`: SEMUA bukti_dukung harus sudah punya file dari OPD baru bisa scoring
- Mode `bukti`: bukti yang dipilih saja harus punya file

---

## 7. Rekap Penolakan (OPD Only)

**Trigger**: GET `/rekap-penolakan`. Komponen: [RekapPenolakan](file:///C:/laragon/www/kke-sakip/app/Livewire/Dashboard/RekapPenolakan.php).

### Computed: `rekapPenolakan`
```
hanya untuk role 'opd' (else return collect())
opdId = Auth::user()->opd_id

verifikatorRoleIds = [2,3,4]
penjaminRoleId = 5
roleIds = [2,3,4,5]

return PenilaianHistory
  ::whereIn('role_id', $roleIds)
  ->where('opd_id', $opdId)
  ->where('is_verified', 0)               ← rejected
  ->whereNotNull('keterangan')            ← ada alasan tolak
  ->whereIn('status_perbaikan', ['belum_diperbaiki', 'sudah_diperbaiki'])
  ->whereHas('kriteria_komponen', fn($q) => $q->where('tahun_id', $tahun_session))
  ->with(['kriteria_komponen.sub_komponen.komponen','bukti_dukung','role'])
  ->orderBy('created_at', 'desc')
  ->get()
```

### Computed: `badgeCount`
Hitung penolakan `belum_diperbaiki` saja → muncul di sidebar sebagai badge merah.

### Action: `showKeterangan($id)`
Open modal, isi `selectedKeterangan` dari `PenilaianHistory::keterangan`.

---

## 8. Rekap Perbaikan (Verifikator/Penjamin/Penilai)

**Trigger**: GET `/rekap-perbaikan`. Komponen: [RekapPerbaikan](file:///C:/laragon/www/kke-sakip/app/Livewire/Dashboard/RekapPerbaikan.php).

### Computed: `rekapPerbaikan`
```
allowedRoles = ['verifikator','penjamin','penilai']
jika user.role.jenis tidak di allowed → return empty

return PenilaianHistory
  ::where('role_id', Auth::user()->role_id)   ← penolakan yang DIBUAT user ini
  ->where('is_verified', 0)
  ->whereNotNull('keterangan')
  ->where('status_perbaikan', 'sudah_diperbaiki')   ← OPD sudah upload ulang
  ->whereHas('kriteria_komponen', fn($q) => $q->where('tahun_id', $tahun_session))
  ->with([...])
  ->orderBy('tanggal_perbaikan', 'desc')
  ->get()
```

**Flow penolakan→perbaikan→penerimaan**:
```
1. Verifikator REJECT → PenilaianHistory(is_verified=0, keterangan='Alasan', status_perbaikan='belum_diperbaiki')
2. OPD upload ulang  → PenilaianHistory.status_perbaikan UPDATED → 'sudah_diperbaiki', tanggal_perbaikan=now()
                       (logic di LembarKerja.php:1115-1135)
3. Verifikator buka /rekap-perbaikan → lihat list
4. Verifikator review → kalau approve, buat Penilaian baru is_verified=1 (atau update existing)
                        kalau reject lagi → ulang dari step 1
5. Eventual final → status_perbaikan='diterima_setelah_perbaikan' (enum value, tapi blm ada UI yang set ini eksplisit; cek code path bila perlu)
```

---

## 9. Ekspor Laporan Word (PhpWord)

**Trigger**: GET `/ekspor-laporan`. Komponen: [EksporLaporan](file:///C:/laragon/www/kke-sakip/app/Livewire/Dashboard/EksporLaporan.php) (647 lines). Library: `phpoffice/phpword`.

### Flow:
```
1. mount()
   ├── tahun_id = session('tahun_session')
   ├── tanggal_ekspor = "Trenggalek, 24 Mei 2026" (formatTanggalIndonesia)
   └── initializeCatatanRekomendasi() → load existing dari konten_laporan table

2. User isi:
   - opd_selected_id
   - tanggal_ekspor (override default)
   - deskripsi[komponen_id][sub_komponen_id] = text
   - catatan[komponen_id] = [text1, text2, ...]
   - rekomendasi[komponen_id] = [text1, text2, ...]

3. Klik tombol "Simpan" → simpan ke konten_laporan table:
   - saveDeskripsiToDatabase  → updateOrCreate per (type='deskripsi', komponen, sub, opd, tahun)
   - saveCatatanToDatabase    → DELETE existing → INSERT ulang dengan urutan 1..N (per komponen)
   - saveRekomendasiToDatabase → idem catatan

4. Klik tombol "Ekspor" → generate Word file dengan TemplateProcessor (template tersimpan di public/storage atau template_laporan)

5. Optional: simpan Template
   - User isi $namaTemplate → save ke template_laporan (konten=JSON {deskripsi, catatan, rekomendasi})
   - User pilih existing $selectedTemplateId → loadTemplate() prefill semua field
```

**Template format** ([TemplateLaporan.php:25](file:///C:/laragon/www/kke-sakip/app/Models/TemplateLaporan.php#L25)):
```json
{
  "deskripsi":   { "1": { "1": "...", "2": "..." } },
  "catatan":     { "1": ["c1", "c2"], "2": [...] },
  "rekomendasi": { "1": ["r1"], ... }
}
```

**Catatan**: panduan template Word ada di [PANDUAN_TEMPLATE_WORD.md](file:///C:/laragon/www/kke-sakip/PANDUAN_TEMPLATE_WORD.md), [CARA_BUAT_TABEL_WORD.md](file:///C:/laragon/www/kke-sakip/CARA_BUAT_TABEL_WORD.md), [PANDUAN_TABEL_PERBANDINGAN.md](file:///C:/laragon/www/kke-sakip/PANDUAN_TABEL_PERBANDINGAN.md), [PANDUAN_PERBAIKI_TABEL_BAB2.md](file:///C:/laragon/www/kke-sakip/PANDUAN_PERBAIKI_TABEL_BAB2.md), [CARA_MEMPERBAIKI_TEMPLATE.md](file:///C:/laragon/www/kke-sakip/CARA_MEMPERBAIKI_TEMPLATE.md). Pelajari sebelum mengubah render Word.

---

## 10. Sinkronisasi eSAKIP

**Trigger**: GET `/sinkron-data`. Komponen: [SinkronData](file:///C:/laragon/www/kke-sakip/app/Livewire/Dashboard/SinkronData.php). Service: [EsakipSyncService](file:///C:/laragon/www/kke-sakip/app/Services/EsakipSyncService.php) (1336 lines).

### Pra-syarat:
1. Master OPD punya `esakip_opd_id` (mapping ke API eSAKIP) — di-seed via [OpdSeeder](file:///C:/laragon/www/kke-sakip/database/seeders/OpdSeeder.php) atau diset di Pengaturan.
2. Master BuktiDukung punya `esakip_document_type` (`'rpjmd'`, `'renja'`, dll dari [config/esakip.php](file:///C:/laragon/www/kke-sakip/config/esakip.php)).

### Flow:

```
[Step A: PREVIEW]
User pilih: tahun (required), opd (optional), document_type (optional)
  ↓
SinkronData::previewSync()
  ├── validate selected_tahun required exists
  └── EsakipSyncService::previewSync($tahunId, $opdId?, $documentType?)
        ├── ambil OPD list (filter whereNotNull('esakip_opd_id'))
        ├── jika kosong → throw "Tidak ada OPD dengan mapping esakip_opd_id"
        ├── ambil document_types target (semua atau yang dipilih)
        ├── foreach (opd × document_type):
        │     ├── ambil bukti_dukung yang di-mapping (where esakip_document_type=type AND tahun_id)
        │     ├── fetch dari API: fetchDocumentsFromEsakip(type, tahun, opd_id)
        │     ├── fetch dokumen 'lainnya' yang keterangannya match → merge
        │     ├── fetch shared documents dari Pemkab (esakip_opd_id=1) → merge
        │     ├── jika is_n_minus_1=true di bukti_dukung → fetch tahun-1
        │     └── return preview struct: { tahun, opd_count, document_count, bukti_dukung_count, auto_verified_count, documents: [...], errors: [...] }
        └── jika document_count=0 → flash warning, tetap tampil preview kosong

[Step B: PROCESS]
User klik "Proses Sinkronisasi"
  ↓
SinkronData::processSync()
  ├── validate selected_tahun required exists
  ├── set syncing=true, progress=0
  ├── EsakipSyncService::processSync($tahunId, $opdId?, $documentType?, $progressCallback)
  │     ├── DB::beginTransaction
  │     ├── foreach (opd × document_type):
  │     │     ├── fetch documents (sama seperti preview)
  │     │     ├── foreach buktiDukung untuk type ini:
  │     │     │     └── syncPenilaian($buktiDukung, $opd, $documents)
  │     │     │           └── 3 cases:
  │     │     │                 1. Penilaian belum ada → CREATE
  │     │     │                       - link_file = build dari documents
  │     │     │                       - source = 'esakip'
  │     │     │                       - is_verified = bukti_dukung.is_auto_verified ? true : null
  │     │     │                       - esakip_synced_at = now()
  │     │     │                 2. Penilaian existing dengan source='upload' → SKIP
  │     │     │                       - jangan timpa data manual user
  │     │     │                 3. Penilaian existing dengan source='esakip' → SMART MERGE
  │     │     │                       - smartMergeDocuments(existing.link_file, newDocs)
  │     │     │                       - dedup by URL primary, timestamp secondary
  │     │     │                       - tambah dokumen baru saja
  │     │     │     └── recordHistory di penilaian_history
  │     │     └── tulis riwayat_sinkron entry per (opd, type)
  │     ├── kalau exception → rollBack, lempar
  │     └── DB::commit
  ├── set syncing=false
  └── flash sukses/warning/error per result
```

### Smart sync detail (dokumentasi mendalam ada di [SMART_SYNC_STRATEGY.md](file:///C:/laragon/www/kke-sakip/SMART_SYNC_STRATEGY.md)):

**`source` column di Penilaian**:
- `'upload'` (default) → user upload manual. **Sync tidak akan menimpa.**
- `'esakip'` → hasil sync. Re-sync akan smart-merge.

**`link_file` JSON struct** (lihat [STRUKTUR_LINK_FILE.md](file:///C:/laragon/www/kke-sakip/STRUKTUR_LINK_FILE.md)):
```json
[
  {
    "url": "https://e-sakip.../storage/file_1761182369.pdf",
    "path": "bukti_dukung/xxx.pdf",   // hanya untuk upload manual
    "original_name": "Renja 2024.pdf",
    "is_perubahan": false,
    "kategori": "induk" | "perubahan",
    "keterangan": "...",
    "periode": "...",                  // dari API kalau ada
    "tanggal_publish": "2026-01-13",
    "from_esakip": true | false,
    "uploaded_at": "2026-01-13 12:30:00",
    "synced_at": "2026-01-13 12:30:00",  // hanya from_esakip=true
    "page_number": 1
  }
]
```

**Dedup logic** ([EsakipSyncService.php:1280-1296](file:///C:/laragon/www/kke-sakip/app/Services/EsakipSyncService.php#L1280-L1296)):
1. Compare URL → match = duplicate
2. Compare extracted timestamp from filename → match = duplicate
3. Else → ADD

### `is_n_minus_1` semantics:
Bukti dukung yang ditandai `is_n_minus_1=true` (e.g. RPJMD-tahun-lalu) akan fetch dengan `tahun = currentTahun - 1` dari API. Lihat [EsakipSyncService.php:536](file:///C:/laragon/www/kke-sakip/app/Services/EsakipSyncService.php#L536).

### Riwayat sinkron:
Setiap (opd, document_type) menghasilkan satu row di `riwayat_sinkron`:
```
opd_id, tahun_id, document_type, document_name, file_url, penilaian_ids (json),
affected_count, auto_verified_count, status enum('success','failed','partial','no_document'), synced_at
```
Tampil di /sinkron-data sebagai pagination.

`SinkronData::clearRiwayat()` → `RiwayatSinkron::truncate()` (admin tool).

---

## 11. Pengaturan (Admin Only)

**Trigger**: GET `/pengaturan`. Komponen: [Pengaturan](file:///C:/laragon/www/kke-sakip/app/Livewire/Dashboard/Pengaturan.php) (597 lines). Sidebar muncul untuk admin saja.

### Sub-feature: Setting Deadline (per tahun)
```
loadSetting() — cari setting where tahun_id=$this->tahun_id, prefill 8 datetime field
saveSetting() — Setting::updateOrCreate(['tahun_id'=>...], [8 datetime fields, maks_bobot_komponen])
              validate: tutup_* >= buka_* per tier
```

### Sub-feature: CRUD Tahun
```
saveTahun()    — validate unique:tahun, create
deleteTahun()  — Tahun::destroy (dengan handling foreign key)
```

### Sub-feature: CRUD User
```
editUser($id)    — prefill form fields
saveUser()       — validate(name, email unique, role exists, opd nullable, password min:6 if create)
                   create dengan Hash::make(password)
                   atau update dengan optional password change
deleteUser($id)
```

### Sub-feature: CRUD Role (admin can rename roles, but seeded names are load-bearing for middleware)
**WARNING**: jangan ganti `role.nama` tanpa update [routes/web.php:39](file:///C:/laragon/www/kke-sakip/routes/web.php#L39) allow-list.

### Sub-feature: CRUD Jenis Nilai & Tingkatan Nilai
- `jenis_nilai`: scale type (e.g. "A/B/C/D" or "Y/T")
- `tingkatan_nilai`: per scale, kode_nilai + bobot multiplier
- Used by `Penilaian.tingkatan_nilai_id` to compute final score

### Sub-feature: CRUD OPD
```
saveOpd() — fields: nama, esakip_opd_id, tahun_mulai_berlaku, predecessor_opd_id (untuk reorganisasi)
```

---

## 12. Penilaian History (Audit Trail)

Bukan halaman sendiri — **side effect dari setiap action di LembarKerja & Monitoring**.

### Setiap perubahan ke `penilaian` → tulis ke `penilaian_history`:

```php
// Penilaian.php:59
$penilaian->recordHistory(
    userId: Auth::id(),
    roleId: $roleId,
    opdId: $opdId,
    kriteriaKomponenId: $kriteriaKomponenId,
    buktiDukungId: $buktiDukungId,        // null kalau penilaian_di='kriteria'
    tingkatanNilaiId: $tingkatanNilaiId,
    isVerified: $isVerified,
    keterangan: $keterangan,
    isPerubahan: $isPerubahan,
);
```

### `getActionDescription()` ([PenilaianHistory.php:63](file:///C:/laragon/www/kke-sakip/app/Models/PenilaianHistory.php#L63)):
- role 'opd' + is_perubahan=true → "melakukan revisi/perbaikan"
- role 'opd' + is_perubahan=false → "melakukan penilaian mandiri"
- role 'verifikator' + is_verified=true → "menyetujui"
- role 'verifikator' + is_verified=false → "menolak"
- role 'penjamin' → "memberikan penilaian penjaminan kualitas"
- role 'penilai' → "memberikan penilaian evaluasi"

---

## 13. Dashboard Tracking Modal

Modal di /lembar-kerja & /monitoring/.../bukti-dukung yang menampilkan timeline 4-tahap untuk satu (kriteria, bukti, opd):

```
1. OPD melakukan: <action>     [tanggal] [tingkatan_nilai_kode_nilai] [keterangan]
2. Verifikator: <action>       [tanggal] [is_verified] [keterangan]
3. Penjamin: <action>           [tanggal] [tingkatan_nilai_kode_nilai] [keterangan]
4. Penilai: <action>            [tanggal] [tingkatan_nilai_kode_nilai] [keterangan]
```

Data dari `getTrackingData()` di [BuktiDukung.php:355](file:///C:/laragon/www/kke-sakip/app/Livewire/Dashboard/Monitoring/KriteriaKomponen/BuktiDukung.php#L355).

---

## 14. Counting nilai akhir OPD (formula)

```
Opd::getNilai($roleId, $tahunId)
  = SUM(Komponen::getNilai per komponen di tahun)

Komponen::getNilai($opdId, $roleId)
  = SUM(SubKomponen::getNilai)

SubKomponen::getNilai($opdId, $roleId)
  = SUM(KriteriaKomponen::getNilai)

KriteriaKomponen::getNilai($opdId, $roleId)
  ├── jika penilaian_di='kriteria':
  │     return Penilaian.tingkatan_nilai.bobot * KriteriaKomponen.bobot
  │     (bobot kriteria = SubKomponen.bobot / count(kriteria di sub))
  └── jika penilaian_di='bukti':
        return SUM(BuktiDukung::getNilai per bukti dukung)

BuktiDukung::getNilai($opdId, $roleId)
  = Penilaian.tingkatan_nilai.bobot * BuktiDukung.bobot (computed accessor)
  = Penilaian.tingkatan_nilai.bobot * (KriteriaKomponen.bobot / count(bukti_dukung))
```

**Caveat**: `BuktiDukung::bobot` butuh `kriteria_komponen` eager-loaded dengan `bukti_dukung_count` aggregat. Tanpa itu = silently 0 (lihat KNOWN_BUGS.md).

**Per role**: `Opd::getNilaiPerRole()` mengiterasi role 'opd', 'verifikator', 'penjamin', 'penilai' dan return array of nilai.

**Cache**: `KriteriaKomponen` punya static `$penilaianCacheStatic`, `$buktiDukungCache` untuk mengurangi N+1. Dipakai oleh batch loading di Monitoring/Dashboard. Cache di-warm via `KriteriaKomponen::preloadPenilaian()`.
