
## 2026-05-25 — PenilaianHistory::getActionDescription() OPD branch

**Pattern: is_perubahan != revision**
- `is_perubahan` flag on penilaian_history records "row sudah ada sebelum aksi", BUKAN "ini revisi".
- Flow OPD: upload → row created (is_perubahan=false). Skoring berikutnya → row exists (is_perubahan=true) — bukan revisi, ini SCORING PERTAMA.
- Untuk membedakan jenis aksi pakai `tingkatan_nilai_id` (null = upload, set = scoring).

**Pattern: EXACT match (===) untuk keterangan konstanta**
- Keterangan delete di-set di LembarKerja.php sebagai literal: `'Menghapus penilaian'` (line 1260), `'Menghapus file dokumen'` (line 1439).
- Pakai `===` (bukan `str_contains` / `stripos`) supaya user-supplied keterangan tidak memicu false-positive.

**LembarKerja.php keterangan strings (untuk reference future fixes)**
- L1059  Upload : `->keterangan_upload ?: 'Upload N file bukti dukung'`
- L1199  Scoring: ` ? 'Update penilaian' : 'Penilaian awal'`
- L1260  Delete penilaian : `'Menghapus penilaian'` — tingkatanNilaiId=null, isPerubahan=true
- L1439  Delete file      : `'Menghapus file dokumen'` — tingkatanNilaiId may be set

**Convention reminder**
- Project AGENTS.md says snake_case relation names di model PenilaianHistory: `kriteria_komponen()`, `bukti_dukung()`, `tingkatan_nilai()`, `file_perbaikan()` — match existing style.
- intelephense LSP not installed in env; pakai `php -l` sebagai syntax fallback.

## Plan B Task 2 — recordHistory in BuktiDukung.php (2026-05-25)

**Pattern**: Mirror LembarKerja.php recordHistory pattern for sister component BuktiDukung.php (Monitoring/KriteriaKomponen).

**Key insight — updateOrCreate returns the model**:
For `simpanPenilaian` we needed to capture the return of `Penilaian::updateOrCreate(...)` into `` so we could call `->recordHistory(...)` on it. The original code discarded the return value. Same applies to LembarKerja.php (already done there).

**isPerubahan detection pattern**:
For update-or-create flows, detect changes by querying for an existing row first:
`
 = Penilaian::where(...)->first();
 =  !== null;
\ = Penilaian::updateOrCreate(...);
\->recordHistory(..., isPerubahan: \);
`
For Upload/Verifikasi flows that already had explicit if(\) branches, reuse \ (or fall back to a fresh `->first()` query for the just-created row).

**Keterangan contract strings (must remain verbatim)**:
- `'Menghapus file dokumen'`  (deleteFileBuktiDukung)
- `'Menghapus penilaian'`     (hapusNilai — N/A in this file)
- `'Update penilaian'` / `'Penilaian awal'` (simpanPenilaian)

These match getActionDescription() in PenilaianHistory rendering. Changing them silently breaks history-tab UI.

**hapusNilai**:
This method exists in LembarKerja.php but NOT in BuktiDukung.php. Spec said "if exists" so it was intentionally skipped.

**LSP note**:
intelephense is not installed in this dev env; `php -l` is the available syntax check. Both passed.

**Result**: BuktiDukung.php went from 0 → 4 recordHistory calls.
