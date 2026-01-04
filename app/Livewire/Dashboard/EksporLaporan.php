<?php

namespace App\Livewire\Dashboard;

use Livewire\Attributes\Computed;
use Livewire\Component;
use PhpOffice\PhpWord\TemplateProcessor;
use App\Models\Opd;
use App\Models\Komponen;
use App\Models\SubKomponen;
use App\Models\KriteriaKomponen;
use App\Models\Penilaian;
use App\Models\Role;
use App\Models\KontenLaporan;
use App\Models\TemplateLaporan;
use Illuminate\Support\Facades\Session;

class EksporLaporan extends Component
{
    public $opd_selected_id;
    public $tahun_id;
    public $tanggal_ekspor; // Format: "Trenggalek, 3 Januari 2026"

    // Catatan dan Rekomendasi per komponen (untuk testing, nanti bisa dari database)
    public $catatan = [];
    public $rekomendasi = [];
    public $deskripsi = []; // deskripsi[komponen_id][sub_komponen_id] = text

    // Template properties
    public $namaTemplate = '';
    public $selectedTemplateId = null;

    public function mount()
    {
        // Get tahun from session or current year
        $this->tahun_id = Session::get('tahun_session');

        // Set default tanggal ekspor (format Indonesia)
        $this->tanggal_ekspor = $this->formatTanggalIndonesia(now());

        // Initialize default catatan & rekomendasi (nanti bisa dari database)
        $this->initializeCatatanRekomendasi();
    }

    /**
     * Format tanggal ke format Indonesia: "Trenggalek, 3 Januari 2026"
     */
    private function formatTanggalIndonesia($date)
    {
        $bulanIndonesia = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember'
        ];

        $tanggal = $date->format('j');
        $bulan = $bulanIndonesia[(int)$date->format('n')];
        $tahun = $date->format('Y');

        return "Trenggalek, {$tanggal} {$bulan} {$tahun}";
    }

    private function initializeCatatanRekomendasi()
    {
        // Load data dari database jika OPD dan Tahun sudah dipilih
        if ($this->opd_selected_id && $this->tahun_id) {
            $this->loadDataFromDatabase();
        } else {
            // Initialize empty arrays
            if (empty($this->catatan)) {
                $this->catatan = [];
            }

            if (empty($this->rekomendasi)) {
                $this->rekomendasi = [];
            }

            if (empty($this->deskripsi)) {
                $this->deskripsi = [];
            }
        }
    }

    /**
     * Load data dari database
     */
    private function loadDataFromDatabase()
    {
        if (!$this->opd_selected_id || !$this->tahun_id) {
            return;
        }

        // Load deskripsi
        $deskripsiData = KontenLaporan::deskripsi()
            ->forOpdTahun($this->opd_selected_id, $this->tahun_id)
            ->get();

        $this->deskripsi = [];
        foreach ($deskripsiData as $item) {
            if ($item->komponen_id && $item->sub_komponen_id) {
                $this->deskripsi[$item->komponen_id][$item->sub_komponen_id] = $item->konten;
            }
        }

        // Load catatan
        $catatanData = KontenLaporan::catatan()
            ->forOpdTahun($this->opd_selected_id, $this->tahun_id)
            ->get();

        $this->catatan = [];
        foreach ($catatanData as $item) {
            if ($item->komponen_id) {
                if (!isset($this->catatan[$item->komponen_id])) {
                    $this->catatan[$item->komponen_id] = [];
                }
                $this->catatan[$item->komponen_id][] = $item->konten;
            }
        }

        // Load rekomendasi
        $rekomendasiData = KontenLaporan::rekomendasi()
            ->forOpdTahun($this->opd_selected_id, $this->tahun_id)
            ->get();

        $this->rekomendasi = [];
        foreach ($rekomendasiData as $item) {
            if ($item->komponen_id) {
                if (!isset($this->rekomendasi[$item->komponen_id])) {
                    $this->rekomendasi[$item->komponen_id] = [];
                }
                $this->rekomendasi[$item->komponen_id][] = $item->konten;
            }
        }
    }

    /**
     * Add catatan untuk komponen tertentu
     */
    public function addCatatan($komponenId)
    {
        if (!isset($this->catatan[$komponenId])) {
            $this->catatan[$komponenId] = [];
        }

        $this->catatan[$komponenId][] = '';
    }

    /**
     * Remove catatan dari komponen
     */
    public function removeCatatan($komponenId, $index)
    {
        if (isset($this->catatan[$komponenId][$index])) {
            unset($this->catatan[$komponenId][$index]);
            $this->catatan[$komponenId] = array_values($this->catatan[$komponenId]); // Re-index
        }
    }

    /**
     * Add rekomendasi untuk komponen tertentu
     */
    public function addRekomendasi($komponenId)
    {
        if (!isset($this->rekomendasi[$komponenId])) {
            $this->rekomendasi[$komponenId] = [];
        }

        $this->rekomendasi[$komponenId][] = '';
    }

    /**
     * Remove rekomendasi dari komponen
     */
    public function removeRekomendasi($komponenId, $index)
    {
        if (isset($this->rekomendasi[$komponenId][$index])) {
            unset($this->rekomendasi[$komponenId][$index]);
            $this->rekomendasi[$komponenId] = array_values($this->rekomendasi[$komponenId]); // Re-index
        }
    }
    // Deskripsi tidak perlu add/remove karena fixed per sub komponen
    // User hanya update text yang sudah ada

    /**
     * Updated saat OPD berubah
     */
    public function updatedOpdSelectedId($value)
    {
        if ($value && $this->tahun_id) {
            $this->loadDataFromDatabase();
        }
    }

    /**
     * Save deskripsi ke database
     */
    private function saveDeskripsiToDatabase()
    {
        if (!$this->opd_selected_id || !$this->tahun_id) {
            return;
        }

        foreach ($this->deskripsi as $komponenId => $subKomponens) {
            foreach ($subKomponens as $subKomponenId => $konten) {
                // Skip empty content
                if (empty($konten)) {
                    continue;
                }

                // Update or create
                KontenLaporan::updateOrCreate(
                    [
                        'type' => 'deskripsi',
                        'komponen_id' => $komponenId,
                        'sub_komponen_id' => $subKomponenId,
                        'opd_id' => $this->opd_selected_id,
                        'tahun_id' => $this->tahun_id,
                    ],
                    [
                        'konten' => $konten,
                        'urutan' => 0,
                    ]
                );
            }
        }
    }

    /**
     * Save catatan ke database
     */
    private function saveCatatanToDatabase()
    {
        if (!$this->opd_selected_id || !$this->tahun_id) {
            return;
        }

        foreach ($this->catatan as $komponenId => $catatanList) {
            // Delete existing catatan for this komponen
            KontenLaporan::where('type', 'catatan')
                ->where('komponen_id', $komponenId)
                ->where('opd_id', $this->opd_selected_id)
                ->where('tahun_id', $this->tahun_id)
                ->delete();

            // Insert new catatan
            $urutan = 1;
            foreach ($catatanList as $konten) {
                if (empty($konten)) {
                    continue;
                }

                KontenLaporan::create([
                    'type' => 'catatan',
                    'komponen_id' => $komponenId,
                    'opd_id' => $this->opd_selected_id,
                    'tahun_id' => $this->tahun_id,
                    'konten' => $konten,
                    'urutan' => $urutan,
                ]);

                $urutan++;
            }
        }
    }

    /**
     * Save rekomendasi ke database
     */
    private function saveRekomendasiToDatabase()
    {
        if (!$this->opd_selected_id || !$this->tahun_id) {
            return;
        }

        foreach ($this->rekomendasi as $komponenId => $rekomendasiList) {
            // Delete existing rekomendasi for this komponen
            KontenLaporan::where('type', 'rekomendasi')
                ->where('komponen_id', $komponenId)
                ->where('opd_id', $this->opd_selected_id)
                ->where('tahun_id', $this->tahun_id)
                ->delete();

            // Insert new rekomendasi
            $urutan = 1;
            foreach ($rekomendasiList as $konten) {
                if (empty($konten)) {
                    continue;
                }

                KontenLaporan::create([
                    'type' => 'rekomendasi',
                    'komponen_id' => $komponenId,
                    'opd_id' => $this->opd_selected_id,
                    'tahun_id' => $this->tahun_id,
                    'konten' => $konten,
                    'urutan' => $urutan,
                ]);

                $urutan++;
            }
        }
    }

    /**
     * Simpan form saat ini sebagai template
     */
    public function simpanTemplate()
    {
        // Validate nama template
        $this->validate([
            'namaTemplate' => 'required|string|max:100',
        ], [
            'namaTemplate.required' => 'Nama template harus diisi',
            'namaTemplate.max' => 'Nama template maksimal 100 karakter',
        ]);

        // Save template
        TemplateLaporan::create([
            'nama' => $this->namaTemplate,
            'konten' => TemplateLaporan::encodeKonten(
                $this->deskripsi,
                $this->catatan,
                $this->rekomendasi
            ),
        ]);

        // Reset nama template
        $this->namaTemplate = '';

        // Flash message
        flash()->use('theme.ruby')->option('position', 'bottom-right')->success('Template berhasil disimpan');
    }

    /**
     * Set selected template (dipanggil dari modal list)
     */
    public function selectTemplate($templateId)
    {
        $this->selectedTemplateId = $templateId;
    }

    /**
     * Load template yang dipilih (setelah konfirmasi)
     */
    public function applyTemplate()
    {
        if (!$this->selectedTemplateId) {
            session()->flash('error', 'Pilih template terlebih dahulu');
            return;
        }

        $template = TemplateLaporan::find($this->selectedTemplateId);
        if (!$template) {
            session()->flash('error', 'Template tidak ditemukan');
            return;
        }

        // Load konten dari template
        $data = $template->decodeKonten();
        $this->deskripsi = $data['deskripsi'];
        $this->catatan = $data['catatan'];
        $this->rekomendasi = $data['rekomendasi'];

        // Reset selected template
        $this->selectedTemplateId = null;

        // Flash message
        flash()->use('theme.ruby')->option('position', 'bottom-right')->success('Template berhasil diterapkan');


        // Dispatch event untuk close modal
        $this->dispatch('templateApplied');
    }

    /**
     * Hapus template
     */
    public function hapusTemplate($templateId)
    {
        $template = TemplateLaporan::find($templateId);
        if ($template) {
            $template->delete();
            flash()->use('theme.ruby')->option('position', 'bottom-right')->success('Template berhasil dihapus');
        }
    }

    #[Computed]
    public function opdList()
    {
        return Opd::all();
    }

    #[Computed]
    public function templateList()
    {
        return TemplateLaporan::orderBy('created_at', 'desc')->get();
    }

    #[Computed]
    public function previewData()
    {
        if (!$this->opd_selected_id || !$this->tahun_id) {
            return null;
        }

        $opd = Opd::find($this->opd_selected_id);
        $komponens = Komponen::where('tahun_id', $this->tahun_id)
            ->with(['sub_komponen.kriteria_komponen'])
            ->orderBy('id')
            ->get();

        // Get Penilai role ID (Penjamin Kualitas)
        $penilaiRole = Role::where('jenis', 'penilai')->first();
        $roleId = $penilaiRole ? $penilaiRole->id : null;

        $data = [
            'opd' => $opd,
            'komponens' => [],
            'total_nilai' => 0,
            'kategori_nilai' => '',
        ];

        foreach ($komponens as $komponen) {
            $nilaiKomponen = $this->calculateNilaiKomponen($komponen, $opd->id, $roleId);
            $data['total_nilai'] += $nilaiKomponen;

            $komponenData = [
                'id' => $komponen->id,  // Tambahkan ID untuk form
                'nama' => $komponen->nama,
                'bobot' => $komponen->bobot,
                'nilai' => $nilaiKomponen,
                'persentase_capaian' => $komponen->bobot > 0 ? round(($nilaiKomponen / $komponen->bobot) * 100, 2) : 0,
                'sub_komponens' => [],
                'catatan' => $this->catatan[$komponen->id] ?? [],
                'rekomendasi' => $this->rekomendasi[$komponen->id] ?? [],
            ];


            foreach ($komponen->sub_komponen as $subKomponen) {
                $nilaiSubKomponen = $this->calculateNilaiSubKomponen($subKomponen, $opd->id, $roleId);
                $komponenData['sub_komponens'][] = [
                    'id' => $subKomponen->id, // Tambahkan ID untuk form deskripsi
                    'nama' => $subKomponen->nama,
                    'bobot' => $subKomponen->bobot,
                    'nilai' => $nilaiSubKomponen,
                ];
            }

            $data['komponens'][] = $komponenData;
        }

        // Calculate total bobot for percentage
        $totalBobot = $komponens->sum('bobot');
        $data['persentase_total'] = $totalBobot > 0 ? round(($data['total_nilai'] / $totalBobot) * 100, 2) : 0;
        $data['kategori_nilai'] = $this->getKategoriNilai($data['persentase_total']);

        return $data;
    }

    public function export()
    {
        // Validate
        if (!$this->opd_selected_id) {
            session()->flash('error', 'Pilih OPD terlebih dahulu');
            return;
        }

        // Save data ke database sebelum ekspor
        $this->saveDeskripsiToDatabase();
        $this->saveCatatanToDatabase();
        $this->saveRekomendasiToDatabase();

        // Get template path
        $templatePath = public_path('assets/template/lap2025.docx');

        if (!file_exists($templatePath)) {
            session()->flash('error', 'Template tidak ditemukan');
            return;
        }

        // Load template
        $template = new TemplateProcessor($templatePath);

        // Get OPD data
        $opd = Opd::find($this->opd_selected_id);

        // Get komponens with relations
        $komponens = Komponen::where('tahun_id', $this->tahun_id)
            ->with(['sub_komponen.kriteria_komponen'])
            ->orderBy('id')
            ->get();

        // Get Penilai role ID (Penjamin Kualitas) - nilai akhir
        $penilaiRole = Role::where('jenis', 'penilai')->first();
        if (!$penilaiRole) {
            session()->flash('error', 'Role Penilai tidak ditemukan');
            return;
        }
        $roleId = $penilaiRole->id;

        // Get tahun data
        $tahunData = \App\Models\Tahun::find($this->tahun_id);

        // Set simple values
        $template->setValue('nama_opd', $opd->nama);
        $template->setValue('nama_opd_caps', strtoupper($opd->nama));
        $template->setValue('jumlah_komponen', $komponens->count());
        $template->setValue('tahun', $tahunData ? $tahunData->tahun : date('Y'));

        // Calculate total nilai OPD and total bobot
        $totalNilaiOpd = 0;
        $totalBobot = $komponens->sum('bobot');

        foreach ($komponens as $komponen) {
            $nilaiKomponen = $this->calculateNilaiKomponen($komponen, $opd->id, $roleId);
            $totalNilaiOpd += $nilaiKomponen;
        }

        $persentaseTotal = $totalBobot > 0 ? round(($totalNilaiOpd / $totalBobot) * 100, 2) : 0;

        $template->setValue('nilai_opd', number_format($persentaseTotal, 2, ',', '.') . '%');
        $template->setValue('kategori_nilai', $this->getKategoriNilai($persentaseTotal));
        $template->setValue('total_bobot', number_format($totalBobot, 2, ',', '.') . '%');

        // Variabel untuk keterangan tabel (diletakkan di luar/setelah tabel)
        $template->setValue('keterangan_tabel', "Nilai keseluruhan sebesar {$persentaseTotal}% {$this->getKategoriNilai($persentaseTotal)}");

        // ===== TABEL KOMPONEN (menggunakan cloneRow) =====
        // Clone row untuk tabel komponen
        $template->cloneRow('komponen_nama', count($komponens));

        $rowIndex = 1;
        foreach ($komponens as $komponen) {
            $nilaiKomponen = $this->calculateNilaiKomponen($komponen, $opd->id, $roleId);
            $persentaseCapaian = $komponen->bobot > 0 ? round(($nilaiKomponen / $komponen->bobot) * 100, 2) : 0;

            $template->setValue('komponen_no#' . $rowIndex, $rowIndex . '.');
            $template->setValue('komponen_nama#' . $rowIndex, $komponen->nama);
            $template->setValue('komponen_bobot#' . $rowIndex, number_format($komponen->bobot, 2, ',', '.') . '%');
            $template->setValue('komponen_nilai#' . $rowIndex, number_format($persentaseCapaian, 2, ',', '.') . '%');
            $template->setValue('komponen_keterangan#' . $rowIndex, ''); // Kolom keterangan kosong

            $rowIndex++;
        }

        // Clone block for komponen (untuk detail text, bukan tabel)
        $template->cloneBlock('block_komponen', count($komponens), true, true);

        // Fill komponen data
        $komponenIndex = 1;
        foreach ($komponens as $komponen) {
            $nilaiKomponen = $this->calculateNilaiKomponen($komponen, $opd->id, $roleId);

            $template->setValue('nama_komponen#' . $komponenIndex, $komponen->nama);
            $template->setValue('nilai_komponen#' . $komponenIndex, number_format($nilaiKomponen, 2, ',', '.'));
            $template->setValue('bobot_komponen#' . $komponenIndex, number_format($komponen->bobot, 2, ',', '.'));

            // Clone block for sub komponen
            $subKomponens = $komponen->sub_komponen;
            $template->cloneBlock('block_sub_komponen#' . $komponenIndex, count($subKomponens), true, true);

            // Fill sub komponen data
            $subKomponenIndex = 1;
            foreach ($subKomponens as $subKomponen) {
                $nilaiSubKomponen = $this->calculateNilaiSubKomponen($subKomponen, $opd->id, $roleId);

                // Convert index to letter (1=a, 2=b, 3=c, dst)
                $huruf = chr(96 + $subKomponenIndex); // 96 + 1 = 97 = 'a'

                $template->setValue('sub_komponen_huruf#' . $komponenIndex . '#' . $subKomponenIndex, $huruf . '.');
                $template->setValue('nama_sub_komponen#' . $komponenIndex . '#' . $subKomponenIndex, $subKomponen->nama);
                $template->setValue('nilai_sub_komponen#' . $komponenIndex . '#' . $subKomponenIndex, number_format($nilaiSubKomponen, 2, ',', '.'));
                $template->setValue('bobot_sub_komponen#' . $komponenIndex . '#' . $subKomponenIndex, number_format($subKomponen->bobot, 2, ',', '.'));

                // Set deskripsi
                $deskripsiText = $this->deskripsi[$komponen->id][$subKomponen->id] ?? '';
                $template->setValue('deskripsi#' . $komponenIndex . '#' . $subKomponenIndex, $deskripsiText);

                $subKomponenIndex++;
            }

            $komponenIndex++;
        }

        // ===== CATATAN & REKOMENDASI =====
        // Clone block untuk catatan per komponen
        $template->cloneBlock('block_catatan_komponen', count($komponens), true, true);

        $catatanIndex = 1;
        foreach ($komponens as $komponen) {
            $template->setValue('catatan_komponen_nama#' . $catatanIndex, $komponen->nama);

            // Get catatan untuk komponen ini
            $catatanList = $this->catatan[$komponen->id] ?? [];

            if (count($catatanList) > 0) {
                // Clone block untuk catatan items
                $template->cloneBlock('block_catatan_item#' . $catatanIndex, count($catatanList), true, true);

                $catatanItemIndex = 1;
                foreach ($catatanList as $catatanText) {
                    // Tambahkan nomor manual agar reset di setiap komponen
                    $template->setValue('catatan_item#' . $catatanIndex . '#' . $catatanItemIndex, $catatanItemIndex . ') ' . $catatanText);
                    $catatanItemIndex++;
                }
            } else {
                // Jika tidak ada catatan, tampilkan 2 item dengan '-' dan nomor manual
                $template->cloneBlock('block_catatan_item#' . $catatanIndex, 2, true, true);
                $template->setValue('catatan_item#' . $catatanIndex . '#1', '1) -');
                $template->setValue('catatan_item#' . $catatanIndex . '#2', '2) -');
            }

            $catatanIndex++;
        }

        // Clone block untuk rekomendasi per komponen
        $template->cloneBlock('block_rekomendasi_komponen', count($komponens), true, true);

        $rekomendasiIndex = 1;
        foreach ($komponens as $komponen) {
            $template->setValue('rekomendasi_komponen_nama#' . $rekomendasiIndex, $komponen->nama);

            // Get rekomendasi untuk komponen ini
            $rekomendasiList = $this->rekomendasi[$komponen->id] ?? [];

            if (count($rekomendasiList) > 0) {
                // Clone block untuk rekomendasi items
                $template->cloneBlock('block_rekomendasi_item#' . $rekomendasiIndex, count($rekomendasiList), true, true);

                $rekomendasiItemIndex = 1;
                foreach ($rekomendasiList as $rekomendasiText) {
                    // Tambahkan nomor manual agar reset di setiap komponen
                    $template->setValue('rekomendasi_item#' . $rekomendasiIndex . '#' . $rekomendasiItemIndex, $rekomendasiItemIndex . ') ' . $rekomendasiText);
                    $rekomendasiItemIndex++;
                }
            } else {
                // Jika tidak ada rekomendasi, tampilkan 2 item dengan '-' dan nomor manual
                $template->cloneBlock('block_rekomendasi_item#' . $rekomendasiIndex, 2, true, true);
                $template->setValue('rekomendasi_item#' . $rekomendasiIndex . '#1', '1) -');
                $template->setValue('rekomendasi_item#' . $rekomendasiIndex . '#2', '2) -');
            }

            $rekomendasiIndex++;
        }

        // ===== TABEL PERBANDINGAN TAHUN (BAB III) =====
        // Get tahun saat ini dan tahun sebelumnya dari database
        $tahunSaatIni = \App\Models\Tahun::find($this->tahun_id);
        $tahunSebelumnya = \App\Models\Tahun::find($this->tahun_id - 1);

        // Set variabel display tahun untuk header tabel
        $template->setValue('tahun_sebelumnya', $tahunSebelumnya ? $tahunSebelumnya->tahun : '-');
        $template->setValue('tahun_saat_ini', $tahunSaatIni ? $tahunSaatIni->tahun : '-');

        // Hitung nilai tahun sebelumnya
        $komponensTahunLalu = [];
        $totalNilaiTahunLalu = 0;

        if ($tahunSebelumnya) {
            $komponensTahunLalu = Komponen::where('tahun_id', $tahunSebelumnya->id)
                ->with(['sub_komponen.kriteria_komponen'])
                ->orderBy('id')
                ->get();
        }

        // Clone row untuk tabel perbandingan
        \Log::info('Cloning perbandingan table with ' . count($komponens) . ' rows');
        $template->cloneRow('perbandingan_komponen', count($komponens));

        $rowIndex = 1;
        foreach ($komponens as $komponen) {
            // Nilai tahun ini
            $nilaiKomponenIni = $this->calculateNilaiKomponen($komponen, $opd->id, $roleId);

            // Cari komponen yang sama di tahun lalu (by nama)
            $nilaiKomponenLalu = 0;
            if (count($komponensTahunLalu) > 0) {
                $komponenTahunLalu = collect($komponensTahunLalu)->firstWhere('nama', $komponen->nama);
                if ($komponenTahunLalu) {
                    $nilaiKomponenLalu = $this->calculateNilaiKomponen($komponenTahunLalu, $opd->id, $roleId);
                    $totalNilaiTahunLalu += $nilaiKomponenLalu;
                }
            }

            $template->setValue('perbandingan_no#' . $rowIndex, $rowIndex . '.');
            $template->setValue('perbandingan_komponen#' . $rowIndex, $komponen->nama);
            $template->setValue('perbandingan_bobot#' . $rowIndex, number_format($komponen->bobot, 2, ',', '.'));
            $template->setValue('perbandingan_nilai_tahun_lalu#' . $rowIndex, number_format($nilaiKomponenLalu, 2, ',', '.'));
            $template->setValue('perbandingan_nilai_tahun_ini#' . $rowIndex, number_format($nilaiKomponenIni, 2, ',', '.'));

            $rowIndex++;
        }

        // Hitung persentase total tahun lalu
        $persentaseTotalTahunLalu = $totalBobot > 0 ? round(($totalNilaiTahunLalu / $totalBobot) * 100, 2) : 0;

        // Set variabel untuk row total perbandingan
        $template->setValue('total_bobot_100', '100,00');
        $template->setValue('total_nilai_tahun_lalu', number_format($persentaseTotalTahunLalu, 2, ',', '.'));
        $template->setValue('total_nilai_tahun_ini', number_format($persentaseTotal, 2, ',', '.'));
        $template->setValue('predikat_tahun_lalu', $this->getKategoriNilai($persentaseTotalTahunLalu));
        $template->setValue('predikat_tahun_ini', $this->getKategoriNilai($persentaseTotal));
        // Set tanggal
        $template->setValue('tanggal_ekspor', $this->tanggal_ekspor);

        // Save file
        $fileName = 'Laporan_Evaluasi_' . str_replace(' ', '_', $opd->nama) . '_' . date('Y-m-d_His') . '.docx';
        $outputPath = storage_path('app/public/exports/' . $fileName);

        // Create directory if not exists
        if (!file_exists(storage_path('app/public/exports'))) {
            mkdir(storage_path('app/public/exports'), 0755, true);
        }

        $template->saveAs($outputPath);

        // Download file
        return response()->download($outputPath, $fileName)->deleteFileAfterSend(true);
    }

    /**
     * Hitung nilai komponen = SUM nilai semua sub komponen
     * Menggunakan getNilai() dari KriteriaKomponen model
     */
    private function calculateNilaiKomponen($komponen, $opdId, $roleId)
    {
        $totalNilai = 0;

        foreach ($komponen->sub_komponen as $subKomponen) {
            $totalNilai += $this->calculateNilaiSubKomponen($subKomponen, $opdId, $roleId);
        }

        return round($totalNilai, 2);
    }

    /**
     * Hitung nilai sub komponen = SUM nilai semua kriteria
     * Menggunakan getNilai() dari KriteriaKomponen model
     * Sama seperti di Dashboard dan LembarKerja
     */
    private function calculateNilaiSubKomponen($subKomponen, $opdId, $roleId)
    {
        $totalNilai = 0;

        foreach ($subKomponen->kriteria_komponen as $kriteria) {
            // Gunakan getNilai() dari model - otomatis handle penilaian di kriteria/bukti
            $nilaiKriteria = $kriteria->getNilai($opdId, $roleId);
            $totalNilai += $nilaiKriteria;
        }

        return round($totalNilai, 2);
    }

    private function getKategoriNilai($persentase)
    {
        if ($persentase >= 90) return 'AA (Sangat Memuaskan)';
        if ($persentase >= 80) return 'A (Memuaskan)';
        if ($persentase >= 70) return 'BB (Sangat Baik)';
        if ($persentase >= 60) return 'B (Baik)';
        if ($persentase >= 50) return 'CC (Cukup)';
        if ($persentase >= 30) return 'C (Kurang)';
        return 'D (Sangat Kurang)';
    }

    public function render()
    {
        return view('livewire.dashboard.ekspor-laporan');
    }
}
