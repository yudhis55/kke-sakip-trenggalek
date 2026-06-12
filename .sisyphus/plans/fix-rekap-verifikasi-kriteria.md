# Plan E — Fix RekapVerifikasi Mode Kriteria + History Verification

## TL;DR

> **Quick Summary**: Fix RekapVerifikasi component agar handle mode kriteria (grouped per kriteria), dan verifikasi history recording di mode kriteria.
>
> **Deliverables**:
> - `RekapVerifikasi.php` rewritten: handle both mode bukti (per bukti) dan mode kriteria (per kriteria grouped)
> - `rekap-verifikasi.blade.php` updated: tampilkan grouped view untuk mode kriteria
> - Verifikasi history recording di mode kriteria sudah benar

---

## Context

### Problem 1: RekapVerifikasi tidak handle mode kriteria

Current `RekapVerifikasi::rekapVerifikasi()` hanya query:
```php
->whereIn('bukti_dukung_id', $buktiDukungIds)  // hanya match mode bukti
```

Dan status verifikasi dicek per bukti_dukung_id:
```php
->where('bukti_dukung_id', $p->bukti_dukung_id)  // tidak handle NULL
```

Di mode kriteria:
- Upload dokumen: per bukti_dukung_id (non-null) ✅
- Verifikasi: per kriteria (`bukti_dukung_id = NULL`) ← TIDAK ter-detect oleh query saat ini

### Problem 2: User decision — tampilkan per kriteria (grouped)

User pilih: untuk mode kriteria, tampilkan per kriteria komponen dengan sub-list bukti dukung di bawahnya. Status verifikasi 1 per kriteria.

### Solution Design

RekapVerifikasi harus return 2 jenis data:
1. **Mode bukti items**: per bukti_dukung (existing behavior, tetap)
2. **Mode kriteria items**: per kriteria_komponen, dengan list bukti_dukung yang sudah diupload sebagai sub-items

Approach: query KriteriaKomponen yang punya `penilaian_di='kriteria'` DAN punya bukti_dukung yang assigned ke verifikator ini DAN OPD sudah upload file. Status verifikasi = cek Penilaian verifikator di level kriteria (`bukti_dukung_id=NULL`).

---

## TODOs

- [ ] 1. Rewrite RekapVerifikasi.php computed method

  **What to do**:
  - Rewrite `rekapVerifikasi()` to return mixed collection:
    - Items dari mode bukti (existing logic, tetap)
    - Items dari mode kriteria (NEW: per kriteria grouped)
  - Each item has: `type` ('bukti' or 'kriteria'), `verifikasi_status`, `opd`, `komponen`, `sub_komponen`, `kriteria`, `bukti_dukung_list` (for kriteria type)
  
  **Logic untuk mode kriteria items**:
  ```php
  // Get kriteria komponen yang punya penilaian_di='kriteria'
  // DAN punya bukti_dukung assigned ke verifikator ini
  // DAN OPD sudah upload file di salah satu bukti_dukung-nya
  $kriteriaKomponenIds = BuktiDukung::where('role_id', $verifikatorRoleId)
      ->when($this->tahun_session, fn($q) => $q->where('tahun_id', $this->tahun_session))
      ->whereHas('kriteria_komponen', fn($q) => $q->where('penilaian_di', 'kriteria'))
      ->pluck('kriteria_komponen_id')
      ->unique();
  
  // For each kriteria, check if OPD has uploaded files
  foreach ($kriteriaKomponenIds as $kriteriaId) {
      // Get all OPDs that have uploaded files for this kriteria's bukti_dukung
      $opdPenilaians = Penilaian::where('kriteria_komponen_id', $kriteriaId)
          ->where('role_id', $opdRoleId)
          ->whereNotNull('link_file')
          ->when($this->selected_opd, fn($q) => $q->where('opd_id', $this->selected_opd))
          ->with(['opd', 'bukti_dukung'])
          ->get()
          ->groupBy('opd_id');
      
      foreach ($opdPenilaians as $opdId => $penilaians) {
          // Check verifikasi status at kriteria level
          $verifPenilaian = Penilaian::where('kriteria_komponen_id', $kriteriaId)
              ->where('opd_id', $opdId)
              ->where('role_id', $verifikatorRoleId)
              ->whereNull('bukti_dukung_id')  // kriteria level
              ->whereNotNull('is_verified')
              ->first();
          
          $item = new \stdClass();
          $item->type = 'kriteria';
          $item->kriteria_komponen_id = $kriteriaId;
          $item->kriteria_komponen = KriteriaKomponen::with('sub_komponen.komponen')->find($kriteriaId);
          $item->opd = Opd::find($opdId);
          $item->opd_id = $opdId;
          $item->bukti_dukung_list = $penilaians; // list of uploaded bukti
          $item->verifikasi_status = $verifPenilaian
              ? ($verifPenilaian->is_verified ? 'disetujui' : 'ditolak')
              : 'belum_diverifikasi';
          $item->verifikasi_keterangan = $verifPenilaian?->keterangan;
          $item->verifikasi_tanggal = $verifPenilaian?->updated_at;
          
          $result->push($item);
      }
  }
  ```

- [ ] 2. Update rekap-verifikasi.blade.php view

  **What to do**:
  - Handle 2 types of items in the table:
    - `type == 'bukti'`: render per bukti dukung (existing)
    - `type == 'kriteria'`: render per kriteria with expandable sub-list of bukti dukung
  - For kriteria type: show kriteria name, OPD, count of uploaded bukti, status badge
  - Optionally: expandable row showing list of bukti dukung yang sudah diupload

- [ ] 3. Verify history recording in mode kriteria

  **What to do**:
  - Trace full flow: OPD upload di mode kriteria → recordHistory called?
  - Verify: `LembarKerja::uploadBuktiDukung()` line 1084 records with `buktiDukungId: $this->bukti_dukung_id` (non-null) — this is CORRECT for upload
  - The "perbaikan" history should show in tracking modal
  - If issue persists, check if Monitoring component also needs fix

---

## Commit Strategy

Single commit: `fix(rekap-verifikasi): handle mode kriteria (grouped per kriteria) + badge visibility`
