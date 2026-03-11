<?php

namespace App\Livewire\Dashboard;

use App\Models\Komponen;
use App\Models\Tahun;
use Livewire\Component;
use Livewire\Attributes\Computed;
use App\Models\SubKomponen;
use App\Models\JenisNilai;
use App\Models\KriteriaKomponen;
use App\Models\BuktiDukung;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

class Mapping extends Component
{
    public $kd_komponen, $nama_komponen, $bobot_komponen;
    public $kd_sub_komponen, $nama_sub_komponen, $bobot_sub_komponen, $komponen_id;
    public $kd_kriteria, $nama_kriteria, $sub_komponen_id, $jenis_nilai_id, $penilaian_di_kriteria;
    public $kd_bukti, $nama_bukti, $bobot_bukti, $kriteria_komponen_id, $kriteria_penilaian, $role_id_bukti, $is_auto_verified, $esakip_document_type, $esakip_document_code;
    public $original_is_auto_verified = null; // Track nilai awal untuk deteksi perubahan
    public $tahun_id;

    // Edit mode properties
    public $editKomponenId, $editSubKomponenId, $editKriteriaKomponenId, $editBuktiDukungId;
    public $isEditMode = false;

    public function mount()
    {
        $this->tahun_id = session('tahun_session') ?? Tahun::where('is_active', true)->first()->id;
    }


    #[Computed]
    public function tahunoptions()
    {
        return Tahun::all();
    }

    #[Computed]
    public function jenisnilaioptions()
    {
        return JenisNilai::all();
    }

    #[Computed]
    public function esakipDocumentTypes()
    {
        return config('esakip.document_types');
    }

    #[Computed]
    public function selectedKriteriaPenilaianDi()
    {
        if ($this->kriteria_komponen_id) {
            $kriteria = KriteriaKomponen::find($this->kriteria_komponen_id);
            return $kriteria?->penilaian_di ?? 'kriteria';
        }
        return 'kriteria';
    }

    #[Computed]
    public function roleoptions()
    {
        return Role::where('jenis', 'verifikator')->get();
    }

    #[Computed]
    public function fullMapping()
    {
        $komponens = Komponen::with([
            'sub_komponen' => function ($q) {
                $q->withCount('kriteria_komponen')
                    ->with([
                        'kriteria_komponen' => function ($q) {
                            $q->withCount('bukti_dukung')
                                ->with([
                                    'jenis_nilai',
                                    'sub_komponen' => function ($sq) {
                                        $sq->withCount('kriteria_komponen');
                                    },
                                    'bukti_dukung' => function ($q) {
                                        $q->with([
                                            'role',
                                            'kriteria_komponen' => function ($kq) {
                                                $kq->withCount('bukti_dukung')
                                                    ->with([
                                                        'sub_komponen' => function ($sq) {
                                                            $sq->withCount('kriteria_komponen');
                                                        }
                                                    ]);
                                            }
                                        ]);
                                    }
                                ]);
                        }
                    ]);
            }
        ])->where('tahun_id', $this->tahun_id)->get();

        return $komponens;
    }

    public function addKomponen()
    {
        $this->validate([
            'kd_komponen' => 'required|unique:komponen,kode',
            'nama_komponen' => 'required',
            'bobot_komponen' => 'required|numeric|min:0',
        ]);

        // Calculate total existing bobot for the same tahun_id
        $totalBobotExisting = Komponen::where('tahun_id', $this->tahun_id)->sum('bobot');

        // Check if adding new bobot would exceed 100
        if (($totalBobotExisting + $this->bobot_komponen) > 100) {
            $sisaBobot = 100 - $totalBobotExisting;
            $this->addError('bobot_komponen', "Total bobot komponen tidak boleh melebihi 100. Sisa bobot yang tersedia: {$sisaBobot}");
            return;
        }

        Komponen::create([
            'kode' => $this->kd_komponen,
            'nama' => $this->nama_komponen,
            'bobot' => $this->bobot_komponen,
            'tahun_id' => $this->tahun_id,
        ]);

        // Reset form fields
        $this->kd_komponen = '';
        $this->nama_komponen = '';
        $this->bobot_komponen = '';

        unset($this->fullMapping);
    }

    public function addSubKomponen()
    {
        $this->validate([
            'kd_sub_komponen' => 'required|unique:sub_komponen,kode',
            'nama_sub_komponen' => 'required',
            'bobot_sub_komponen' => 'required|numeric|min:0',
            'komponen_id' => 'required|exists:komponen,id',
        ]);

        // Get komponen induk
        $komponen = Komponen::find($this->komponen_id);

        // Calculate total existing bobot for the same komponen_id
        $totalBobotExisting = SubKomponen::where('komponen_id', $this->komponen_id)->sum('bobot');

        // Check if adding new bobot would exceed komponen bobot
        if (($totalBobotExisting + $this->bobot_sub_komponen) > $komponen->bobot) {
            $sisaBobot = $komponen->bobot - $totalBobotExisting;
            $this->addError('bobot_sub_komponen', "Total bobot sub komponen tidak boleh melebihi bobot komponen induk ({$komponen->bobot}). Sisa bobot yang tersedia: {$sisaBobot}");
            return;
        }

        SubKomponen::create([
            'kode' => $this->kd_sub_komponen,
            'nama' => $this->nama_sub_komponen,
            'bobot' => $this->bobot_sub_komponen,
            'komponen_id' => $this->komponen_id,
            'tahun_id' => $this->tahun_id,
        ]);

        // Reset form fields
        $this->kd_sub_komponen = '';
        $this->nama_sub_komponen = '';
        $this->bobot_sub_komponen = '';
        $this->komponen_id = '';

        unset($this->fullMapping);
    }

    public function addKriteriaKomponen()
    {
        $this->validate([
            'kd_kriteria' => 'required|unique:kriteria_komponen,kode',
            'nama_kriteria' => 'required',
            'sub_komponen_id' => 'required|exists:sub_komponen,id',
            'jenis_nilai_id' => 'required|exists:jenis_nilai,id',
            'penilaian_di_kriteria' => 'required|in:kriteria,bukti',
        ]);

        // Create Kriteria Komponen
        KriteriaKomponen::create([
            'kode' => $this->kd_kriteria,
            'nama' => $this->nama_kriteria,
            'sub_komponen_id' => $this->sub_komponen_id,
            'komponen_id' => SubKomponen::find($this->sub_komponen_id)->komponen_id,
            'jenis_nilai_id' => $this->jenis_nilai_id,
            'penilaian_di' => $this->penilaian_di_kriteria,
            'tahun_id' => $this->tahun_id,
        ]);

        // Reset form fields
        $this->kd_kriteria = '';
        $this->nama_kriteria = '';
        $this->sub_komponen_id = '';
        $this->jenis_nilai_id = '';
        $this->penilaian_di_kriteria = '';

        unset($this->fullMapping);
    }

    public function addBuktiDukung()
    {
        $this->validate([
            'nama_bukti' => 'required',
            'role_id_bukti' => 'nullable|exists:role,id',
            'is_auto_verified' => 'nullable|boolean',
            'esakip_document_type' => 'nullable|string',
            'esakip_document_code' => 'nullable|string',
        ]);

        // Create Bukti Dukung
        BuktiDukung::create([
            'nama' => $this->nama_bukti,
            'kriteria_komponen_id' => $this->kriteria_komponen_id,
            'sub_komponen_id' => KriteriaKomponen::find($this->kriteria_komponen_id)->sub_komponen_id,
            'komponen_id' => KriteriaKomponen::find($this->kriteria_komponen_id)->komponen_id,
            'role_id' => $this->role_id_bukti,
            'is_auto_verified' => $this->is_auto_verified ?? false,
            'esakip_document_type' => $this->esakip_document_type,
            'esakip_document_code' => $this->esakip_document_code,
            'tahun_id' => $this->tahun_id,
        ]);

        // Reset form fields
        $this->kd_bukti = '';
        $this->nama_bukti = '';
        $this->bobot_bukti = '';
        $this->kriteria_penilaian = '';
        $this->kriteria_komponen_id = '';
        $this->role_id_bukti = null;
        $this->is_auto_verified = false;
        $this->esakip_document_type = '';
        $this->esakip_document_code = '';

        unset($this->fullMapping);
    }

    // Edit functions
    public function editKomponen($id)
    {
        $komponen = Komponen::find($id);
        if ($komponen) {
            $this->editKomponenId = $id;
            $this->kd_komponen = $komponen->kode;
            $this->nama_komponen = $komponen->nama;
            $this->bobot_komponen = $komponen->bobot;
            $this->isEditMode = true;
        }
    }

    public function updateKomponen()
    {
        $this->validate([
            'kd_komponen' => 'required',
            'nama_komponen' => 'required',
            'bobot_komponen' => 'required|numeric|min:0',
        ]);

        $komponen = Komponen::find($this->editKomponenId);
        if ($komponen) {
            // Calculate total bobot excluding current komponen
            $totalBobotExisting = Komponen::where('tahun_id', $this->tahun_id)
                ->where('id', '!=', $this->editKomponenId)
                ->sum('bobot');

            if (($totalBobotExisting + $this->bobot_komponen) > 100) {
                $sisaBobot = 100 - $totalBobotExisting;
                $this->addError('bobot_komponen', "Total bobot komponen tidak boleh melebihi 100. Sisa bobot yang tersedia: {$sisaBobot}");
                return;
            }

            $komponen->update([
                'kode' => $this->kd_komponen,
                'nama' => $this->nama_komponen,
                'bobot' => $this->bobot_komponen,
            ]);

            $this->resetFormKomponen();
        }
    }

    public function editSubKomponen($id)
    {
        $subKomponen = SubKomponen::find($id);
        if ($subKomponen) {
            $this->editSubKomponenId = $id;
            $this->kd_sub_komponen = $subKomponen->kode;
            $this->nama_sub_komponen = $subKomponen->nama;
            $this->bobot_sub_komponen = $subKomponen->bobot;
            $this->komponen_id = $subKomponen->komponen_id;
            $this->isEditMode = true;
        }
    }

    public function updateSubKomponen()
    {
        $this->validate([
            'kd_sub_komponen' => 'required',
            'nama_sub_komponen' => 'required',
            'bobot_sub_komponen' => 'required|numeric|min:0',
        ]);

        $subKomponen = SubKomponen::find($this->editSubKomponenId);
        if ($subKomponen) {
            $komponen = Komponen::find($subKomponen->komponen_id);

            // Calculate total bobot excluding current sub komponen
            $totalBobotExisting = SubKomponen::where('komponen_id', $subKomponen->komponen_id)
                ->where('id', '!=', $this->editSubKomponenId)
                ->sum('bobot');

            if (($totalBobotExisting + $this->bobot_sub_komponen) > $komponen->bobot) {
                $sisaBobot = $komponen->bobot - $totalBobotExisting;
                $this->addError('bobot_sub_komponen', "Total bobot sub komponen tidak boleh melebihi bobot komponen induk ({$komponen->bobot}). Sisa bobot yang tersedia: {$sisaBobot}");
                return;
            }

            $subKomponen->update([
                'kode' => $this->kd_sub_komponen,
                'nama' => $this->nama_sub_komponen,
                'bobot' => $this->bobot_sub_komponen,
            ]);

            $this->resetFormSubKomponen();
        }
    }

    public function editKriteriaKomponen($id)
    {
        $kriteria = KriteriaKomponen::find($id);
        if ($kriteria) {
            $this->editKriteriaKomponenId = $id;
            $this->kd_kriteria = $kriteria->kode;
            $this->nama_kriteria = $kriteria->nama;
            $this->jenis_nilai_id = $kriteria->jenis_nilai_id;
            $this->penilaian_di_kriteria = $kriteria->penilaian_di;
            $this->sub_komponen_id = $kriteria->sub_komponen_id;
            $this->isEditMode = true;
        }
    }

    public function updateKriteriaKomponen()
    {
        $this->validate([
            'kd_kriteria' => 'required',
            'nama_kriteria' => 'required',
            'jenis_nilai_id' => 'required|exists:jenis_nilai,id',
            'penilaian_di_kriteria' => 'required|in:kriteria,bukti',
        ]);

        $kriteria = KriteriaKomponen::find($this->editKriteriaKomponenId);
        if ($kriteria) {
            $kriteria->update([
                'kode' => $this->kd_kriteria,
                'nama' => $this->nama_kriteria,
                'jenis_nilai_id' => $this->jenis_nilai_id,
                'penilaian_di' => $this->penilaian_di_kriteria,
            ]);

            $this->resetFormKriteriaKomponen();
        }
    }

    public function editBuktiDukung($id)
    {
        $bukti = BuktiDukung::find($id);
        if ($bukti) {
            $this->editBuktiDukungId = $id;
            $this->kd_bukti = $bukti->kode;
            $this->nama_bukti = $bukti->nama;
            $this->kriteria_penilaian = $bukti->kriteria_penilaian;
            $this->kriteria_komponen_id = $bukti->kriteria_komponen_id;
            $this->role_id_bukti = $bukti->role_id;
            $this->is_auto_verified = $bukti->is_auto_verified;
            $this->original_is_auto_verified = $bukti->is_auto_verified; // Simpan nilai awal
            $this->esakip_document_type = $bukti->esakip_document_type;
            $this->esakip_document_code = $bukti->esakip_document_code;
            $this->isEditMode = true;
        }
    }

    public function updateBuktiDukung()
    {
        $this->validate([
            'nama_bukti' => 'required',
            'kriteria_penilaian' => 'nullable|string',
            'role_id_bukti' => 'nullable|exists:role,id',
            'is_auto_verified' => 'nullable|boolean',
            'esakip_document_type' => 'nullable|string',
            'esakip_document_code' => 'nullable|string',
        ]);

        $bukti = BuktiDukung::find($this->editBuktiDukungId);
        if ($bukti) {
            // Deteksi perubahan is_auto_verified
            $isAutoVerifiedChanged = $this->original_is_auto_verified !== $this->is_auto_verified;
            $wasAutoVerified = $this->original_is_auto_verified;
            $nowAutoVerified = $this->is_auto_verified ?? false;

            // Update bukti dukung
            $bukti->update([
                'nama' => $this->nama_bukti,
                'kriteria_penilaian' => $this->kriteria_penilaian,
                'role_id' => $this->role_id_bukti,
                'is_auto_verified' => $nowAutoVerified,
                'esakip_document_type' => $this->esakip_document_type,
                'esakip_document_code' => $this->esakip_document_code,
            ]);

            // Handle perubahan is_auto_verified
            if ($isAutoVerifiedChanged) {
                if ($wasAutoVerified && !$nowAutoVerified) {
                    // CASE 1: true → false: Kosongkan tingkatan_nilai_id (OPD) dan is_verified (Verifikator)
                    $updatedCount = $this->clearAutoVerifiedFields($bukti);

                    flash()
                        ->use('theme.ruby')
                        ->option('position', 'bottom-right')
                        ->warning("Bukti dukung berhasil diupdate. {$updatedCount} penilaian dikosongkan karena verifikasi otomatis dinonaktifkan.");
                } elseif (!$wasAutoVerified && $nowAutoVerified) {
                    // CASE 2: false → true: Re-aktifkan auto-verified untuk penilaian yang sudah ada
                    $reactivatedCount = $this->reactivateAutoVerifiedFields($bukti);

                    if ($reactivatedCount > 0) {
                        flash()
                            ->use('theme.ruby')
                            ->option('position', 'bottom-right')
                            ->success("Bukti dukung berhasil diupdate. {$reactivatedCount} penilaian diaktifkan kembali dengan verifikasi otomatis.");
                    } else {
                        flash()
                            ->use('theme.ruby')
                            ->option('position', 'bottom-right')
                            ->success('Bukti dukung berhasil diupdate. Verifikasi otomatis akan diterapkan saat sinkronisasi dokumen berikutnya.');
                    }
                }
            } else {
                flash()
                    ->use('theme.ruby')
                    ->option('position', 'bottom-right')
                    ->success('Bukti dukung berhasil diupdate.');
            }

            $this->resetFormBuktiDukung();
        }
    }

    /**
     * Re-aktifkan field auto-verified untuk penilaian yang sudah ada
     * - Isi tingkatan_nilai_id OPD dengan nilai tertinggi (jika punya dokumen)
     * - Set is_verified = true untuk Verifikator (jika OPD punya dokumen)
     */
    private function reactivateAutoVerifiedFields($buktiDukung)
    {
        $opdRole = \App\Models\Role::where('jenis', 'opd')->first();
        $reactivatedCount = 0;

        if (!$opdRole) {
            return $reactivatedCount;
        }

        // Hitung tingkatan nilai tertinggi untuk auto-verify
        $tingkatanNilaiId = null;
        if ($buktiDukung->kriteria_komponen) {
            $jenisNilaiId = $buktiDukung->kriteria_komponen->jenis_nilai_id;
            $tingkatanNilaiTertinggi = \App\Models\TingkatanNilai::where('jenis_nilai_id', $jenisNilaiId)
                ->orderBy('bobot', 'desc')
                ->first();
            $tingkatanNilaiId = $tingkatanNilaiTertinggi?->id;
        }

        // Re-aktifkan tingkatan_nilai_id untuk penilaian OPD yang punya dokumen
        if ($tingkatanNilaiId) {
            $opdUpdated = \App\Models\Penilaian::where('bukti_dukung_id', $buktiDukung->id)
                ->where('role_id', $opdRole->id)
                ->whereNotNull('link_file')
                ->whereNull('tingkatan_nilai_id')
                ->update(['tingkatan_nilai_id' => $tingkatanNilaiId]);

            $reactivatedCount += $opdUpdated;

            // Re-aktifkan is_verified untuk Verifikator jika OPD punya dokumen
            if ($buktiDukung->role_id && $opdUpdated > 0) {
                // Ambil OPD yang baru di-update
                $opdIdsWithDocs = \App\Models\Penilaian::where('bukti_dukung_id', $buktiDukung->id)
                    ->where('role_id', $opdRole->id)
                    ->whereNotNull('link_file')
                    ->whereNotNull('tingkatan_nilai_id')
                    ->pluck('opd_id');

                // Update verifikator untuk OPD tersebut
                $verifikatorUpdated = \App\Models\Penilaian::where('bukti_dukung_id', $buktiDukung->id)
                    ->where('role_id', $buktiDukung->role_id)
                    ->whereIn('opd_id', $opdIdsWithDocs)
                    ->where('is_verified', false)
                    ->update([
                        'is_verified' => true,
                        'keterangan' => 'Auto-verified dari re-aktivasi mapping',
                    ]);

                $reactivatedCount += $verifikatorUpdated;
            }
        }

        return $reactivatedCount;
    }

    /**
     * Kosongkan field auto-verified saat verifikasi otomatis dinonaktifkan
     * - Kosongkan tingkatan_nilai_id dari penilaian OPD
     * - Kosongkan is_verified dari penilaian Verifikator
     */
    private function clearAutoVerifiedFields($buktiDukung)
    {
        $opdRole = \App\Models\Role::where('jenis', 'opd')->first();
        $updatedCount = 0;

        if (!$opdRole) {
            return $updatedCount;
        }

        // Clear tingkatan_nilai_id dari penilaian OPD
        $opdUpdated = \App\Models\Penilaian::where('bukti_dukung_id', $buktiDukung->id)
            ->where('role_id', $opdRole->id)
            ->whereNotNull('tingkatan_nilai_id')
            ->update(['tingkatan_nilai_id' => null]);

        $updatedCount += $opdUpdated;

        // Clear is_verified dari penilaian Verifikator
        if ($buktiDukung->role_id) {
            $verifikatorUpdated = \App\Models\Penilaian::where('bukti_dukung_id', $buktiDukung->id)
                ->where('role_id', $buktiDukung->role_id)
                ->where('is_verified', true)
                ->update([
                    'is_verified' => false,
                    'keterangan' => null,
                ]);

            $updatedCount += $verifikatorUpdated;
        }

        return $updatedCount;
    }



    // Reset functions
    public function resetFormKomponen()
    {
        $this->kd_komponen = '';
        $this->nama_komponen = '';
        $this->bobot_komponen = '';
        $this->editKomponenId = null;
        $this->isEditMode = false;
        unset($this->fullMapping);
    }

    public function resetFormSubKomponen()
    {
        $this->kd_sub_komponen = '';
        $this->nama_sub_komponen = '';
        $this->bobot_sub_komponen = '';
        $this->komponen_id = '';
        $this->editSubKomponenId = null;
        $this->isEditMode = false;
        unset($this->fullMapping);
    }

    public function resetFormKriteriaKomponen()
    {
        $this->kd_kriteria = '';
        $this->nama_kriteria = '';
        $this->jenis_nilai_id = '';
        $this->penilaian_di_kriteria = '';
        $this->sub_komponen_id = '';
        $this->editKriteriaKomponenId = null;
        $this->isEditMode = false;
        unset($this->fullMapping);
    }

    public function resetFormBuktiDukung()
    {
        $this->kd_bukti = '';
        $this->nama_bukti = '';
        $this->kriteria_penilaian = '';
        $this->kriteria_komponen_id = '';
        $this->role_id_bukti = null;
        $this->is_auto_verified = false;
        $this->original_is_auto_verified = null; // Reset tracking
        $this->esakip_document_type = '';
        $this->esakip_document_code = '';
        $this->editBuktiDukungId = null;
        $this->isEditMode = false;
        unset($this->fullMapping);
    }

    /**
     * Helper: Check apakah penilaian memiliki data penting (untuk audit trail)
     * Data penting: tingkatan_nilai_id, is_verified, keterangan
     */
    private function hasImportantPenilaianData($penilaianIds)
    {
        if (empty($penilaianIds)) {
            return false;
        }

        return \App\Models\Penilaian::whereIn('id', $penilaianIds)
            ->where(function ($query) {
                $query->whereNotNull('tingkatan_nilai_id')
                    ->orWhereNotNull('is_verified');
            })
            ->exists();
    }

    public function deleteBuktiDukung($id)
    {
        $bukti = BuktiDukung::find($id);
        if (!$bukti) {
            flash()->use('theme.ruby')->option('position', 'bottom-right')->error('Bukti dukung tidak ditemukan.');
            return;
        }

        // Get semua penilaian yang menggunakan bukti ini
        $penilaianRecords = \App\Models\Penilaian::where('bukti_dukung_id', $id)->get();
        $countPenilaian = $penilaianRecords->count();

        if ($countPenilaian > 0) {
            // Check apakah ada data penting
            $hasImportantData = $this->hasImportantPenilaianData($penilaianRecords->pluck('id')->toArray());

            if ($hasImportantData) {
                // Ada data penting (nilai/verifikasi/keterangan) → PREVENT DELETE
                flash()->use('theme.ruby')->option('position', 'bottom-right')
                    ->error("Tidak dapat menghapus bukti dukung. Terdapat {$countPenilaian} data penilaian");
                return;
            }

            // Tidak ada data penting → CASCADE DELETE (hapus penilaian juga)
            try {
                DB::transaction(function () use ($bukti, $countPenilaian) {
                    // Hapus penilaian terlebih dahulu (record kosong/tidak penting)
                    \App\Models\Penilaian::where('bukti_dukung_id', $bukti->id)->delete();
                    // Hapus bukti dukung
                    $bukti->delete();
                });
                unset($this->fullMapping);
                flash()->use('theme.ruby')->option('position', 'bottom-right')
                    ->success("Bukti dukung dan {$countPenilaian} record penilaian kosong berhasil dihapus.");
                return;
            } catch (\Exception $e) {
                flash()->use('theme.ruby')->option('position', 'bottom-right')->error('Gagal menghapus: ' . $e->getMessage());
                return;
            }
        }

        // Tidak ada penilaian sama sekali → DIRECT DELETE
        try {
            $bukti->delete();
            unset($this->fullMapping);
            flash()->use('theme.ruby')->option('position', 'bottom-right')->success('Bukti dukung berhasil dihapus.');
        } catch (\Exception $e) {
            flash()->use('theme.ruby')->option('position', 'bottom-right')->error('Gagal menghapus bukti dukung: ' . $e->getMessage());
        }
    }

    public function deleteKriteriaKomponen($id)
    {
        $kriteria = KriteriaKomponen::find($id);
        if (!$kriteria) {
            flash()->use('theme.ruby')->option('position', 'bottom-right')->error('Kriteria komponen tidak ditemukan.');
            return;
        }

        // Get semua penilaian yang menggunakan kriteria ini
        $penilaianKriteria = \App\Models\Penilaian::where('kriteria_komponen_id', $id)->get();
        $countPenilaianKriteria = $penilaianKriteria->count();

        // Check apakah penilaian kriteria punya data penting
        if ($countPenilaianKriteria > 0 && $this->hasImportantPenilaianData($penilaianKriteria->pluck('id')->toArray())) {
            flash()->use('theme.ruby')->option('position', 'bottom-right')
                ->error("Tidak dapat menghapus kriteria komponen. Terdapat {$countPenilaianKriteria} data penilaian");
            return;
        }

        // Check apakah ada bukti dukung
        $buktiDukungIds = $kriteria->bukti_dukung()->pluck('id')->toArray();
        $countBuktiDukung = count($buktiDukungIds);

        if ($countBuktiDukung > 0) {
            // Check apakah bukti dukung punya penilaian dengan data penting
            $penilaianBukti = \App\Models\Penilaian::whereIn('bukti_dukung_id', $buktiDukungIds)->get();
            $countPenilaianBukti = $penilaianBukti->count();

            if ($countPenilaianBukti > 0 && $this->hasImportantPenilaianData($penilaianBukti->pluck('id')->toArray())) {
                flash()->use('theme.ruby')->option('position', 'bottom-right')
                    ->error("Tidak dapat menghapus kriteria komponen. Bukti dukung yang terhubung memiliki {$countPenilaianBukti} data penilaian");
                return;
            }

            // Bukti dukung ada + mungkin ada penilaian kosong → CASCADE DELETE
            try {
                DB::transaction(function () use ($kriteria, $buktiDukungIds, $countBuktiDukung, $countPenilaianKriteria, $countPenilaianBukti) {
                    // Hapus penilaian bukti dukung (jika ada)
                    if ($countPenilaianBukti > 0) {
                        \App\Models\Penilaian::whereIn('bukti_dukung_id', $buktiDukungIds)->delete();
                    }
                    // Hapus penilaian kriteria (jika ada)
                    if ($countPenilaianKriteria > 0) {
                        \App\Models\Penilaian::where('kriteria_komponen_id', $kriteria->id)->delete();
                    }
                    // Hapus bukti dukung
                    $kriteria->bukti_dukung()->delete();
                    // Hapus kriteria
                    $kriteria->delete();
                });
                unset($this->fullMapping);

                $totalPenilaian = $countPenilaianKriteria + $countPenilaianBukti;
                $message = "Kriteria komponen dan {$countBuktiDukung} bukti dukung";
                if ($totalPenilaian > 0) {
                    $message .= " (serta {$totalPenilaian} record penilaian kosong)";
                }
                $message .= " berhasil dihapus.";

                flash()->use('theme.ruby')->option('position', 'bottom-right')->success($message);
                return;
            } catch (\Exception $e) {
                flash()->use('theme.ruby')->option('position', 'bottom-right')->error('Gagal menghapus: ' . $e->getMessage());
                return;
            }
        }

        // Tidak ada bukti dukung, tapi mungkin ada penilaian kosong di kriteria
        if ($countPenilaianKriteria > 0) {
            try {
                DB::transaction(function () use ($kriteria, $countPenilaianKriteria) {
                    // Hapus penilaian kriteria
                    \App\Models\Penilaian::where('kriteria_komponen_id', $kriteria->id)->delete();
                    // Hapus kriteria
                    $kriteria->delete();
                });
                unset($this->fullMapping);
                flash()->use('theme.ruby')->option('position', 'bottom-right')
                    ->success("Kriteria komponen dan {$countPenilaianKriteria} data penilaian berhasil dihapus.");
                return;
            } catch (\Exception $e) {
                flash()->use('theme.ruby')->option('position', 'bottom-right')->error('Gagal menghapus: ' . $e->getMessage());
                return;
            }
        }

        // Tidak ada bukti dukung dan tidak ada penilaian - DIRECT DELETE
        try {
            $kriteria->delete();
            unset($this->fullMapping);
            flash()->use('theme.ruby')->option('position', 'bottom-right')->success('Kriteria komponen berhasil dihapus.');
        } catch (\Exception $e) {
            flash()->use('theme.ruby')->option('position', 'bottom-right')->error('Gagal menghapus kriteria komponen: ' . $e->getMessage());
        }
    }

    public function deleteSubKomponen($id)
    {
        $subKomponen = SubKomponen::find($id);
        if (!$subKomponen) {
            flash()->use('theme.ruby')->option('position', 'bottom-right')->error('Sub komponen tidak ditemukan.');
            return;
        }

        // Check apakah ada kriteria komponen
        $kriteriaIds = $subKomponen->kriteria_komponen()->pluck('id')->toArray();
        $countKriteria = count($kriteriaIds);

        if ($countKriteria > 0) {
            // Check apakah ada penilaian dengan data penting
            $penilaianKriteria = \App\Models\Penilaian::whereIn('kriteria_komponen_id', $kriteriaIds)->get();
            $countPenilaianKriteria = $penilaianKriteria->count();

            if ($countPenilaianKriteria > 0 && $this->hasImportantPenilaianData($penilaianKriteria->pluck('id')->toArray())) {
                flash()->use('theme.ruby')->option('position', 'bottom-right')
                    ->error("Tidak dapat menghapus sub komponen. Kriteria yang terhubung memiliki {$countPenilaianKriteria} data penilaian");
                return;
            }

            // Check bukti dukung dari kriteria
            $buktiDukungIds = \App\Models\BuktiDukung::whereIn('kriteria_komponen_id', $kriteriaIds)->pluck('id')->toArray();
            $penilaianBukti = \App\Models\Penilaian::whereIn('bukti_dukung_id', $buktiDukungIds)->get();
            $countPenilaianBukti = $penilaianBukti->count();

            if ($countPenilaianBukti > 0 && $this->hasImportantPenilaianData($penilaianBukti->pluck('id')->toArray())) {
                flash()->use('theme.ruby')->option('position', 'bottom-right')
                    ->error("Tidak dapat menghapus sub komponen. Bukti dukung yang terhubung memiliki {$countPenilaianBukti} data penilaian");
                return;
            }

            // Ada kriteria + mungkin ada penilaian kosong → CASCADE DELETE
            try {
                DB::transaction(function () use ($subKomponen, $countKriteria, $buktiDukungIds, $countPenilaianKriteria, $countPenilaianBukti) {
                    // Hapus penilaian bukti dukung (jika ada)
                    if ($countPenilaianBukti > 0) {
                        \App\Models\Penilaian::whereIn('bukti_dukung_id', $buktiDukungIds)->delete();
                    }
                    // Hapus penilaian kriteria (jika ada)
                    if ($countPenilaianKriteria > 0) {
                        \App\Models\Penilaian::whereIn('kriteria_komponen_id', $subKomponen->kriteria_komponen()->pluck('id'))->delete();
                    }
                    // Hapus bukti dukung dari semua kriteria
                    foreach ($subKomponen->kriteria_komponen as $kriteria) {
                        $kriteria->bukti_dukung()->delete();
                    }
                    // Hapus kriteria komponen
                    $subKomponen->kriteria_komponen()->delete();
                    // Hapus sub komponen
                    $subKomponen->delete();
                });
                unset($this->fullMapping);

                $totalPenilaian = $countPenilaianKriteria + $countPenilaianBukti;
                $message = "Sub komponen dan {$countKriteria} kriteria komponen (beserta bukti dukung)";
                if ($totalPenilaian > 0) {
                    $message .= " serta {$totalPenilaian} data penilaian";
                }
                $message .= " berhasil dihapus.";

                flash()->use('theme.ruby')->option('position', 'bottom-right')->success($message);
                return;
            } catch (\Exception $e) {
                flash()->use('theme.ruby')->option('position', 'bottom-right')->error('Gagal menghapus: ' . $e->getMessage());
                return;
            }
        }

        // Tidak ada kriteria - DIRECT DELETE
        try {
            $subKomponen->delete();
            unset($this->fullMapping);
            flash()->use('theme.ruby')->option('position', 'bottom-right')->success('Sub komponen berhasil dihapus.');
        } catch (\Exception $e) {
            flash()->use('theme.ruby')->option('position', 'bottom-right')->error('Gagal menghapus sub komponen: ' . $e->getMessage());
        }
    }

    public function deleteKomponen($id)
    {
        $komponen = Komponen::find($id);
        if (!$komponen) {
            flash()->use('theme.ruby')->option('position', 'bottom-right')->error('Komponen tidak ditemukan.');
            return;
        }

        // Check apakah ada sub komponen
        $subKomponenIds = $komponen->sub_komponen()->pluck('id')->toArray();
        $countSubKomponen = count($subKomponenIds);

        if ($countSubKomponen > 0) {
            // Check apakah ada kriteria dari sub komponen tersebut
            $kriteriaIds = \App\Models\KriteriaKomponen::whereIn('sub_komponen_id', $subKomponenIds)->pluck('id')->toArray();

            if (count($kriteriaIds) > 0) {
                // Check apakah ada penilaian kriteria dengan data penting
                $penilaianKriteria = \App\Models\Penilaian::whereIn('kriteria_komponen_id', $kriteriaIds)->get();
                $countPenilaianKriteria = $penilaianKriteria->count();

                if ($countPenilaianKriteria > 0 && $this->hasImportantPenilaianData($penilaianKriteria->pluck('id')->toArray())) {
                    flash()->use('theme.ruby')->option('position', 'bottom-right')
                        ->error("Tidak dapat menghapus komponen. Terdapat {$countPenilaianKriteria} penilaian pada kriteria");
                    return;
                }

                // Check bukti dukung
                $buktiDukungIds = \App\Models\BuktiDukung::whereIn('kriteria_komponen_id', $kriteriaIds)->pluck('id')->toArray();
                $penilaianBukti = \App\Models\Penilaian::whereIn('bukti_dukung_id', $buktiDukungIds)->get();
                $countPenilaianBukti = $penilaianBukti->count();

                if ($countPenilaianBukti > 0 && $this->hasImportantPenilaianData($penilaianBukti->pluck('id')->toArray())) {
                    flash()->use('theme.ruby')->option('position', 'bottom-right')
                        ->error("Tidak dapat menghapus komponen. Bukti dukung yang terhubung memiliki {$countPenilaianBukti} penilaian");
                    return;
                }
            }

            // Ada sub komponen + mungkin ada penilaian kosong → CASCADE DELETE
            try {
                $totalPenilaian = 0;
                DB::transaction(function () use ($komponen, $countSubKomponen, &$totalPenilaian) {
                    // Hapus semua penilaian kosong terlebih dahulu
                    $kriteriaIds = \App\Models\KriteriaKomponen::whereIn('sub_komponen_id', $komponen->sub_komponen()->pluck('id'))->pluck('id')->toArray();
                    $buktiDukungIds = \App\Models\BuktiDukung::whereIn('kriteria_komponen_id', $kriteriaIds)->pluck('id')->toArray();

                    // Hapus penilaian bukti dukung
                    $deletedBukti = \App\Models\Penilaian::whereIn('bukti_dukung_id', $buktiDukungIds)->delete();
                    // Hapus penilaian kriteria
                    $deletedKriteria = \App\Models\Penilaian::whereIn('kriteria_komponen_id', $kriteriaIds)->delete();
                    $totalPenilaian = $deletedBukti + $deletedKriteria;

                    // Hapus bukti dukung dari semua kriteria di semua sub komponen
                    foreach ($komponen->sub_komponen as $subKomponen) {
                        foreach ($subKomponen->kriteria_komponen as $kriteria) {
                            $kriteria->bukti_dukung()->delete();
                        }
                        $subKomponen->kriteria_komponen()->delete();
                    }
                    // Hapus sub komponen
                    $komponen->sub_komponen()->delete();
                    // Hapus komponen
                    $komponen->delete();
                });
                unset($this->fullMapping);

                $message = "Komponen dan {$countSubKomponen} sub komponen (beserta kriteria & bukti dukung)";
                if ($totalPenilaian > 0) {
                    $message .= " serta {$totalPenilaian} record penilaian kosong";
                }
                $message .= " berhasil dihapus.";

                flash()->use('theme.ruby')->option('position', 'bottom-right')->success($message);
                return;
            } catch (\Exception $e) {
                flash()->use('theme.ruby')->option('position', 'bottom-right')->error('Gagal menghapus: ' . $e->getMessage());
                return;
            }
        }

        // Tidak ada sub komponen - DIRECT DELETE
        try {
            $komponen->delete();
            unset($this->fullMapping);
            flash()->use('theme.ruby')->option('position', 'bottom-right')->success('Komponen berhasil dihapus.');
        } catch (\Exception $e) {
            flash()->use('theme.ruby')->option('position', 'bottom-right')->error('Gagal menghapus komponen: ' . $e->getMessage());
        }
    }
}
