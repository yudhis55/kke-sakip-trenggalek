
## BUG-C Fix — FilePond State Persistence (2026-05-25)

**Pattern**: When Livewire components have transient form state (uploads, drafts) that should NOT carry across navigation:
1. Reset all related public properties in a dedicated method.
2. Add `wire:key` to dynamic child components (e.g. FilePond) tied to the parent record ID — forces Livewire to destroy & recreate the DOM/Alpine state on ID change.

**LembarKerja navigation reset scope** (BUG-C):
- `resetPenilaianForm()` only handled scoring fields.
- Upload state (`file_bukti_dukung`, `file_count`, `temporary_file_names`, `file_page_numbers`, `keterangan_upload`, `page_number`, `is_perubahan`, `ganti_semua_dokumen`, `is_final`, `is_setting_upload_page`) leaked across prev/next.
- Solution: dedicated `resetAllFormStatesForNavigation()` for navigation; keep `resetPenilaianForm()` for other call sites.

**FilePond + Livewire**: Without `wire:key`, the Alpine `isUploading`/`hasFiles` state and FilePond's internal file list survive a property reset. Bind `wire:key="filepond-bukti-{$bukti_dukung_id ?? 'none'}"` so Livewire re-mounts on context change.
