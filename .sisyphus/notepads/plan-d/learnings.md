[2026-05-25 11:00] BUG-E (per-file delete) implementation
- Confirmed pattern: keterangan 'Menghapus file dokumen' is detected verbatim by getActionDescription, so reusing the same string keeps the audit trail consistent between bulk and per-file delete.
- Storage cleanup must skip entries where from_esakip is truthy (use empty(file['from_esakip'])) - same guard already used in deleteFileBuktiDukung.
- After unset on a numeric array, MUST call array_values() before saving back to JSON column to avoid sparse keys (becomes object on next load).
- When count(files)===0 set link_file=null so existing UI guards (!penilaian->link_file) treat the bukti as empty rather than rendering a 0-length array.
- Blade view already had a separate deleteFileByIndexForBukti(buktiId, idx) (mode bukti list view, lines ~2121/2144). Independent helper for mode bukti-detail tabs uses just deleteFileByIndex(idx) because bukti_dukung_id is already in component state.
- Single-file display path passes 0 as the index (link_file[0]).
