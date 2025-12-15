<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MappingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tahunRows = DB::table('tahun')->get(['id', 'tahun']);
        if ($tahunRows->isEmpty()) {
            $this->command->info("Tabel tahun kosong. Masukkan data tahun dulu. Seeder dibatalkan.");
            return;
        }

        // Mapping role_id untuk setiap komponen
        $roleMapping = [
            'AREN01' => 2, // Perencanaan Kinerja
            'BKUR01' => 2, // Pengukuran Kinerja
            'CLAP01' => 3, // Pelaporan Kinerja
            'DVAL01' => 4, // Evaluasi Akuntabilitas Kinerja Internal
        ];

        // Nested array (dibuat dari data Anda). children = sub_komponen -> children = kriteria (with bukti array)
        $data = [
            [
                'kode' => 'AREN01',
                'nama' => 'Perencanaan Kinerja',
                'bobot' => '30.00%',
                'children' => [
                    [
                        'kode' => 'AREN001',
                        'nama' => 'Pemenuhan',
                        'bobot' => '6.00%',
                        'children' => [
                            [
                                'kode' => 'AREN001.1',
                                'nama' => 'Terdapat dokumen perencanaan kinerja jangka menengah',
                                'bukti' => [
                                    'Terdapat dokumen perencanaan kinerja jangka menengah',
                                    'RPJMD',
                                    'Renstra PD',
                                    'Pokin PD',
                                ],
                            ],
                            [
                                'kode' => 'AREN001.2',
                                'nama' => 'Terdapat dokumen perencanaan kinerja jangka pendek',
                                'bukti' => [
                                    'Terdapat dokumen perencanaan kinerja jangka pendek',
                                    'Renja Tahun n-1',
                                    'Renja Tahun n',
                                    'IKU',
                                    'PK seluruh pegawai tahun n (termasuk PK Perubahan terakhir)',
                                ],
                            ],
                            [
                                'kode' => 'AREN001.3',
                                'nama' => 'Terdapat dokumen perencanaan aktivitas yang mendukung kinerja',
                                'bukti' => [
                                    'Terdapat dokumen perencanaan aktivitas yang mendukung kinerja',
                                    'Rencana Aksi tahun n',
                                    'Dialog Kinerja (Peran & Hasil)',
                                    'DPA tahun n',
                                    'Cascading Kinerja',
                                    'Croscutting (Jika Ada)',
                                ],
                            ],
                            [
                                'kode' => 'AREN001.4',
                                'nama' => 'Terdapat dokumen perencanaan anggaran yang mendukung kinerja',
                                'bukti' => [
                                    'Terdapat dokumen perencanaan anggaran yang mendukung kinerja',
                                    'DPA tahun n',
                                    'KAK',
                                    'RAB (Jika Ada)',
                                ],
                            ],
                        ],
                    ],

                    [
                        'kode' => 'AREN002',
                        'nama' => 'Kualitas',
                        'bobot' => '9.00%',
                        'children' => [
                            [
                                'kode' => 'AREN002.1',
                                'nama' => 'Dokumen Perencanaan Kinerja telah diformalkan.',
                                'bukti' => [
                                    'Dokumen Perencanaan Kinerja telah diformalkan.',
                                    'Renstra',
                                    'Renja',
                                    'Pokin',
                                    'IKU',
                                ],
                            ],
                            [
                                'kode' => 'AREN002.2',
                                'nama' => 'Dokumen Perencanaan Kinerja telah dipublikasikan tepat waktu.',
                                'bukti' => [
                                    'Dokumen Perencanaan Kinerja telah dipublikasikan tepat waktu.',
                                    'Renstra',
                                    'Renja',
                                    'Pokin',
                                    'IKU',
                                ],
                            ],
                            [
                                'kode' => 'AREN002.3',
                                'nama' => 'Dokumen Perencanaan Kinerja telah menggambarkan Kebutuhan atas Kinerja sebenarnya yang perlu dicapai.',
                                'bukti' => [
                                    'Dokumen Perencanaan Kinerja telah menggambarkan Kebutuhan atas Kinerja sebenarnya yang perlu dicapai.',
                                    'Renja',
                                    'PK',
                                    'IKU',
                                    'Dialog Kinerja (Peran Hasil)',
                                    'Renaksi',
                                ],
                            ],
                            [
                                'kode' => 'AREN002.5',
                                'nama' => 'Ukuran Keberhasilan (Indikator Kinerja) telah memenuhi kriteria SMART.',
                                'bukti' => [
                                    'Ukuran Keberhasilan (Indikator Kinerja) telah memenuhi kriteria SMART.',
                                    'Pokin',
                                    'IKU Tahun n',
                                ],
                            ],
                            [
                                'kode' => 'AREN002.6',
                                'nama' => 'Indikator Kinerja Utama (IKU) ... tertuang secara berkelanjutan',
                                'bukti' => [
                                    'Indikator Kinerja Utama (IKU) telah menggambarkan kondisi Kinerja Utama ...',
                                    'IKU Tahun n-1',
                                    'IKU Tahun n',
                                ],
                            ],
                            [
                                'kode' => 'AREN002.7',
                                'nama' => 'Target yang ditetapkan dapat dicapai (achievable).',
                                'bukti' => [
                                    'Target yang ditetapkan ...',
                                    'PK Tahun n-1',
                                    'PK Tahun n',
                                    'LKJiP Tahun n-1',
                                    'Renaksi Tahun n-1',
                                    'Renaksi Tahun n',
                                ],
                            ],
                            [
                                'kode' => 'AREN002.8',
                                'nama' => 'Dokumen Perencanaan Kinerja menggambarkan hubungan yang berkesinambungan (Cascading).',
                                'bukti' => [
                                    'PK Seluruh Pegawai Tahun n',
                                    'Cascading',
                                    'Probis (Proses Bisnis)',
                                ],
                            ],
                            [
                                'kode' => 'AREN002.9',
                                'nama' => 'Perencanaan kinerja dapat memberikan informasi ... Crosscutting',
                                'bukti' => [
                                    'Matrik RPJMD',
                                    'Lampiran Crosscutting',
                                ],
                            ],
                            [
                                'kode' => 'AREN002.10',
                                'nama' => 'Setiap unit/satuan kerja merumuskan dan menetapkan Perencanaan Kinerja.',
                                'bukti' => [
                                    'PK Tahun n',
                                    'SKP Tahun n',
                                    'Dialog Kinerja (Matrik Peran Hasil)',
                                ],
                            ],
                        ],
                    ],

                    [
                        'kode' => 'AREN003',
                        'nama' => 'Pemanfaatan',
                        'bobot' => '15.00%',
                        'children' => [
                            [
                                'kode' => 'AREN003.1',
                                'nama' => 'Anggaran yang ditetapkan telah mengacu pada dokumen perencanaan perangkat daerah.',
                                'bukti' => [
                                    'Anggaran yang ditetapkan telah mengacu pada dokumen perencanaan perangkat daerah.',
                                    'Renstra',
                                    'Renja Tahun n',
                                    'Perjanjian Kinerja JPT/Kepala Perangkat Daerah (Eselon II) Tahun n',
                                    'DPA Tahun n',
                                ],
                            ],
                            [
                                'kode' => 'AREN003.2',
                                'nama' => 'Aktivitas yang dilaksanakan telah mendukung Kinerja yang ingin dicapai.',
                                'bukti' => [
                                    'Perjanjian Kinerja Berjenjang (Unsur pimpinan s.d. staf) Tahun n',
                                    'Rencana Aksi (aktivitas yang akan dilakukan) Tahun n',
                                ],
                            ],
                            [
                                'kode' => 'AREN003.3',
                                'nama' => 'Target kinerja yang diperjanjikan ... on the track.',
                                'bukti' => [
                                    'Perjanjian Kinerja berjenjang Tahun n',
                                    'Evaluasi rencana aksi secara berjenjang',
                                ],
                            ],
                            [
                                'kode' => 'AREN003.4',
                                'nama' => 'Rencana aksi kinerja dipantau secara berkala.',
                                'bukti' => [
                                    'Form E.81',
                                    'Laporan Monev Rencana Aksi',
                                ],
                            ],
                            [
                                'kode' => 'AREN003.5',
                                'nama' => 'Perbaikan/penyempurnaan Dokumen Perencanaan Kinerja dari analisis perbaikan sebelumnya.',
                                'bukti' => [
                                    'Perbaikan/penyempurnaan Dokumen Perencanaan Kinerja'
                                ],
                            ],
                            [
                                'kode' => 'AREN003.6',
                                'nama' => 'Setiap unit/satuan kerja memahami dan peduli serta berkomitmen.',
                                'bukti' => [
                                    'Setiap unit/satuan kerja memahami dan peduli serta berkomitmen'
                                ],
                            ],
                            [
                                'kode' => 'AREN003.7',
                                'nama' => 'Setiap Pegawai memahami dan peduli serta berkomitmen.',
                                'bukti' => [
                                    'Setiap Pegawai memahami dan peduli serta berkomitmen'
                                ],
                            ],
                        ],
                    ],
                ],
            ],

            // ---------- BKUR (Pengukuran Kinerja) ----------
            [
                'kode' => 'BKUR01', // top code chosen to group sub items
                'nama' => 'Pengukuran Kinerja',
                'bobot' => '30.00%',
                'children' => [
                    [
                        'kode' => 'BKUR001',
                        'nama' => 'Pemenuhan',
                        'bobot' => '6.00%',
                        'children' => [
                            [
                                'kode' => 'BKUR001.1',
                                'nama' => 'Terdapat pedoman teknis pengukuran kinerja dan pengumpulan data kinerja',
                                'bukti' => [
                                    'Terdapat pedoman teknis pengukuran kinerja dan pengumpulan data kinerja',
                                    'Renstra',
                                ],
                            ],
                            [
                                'kode' => 'BKUR001.2',
                                'nama' => 'Terdapat Definisi Operasional yang jelas atas kinerja dan cara mengukur indikator kinerja.',
                                'bukti' => [
                                    'Terdapat Definisi Operasional yang jelas atas kinerja dan cara mengukur indikator kinerja.',
                                    'IKU',
                                    'Manual IKU',
                                ],
                            ],
                            [
                                'kode' => 'BKUR001.3',
                                'nama' => 'Terdapat mekanisme pengumpulan data kinerja yang dapat diandalkan.',
                                'bukti' => [
                                    'Terdapat mekanisme yang jelas terhadap pengumpulan data kinerja yang dapat diandalkan.',
                                    'SOP Pengumpulan Data Kinerja',
                                ],
                            ],
                        ],
                    ],

                    [
                        'kode' => 'BKUR002',
                        'nama' => 'Kualitas',
                        'bobot' => '9.00%',
                        'children' => [
                            [
                                'kode' => 'BKUR002.1',
                                'nama' => 'Pimpinan selalu terlibat sebagai pengambil keputusan',
                                'bukti' => [
                                    'Laporan Capaian Kinerja Bulanan (Organisasi) /Dialog Kinerja evaluasi renaksi yang memuat capaian',
                                    'SKP seluruh pegawai (periode Tahun n-1 s.d Tahun n)',
                                ],
                            ],
                            [
                                'kode' => 'BKUR002.2',
                                'nama' => 'Data kinerja relevan untuk mengukur capaian kinerja',
                                'bukti' => [
                                    'dokumen evaluasi yang dilaksanakan secara berjenjang berdasarkan renaksi yang telah ditetapkan',
                                ],
                            ],
                            [
                                'kode' => 'BKUR002.3',
                                'nama' => 'Data kinerja mendukung capaian kinerja',
                                'bukti' => [
                                    'Berita Acara Evaluasi Renja per Triwulan (dari Bappedalitbang)',
                                    'Tindaklanjut Hasil Evaluasi Renja per Triwulan',
                                ],
                            ],
                            [
                                'kode' => 'BKUR002.4',
                                'nama' => 'Pengukuran kinerja telah dilakukan secara berkala',
                                'bukti' => [
                                    'Berita Acara Evaluasi Renja per Triwulan (dari Bappedalitbang)',
                                    'Dialog Kinerja secara berkala (bulanan/triwulanan/semester)',
                                ],
                            ],
                            [
                                'kode' => 'BKUR002.5',
                                'nama' => 'Pemantauan pengukuran capaian kinerja berjenjang',
                                'bukti' => [
                                    'Laporan capaian Kinerja Triwulanan',
                                    'SKP seluruh pegawai (periode Tahun n-1 s.d Tahun n)',
                                ],
                            ],
                            [
                                'kode' => 'BKUR002.6',
                                'nama' => 'Pengumpulan data memanfaatkan TI (Aplikasi).',
                                'bukti' => [
                                    'Screenshot Aplikasi',
                                ],
                            ],
                            [
                                'kode' => 'BKUR002.7',
                                'nama' => 'Pengukuran memanfaatkan TI (Aplikasi).',
                                'bukti' => [
                                    'Screenshot Aplikasi',
                                ],
                            ],
                        ],
                    ],

                    [
                        'kode' => 'BKUR003',
                        'nama' => 'Pemanfaatan',
                        'bobot' => '15.00%',
                        'children' => [
                            [
                                'kode' => 'BKUR003.1',
                                'nama' => 'Pengukuran Kinerja menjadi dasar penyesuaian tunjangan/penghasilan.',
                                'bukti' => [
                                    'SKP',
                                    'Rekapitulasi Kinerja ASN (Daftar Nominatif)',
                                ],
                            ],
                            [
                                'kode' => 'BKUR003.2',
                                'nama' => 'Pengukuran mempengaruhi penyesuaian organisasi (Refocusing).',
                                'bukti' => [
                                    'Evaluasi Kinerja Semester I',
                                    'DPA Perubahan',
                                ],
                            ],
                            [
                                'kode' => 'BKUR003.3',
                                'nama' => 'Pengukuran mempengaruhi penyesuaian strategi.',
                                'bukti' => [
                                    'Evaluasi Kinerja Semester I',
                                    'Berita Acara/ Hasil Dialog Kinerja secara berjenjang',
                                ],
                            ],
                            [
                                'kode' => 'BKUR003.4',
                                'nama' => 'Pengukuran mempengaruhi penyesuaian kebijakan.',
                                'bukti' => [
                                    'Evaluasi Kinerja Semester I',
                                    'Berita Acara/ Hasil Dialog Kinerja secara berjenjang',
                                ],
                            ],
                            [
                                'kode' => 'BKUR003.5',
                                'nama' => 'Pengukuran mempengaruhi penyesuaian aktivitas.',
                                'bukti' => [
                                    'Evaluasi Kinerja Semester I',
                                ],
                            ],
                            [
                                'kode' => 'BKUR003.6',
                                'nama' => 'Pengukuran mempengaruhi penyesuaian anggaran.',
                                'bukti' => [
                                    'LKjIP Tahun n-1',
                                    'Realisasi Kinerja Semester I Tahun n',
                                    'DPA Perubahan',
                                ],
                            ],
                            [
                                'kode' => 'BKUR003.7',
                                'nama' => 'Terdapat efisiensi atas penggunaan anggaran.',
                                'bukti' => [
                                    'LKjIP Tahun n-1 BAB III (yang menguraikan catatan adanya efisiensi)',
                                    'Realisasi Kinerja Semester I Tahun n yang menguraikan catatan adanya efisiensi',
                                ],
                            ],
                            [
                                'kode' => 'BKUR003.8',
                                'nama' => 'Unit kerja memahami dan peduli atas hasil pengukuran kinerja.',
                                'bukti' => [
                                    'Tindak Lanjut Evaluasi (SKP)',
                                    'Tindak Lanjut atas rekomendasi Dialog Kinerja secara berjenjang',
                                ],
                            ],
                            [
                                'kode' => 'BKUR003.9',
                                'nama' => 'Pegawai memahami dan peduli atas hasil pengukuran kinerja.',
                                'bukti' => [
                                    'Tindak Lanjut Evaluasi (SKP)',
                                ],
                            ],
                        ],
                    ],
                ],
            ],

            // ---------- CLAP (Pelaporan Kinerja) ----------
            [
                'kode' => 'CLAP01',
                'nama' => 'Pelaporan Kinerja',
                'bobot' => '15.00%',
                'children' => [
                    [
                        'kode' => 'CLAP001',
                        'nama' => 'Pemenuhan',
                        'bobot' => '3.00%',
                        'children' => [
                            [
                                'kode' => 'CLAP001.1',
                                'nama' => 'Dokumen Laporan Kinerja telah disusun.',
                                'bukti' => [
                                    'Dokumen Laporan Kinerja telah disusun.',
                                    'LKjIP Tahun n-1',
                                ],
                            ],
                            [
                                'kode' => 'CLAP001.2',
                                'nama' => 'Dokumen Laporan Kinerja disusun berkala.',
                                'bukti' => [
                                    'Dokumen Laporan Kinerja telah disusun secara berkala.',
                                    'LKjIP Tahun n-1',
                                    'Laporan Capaian Kinerja Triwulan (TW I - TW IV Tahun n-1)',
                                    'Dokumen Laporan Kinerja Semester 1 dan 2 Tahun n',
                                ],
                            ],
                            [
                                'kode' => 'CLAP001.3',
                                'nama' => 'Dokumen Laporan Kinerja telah diformalkan.',
                                'bukti' => [
                                    'LKjIP Tahun n-1',
                                ],
                            ],
                            [
                                'kode' => 'CLAP001.4',
                                'nama' => 'Dokumen Laporan Kinerja telah direviu.',
                                'bukti' => [
                                    'Bukti Evaluasi secara berkala',
                                    'LHR LKjIP',
                                ],
                            ],
                            [
                                'kode' => 'CLAP001.5',
                                'nama' => 'Dokumen Laporan Kinerja telah dipublikasikan.',
                                'bukti' => [
                                    'Screenshot LKjIP pada websites e-SAKIP',
                                    'Screenshot LKjIP pada websites e-SAKIP Review KEMENPAN RB',
                                ],
                            ],
                            [
                                'kode' => 'CLAP001.6',
                                'nama' => 'Dokumen Laporan Kinerja telah disampaikan tepat waktu.',
                                'bukti' => [
                                    'Screenshot LKjIP telah diunggah pada websites e-SAKIP maksimal 28 Februari',
                                ],
                            ],
                        ],
                    ],

                    [
                        'kode' => 'CLAP002',
                        'nama' => 'Kualitas',
                        'bobot' => '4.50%',
                        'children' => [
                            [
                                'kode' => 'CLAP002.1',
                                'nama' => 'Dokumen Laporan Kinerja disusun secara berkualitas sesuai standar.',
                                'bukti' => ['LKjIP'],
                            ],
                            [
                                'kode' => 'CLAP002.2',
                                'nama' => 'Dokumen Laporan Kinerja mengungkap seluruh informasi pencapaian kinerja.',
                                'bukti' => ['PK', 'LKjIP'],
                            ],
                            [
                                'kode' => 'CLAP002.3',
                                'nama' => 'Dokumen Laporan Kinerja menginformasikan analisis dan evaluasi realisasi kinerja dengan target tahunan.',
                                'bukti' => ['LKjIP', 'PK', 'Renja'],
                            ],
                            [
                                'kode' => 'CLAP002.4',
                                'nama' => 'Dokumen Laporan Kinerja menginformasikan analisis realisasi kinerja jangka menengah.',
                                'bukti' => ['LKjIP', 'Renstra/IKU'],
                            ],
                            [
                                'kode' => 'CLAP002.5',
                                'nama' => 'Dokumen Laporan Kinerja menginformasikan realisasi tahun-tahun sebelumnya.',
                                'bukti' => ['LKjIP'],
                            ],
                            [
                                'kode' => 'CLAP002.6',
                                'nama' => 'Dokumen Laporan Kinerja menginformasikan benchmark (nasional/internasional).',
                                'bukti' => ['LKjIP'],
                            ],
                            [
                                'kode' => 'CLAP002.7',
                                'nama' => 'Dokumen Laporan Kinerja menginformasikan kualitas keberhasilan/kegagalan.',
                                'bukti' => ['LKjIP'],
                            ],
                            [
                                'kode' => 'CLAP002.8',
                                'nama' => 'Dokumen Laporan Kinerja menginformasikan efisiensi penggunaan sumber daya.',
                                'bukti' => ['LKjIP (Efisiensi)'],
                            ],
                            [
                                'kode' => 'CLAP002.9',
                                'nama' => 'Dokumen Laporan Kinerja menginformasikan rekomendasi perbaikan kinerja.',
                                'bukti' => ['LKjIP'],
                            ],
                        ],
                    ],

                    [
                        'kode' => 'CLAP003',
                        'nama' => 'Pemanfaatan',
                        'bobot' => '7.50%',
                        'children' => [
                            [
                                'kode' => 'CLAP003.1',
                                'nama' => 'Informasi dalam laporan kinerja menjadi perhatian utama pimpinan.',
                                'bukti' => [
                                    'Dokumen bukti kegiatan Evaluasi Kinerja (FGD, Rapat Koordinasi, Presensi, Foto, Notulensi)'
                                ],
                            ],
                            [
                                'kode' => 'CLAP003.2',
                                'nama' => 'Penyajian informasi menjadi kepedulian seluruh pegawai.',
                                'bukti' => ['Tindak Lanjut Hasil Rekomendasi dari Evaluasi Kinerja'],
                            ],
                            [
                                'kode' => 'CLAP003.3',
                                'nama' => 'Informasi digunakan untuk penyesuaian aktivitas.',
                                'bukti' => ['DPA Perubahan (aktivitas kinerja)'],
                            ],
                            [
                                'kode' => 'CLAP003.4',
                                'nama' => 'Informasi digunakan untuk penyesuaian anggaran.',
                                'bukti' => ['DPA Perubahan (anggaran)'],
                            ],
                            [
                                'kode' => 'CLAP003.5',
                                'nama' => 'Informasi digunakan dalam evaluasi pencapaian keberhasilan kinerja.',
                                'bukti' => ['Dokumen Evaluasi Kinerja (Tribulan/Semester/Tahunan)'],
                            ],
                            [
                                'kode' => 'CLAP003.6',
                                'nama' => 'Informasi digunakan untuk penyesuaian perencanaan kinerja berikutnya.',
                                'bukti' => ['DPA Induk', 'DPA Perubahan'],
                            ],
                            [
                                'kode' => 'CLAP003.7',
                                'nama' => 'Informasi mempengaruhi perubahan budaya kinerja organisasi.',
                                'bukti' => ['SK Budaya Kinerja', 'Implementasi budaya kerja di lingkungan Perangkat Daerah'],
                            ],
                        ],
                    ],
                ],
            ],

            // ---------- DVAL (Evaluasi Akuntabilitas Kinerja Internal) ----------
            [
                'kode' => 'DVAL01',
                'nama' => 'Evaluasi Akuntabilitas Kinerja Internal',
                'bobot' => '25.00%',
                'children' => [
                    [
                        'kode' => 'DVAL001',
                        'nama' => 'Pemenuhan',
                        'bobot' => '5.00%',
                        'children' => [
                            [
                                'kode' => 'DVAL001.1',
                                'nama' => 'Evaluasi AKIP Internal telah dilaksanakan pada seluruh unit kerja.',
                                'bukti' => [
                                    'Evaluasi AKIP Internal telah dilaksanakan pada seluruh unit kerja/ perangkat daerah',
                                    'LHE AKIP seluruh OPD',
                                    'Rekapitulasi Nilai AKIP seluruh OPD',
                                    'TL LHE AKIP seluruh OPD',
                                ],
                            ],
                            [
                                'kode' => 'DVAL001.2',
                                'nama' => 'Evaluasi AKIP Internal telah dilaksanakan secara berjenjang',
                                'bukti' => [
                                    'Rekapitulasi Jumlah Data Karyawan tiap Perangkat Daerah',
                                    'Rekapitulasi SKP yang tidak dinilai tiap Perangkat Daerah',
                                    'SK Tim Evaluasi Internal Perangkat Daerah',
                                ],
                            ],
                        ],
                    ],

                    [
                        'kode' => 'DVAL002',
                        'nama' => 'Kualitas',
                        'bobot' => '7.50%',
                        'children' => [
                            [
                                'kode' => 'DVAL002.1',
                                'nama' => 'Evaluasi AKIP telah dilaksanakan secara berjenjang',
                                'bukti' => ['Berita Acara evaluasi kinerja secara berjenjang mulai dari unsur staf s.d pimpinan.'],
                            ],
                            [
                                'kode' => 'DVAL002.2',
                                'nama' => 'Evaluasi AKIP dilaksanakan dengan pendalaman yang memadai.',
                                'bukti' => [
                                    'LKE hasil dari Penilaian Mandiri oleh Perangkat Daerah',
                                    'Catatan dan Rekomendasi oleh APIP telah ditindaklanjuti',
                                ],
                            ],
                            [
                                'kode' => 'DVAL002.3',
                                'nama' => 'Evaluasi AKIP sesuai pedoman teknis yang berlaku.',
                                'bukti' => ['LKE dan LHE AKIP Tahun n.'],
                            ],
                            [
                                'kode' => 'DVAL002.4',
                                'nama' => 'Evaluasi AKIP menggunakan Teknologi Informasi (Aplikasi).',
                                'bukti' => [
                                    'Screenshot Aplikasi SIMONEV Kab. Trenggalek',
                                    'Screenshot Aplikasi E-SAKIP REVIU KEMENPAN RB',
                                ],
                            ],
                        ],
                    ],

                    [
                        'kode' => 'DVAL003',
                        'nama' => 'Pemanfaatan',
                        'bobot' => '5.00%',
                        'children' => [
                            [
                                'kode' => 'DVAL003.1',
                                'nama' => 'Seluruh rekomendasi atas hasil evaluasi telah ditindaklanjuti.',
                                'bukti' => [
                                    'LHE SAKIP Tahun n-1',
                                    'Matriks Tindak Lanjut LHE SAKIP (Format: Rekomendasi-RATL-PJ-Status/Progres Penyelesaian)',
                                ],
                            ],
                            [
                                'kode' => 'DVAL003.2',
                                'nama' => 'Peningkatan implementasi SAKIP melalui tindak lanjut rekomendasi.',
                                'bukti' => [
                                    'Dokumen hasil evaluasi AKIP tahun n-1 dan tahun n',
                                    'Bukti Tindaklanjut Rekomendasi Hasil Evaluasi AKIP',
                                ],
                            ],
                            [
                                'kode' => 'DVAL003.3',
                                'nama' => 'Hasil Evaluasi AKIP dimanfaatkan untuk perbaikan.',
                                'bukti' => ['Nilai Akip N-2 dan N-1 (Tahun 2024 dan Tahun 2025)'],
                            ],
                            [
                                'kode' => 'DVAL003.4',
                                'nama' => 'Hasil Evaluasi AKIP mendukung efektivitas dan efisiensi kinerja.',
                                'bukti' => [
                                    'Realisasi Kinerja n-1 BAB III',
                                    'Laporan Kinerja Tahun n Semester 1',
                                ],
                            ],
                            [
                                'kode' => 'DVAL003.5',
                                'nama' => 'Telah terjadi perbaikan dan peningkatan kinerja menggunakan hasil evaluasi.',
                                'bukti' => ['Bukti dokumen nilai evaluasi tahun n-1 dan tahun n'],
                            ],
                        ],
                    ],

                    [
                        'kode' => 'DVAL004',
                        'nama' => 'Capaian',
                        'bobot' => '7.50%',
                        'children' => [
                            [
                                'kode' => 'DVAL004.1',
                                'nama' => 'Target dapat dicapai',
                                'bukti' => ['Dokumen LKjIP (perbandingan target kinerja tahun n-1 dengan tahun n)'],
                            ],
                            [
                                'kode' => 'DVAL004.2',
                                'nama' => 'Capaian kinerja lebih baik dari tahun sebelumnya',
                                'bukti' => ['Dokumen LKjIP perbandingan target kinerja tahun n-1 dengan tahun n'],
                            ],
                            [
                                'kode' => 'DVAL004.3',
                                'nama' => 'Informasi mengenai kinerja dapat diandalkan',
                                'bukti' => [
                                    'LKjiP memuat target dan realisasi kinerja disertai analisa formulasi perhitungan capaian kinerja',
                                    'Laporan Kinerja Tahun n Semester I (BAB III)',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        // Loop per tahun -> masukkan data
        foreach ($tahunRows as $tahunRow) {
            $tahun_id = $tahunRow->id;
            $this->command->info("Memproses tahun: {$tahunRow->tahun} (id={$tahun_id})...");

            foreach ($data as $kom) {
                // skip if komponen already exists for this year
                $existingKom = DB::table('komponen')
                    ->where('kode', $kom['kode'])
                    ->where('tahun_id', $tahun_id)
                    ->first();

                if ($existingKom) {
                    $komponenId = $existingKom->id;
                } else {
                    $komponenId = DB::table('komponen')->insertGetId([
                        'kode' => $kom['kode'],
                        'nama' => $kom['nama'],
                        'bobot' => $this->parseBobot($kom['bobot'] ?? null),
                        'tahun_id' => $tahun_id,
                        'role_id' => $roleMapping[$kom['kode']] ?? null,
                    ]);
                }

                // sub komponen
                if (!empty($kom['children'])) {
                    foreach ($kom['children'] as $sub) {
                        $existingSub = DB::table('sub_komponen')
                            ->where('kode', $sub['kode'])
                            ->where('tahun_id', $tahun_id)
                            ->where('komponen_id', $komponenId)
                            ->first();

                        if ($existingSub) {
                            $subId = $existingSub->id;
                        } else {
                            $subId = DB::table('sub_komponen')->insertGetId([
                                'kode' => $sub['kode'],
                                'nama' => $sub['nama'] ?? '—',
                                'bobot' => $this->parseBobot($sub['bobot'] ?? null),
                                'komponen_id' => $komponenId,
                                'tahun_id' => $tahun_id,
                            ]);
                        }

                        // kriteria
                        if (!empty($sub['children'])) {
                            foreach ($sub['children'] as $krit) {
                                $existingK = DB::table('kriteria_komponen')
                                    ->where('kode', $krit['kode'])
                                    ->where('tahun_id', $tahun_id)
                                    ->where('sub_komponen_id', $subId)
                                    ->first();

                                if ($existingK) {
                                    $kriteriaId = $existingK->id;
                                } else {
                                    $kriteriaId = DB::table('kriteria_komponen')->insertGetId([
                                        'kode' => $krit['kode'],
                                        'nama' => $krit['nama'] ?? '—',
                                        'sub_komponen_id' => $subId,
                                        'komponen_id' => $komponenId,
                                        'jenis_nilai_id' => 1,
                                        'tahun_id' => $tahun_id,
                                    ]);
                                }

                                // bukti dukung
                                if (!empty($krit['bukti']) && is_array($krit['bukti'])) {
                                    foreach ($krit['bukti'] as $buk) {
                                        // avoid exact duplicate bukti for same kriteria
                                        $existsB = DB::table('bukti_dukung')
                                            ->where('nama', $buk)
                                            ->where('kriteria_komponen_id', $kriteriaId)
                                            ->where('tahun_id', $tahun_id)
                                            ->first();
                                        if (!$existsB) {
                                            DB::table('bukti_dukung')->insert([
                                                'nama' => $buk,
                                                'kriteria_komponen_id' => $kriteriaId,
                                                'sub_komponen_id' => $subId,
                                                'komponen_id' => $komponenId,
                                                'tahun_id' => $tahun_id,
                                            ]);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            } // end foreach komponen
        } // end foreach tahun

        $this->command->info("Selesai seed data nested SAKIP.");
    }

    private function parseBobot($s)
    {
        if (is_null($s)) return 0;
        $s = trim((string)$s);
        if ($s === '') return 0;
        $s = str_replace('%', '', $s);
        $s = str_replace(',', '.', $s);
        return (float) $s;
    }

    private $jenisNilaiMapping = [
        "Terdapat dokumen perencanaan kinerja jangka menengah" => 2,
        "Terdapat dokumen perencanaan kinerja jangka pendek" => 2,
        "Terdapat dokumen perencanaan aktivitas yang mendukung kinerja" => 2,
        "Terdapat dokumen perencanaan anggaran yang mendukung kinerja" => 2,
        "Dokumen Perencanaan Kinerja telah diformalkan." => 2,
        "Dokumen Perencanaan Kinerja telah dipublikasikan tepat waktu." => 2,
        "Dokumen Perencanaan Kinerja telah menggambarkan Kebutuhan atas Kinerja sebenarnya yang perlu dicapai." => 1,
        "Kualitas Rumusan Hasil (Tujuan/Sasaran) telah jelas menggambarkan kondisi kinerja yang akan dicapai." => 1,
        "Ukuran Keberhasilan (Indikator Kinerja) telah memenuhi kriteria SMART." => 1,
        "Indikator Kinerja Utama (IKU) telah menggambarkan kondisi Kinerja Utama yang harus dicapai, tertuang secara berkelanjutan (sustainable - tidak sering diganti dalam 1 periode Perencanaan Strategis)." => 1,
        "Target yang ditetapkan dalam Perencanaan Kinerja dapat dicapai (achievable), menantang, dan realistis." => 1,
        "Setiap Dokumen Perencanaan Kinerja menggambarkan hubungan yang berkesinambungan, serta selaras antara Kondisi/Hasil yang akan dicapai di setiap level jabatan (Cascading)." => 1,
        "Perencanaan kinerja dapat memberikan informasi tentang hubungan kinerja antar perangkat daerah (Crosscutting)" => 1,
        "Setiap unit/satuan kerja merumuskan dan menetapkan Perencanaan Kinerja." => 1,
        "Anggaran yang ditetapkan telah mengacu pada dokumen perencanaan perangkat daerah." => 1,
        "Aktivitas yang dilaksanakan telah mendukung Kinerja yang ingin dicapai." => 1,
        "Target kinerja yang diperjanjikan pada Perjanjian Kinerja telah dicapai dengan baik, atau setidaknya masih on the track." => 1,
        "Rencana aksi kinerja dapat berjalan dinamis karena capaian kinerja selalu dipantau secara berkala." => 1,
        "Terdapat perbaikan/penyempurnaan Dokumen Perencanaan Kinerja yang ditetapkan dari hasil analisis perbaikan kinerja sebelumnya." => 1,
        "Setiap unit/satuan kerja memahami dan peduli serta berkomitmen dalam mencapai kinerja yang telah direncanakan." => 1,
        "Setiap Pegawai memahami dan peduli serta berkomitmen dalam mencapai kinerja yang telah direncanakan." => 1,
        "Terdapat pedoman teknis pengukuran kinerja dan pengumpulan data kinerja" => 2,
        "Terdapat Definisi Operasional yang jelas atas kinerja dan cara mengukur indikator kinerja." => 2,
        "Terdapat mekanisme yang jelas terhadap pengumpulan data kinerja yang dapat diandalkan." => 2,
        "Pimpinan selalu teribat sebagai pengambil keputusan (Decision Maker) dalam mengukur capaian kinerja." => 1,
        "Data kinerja yang dikumpulkan telah relevan untuk mengukur capaian kinerja yang diharapkan." => 1,
        "Data kinerja yang dikumpulkan telah mendukung capaian kinerja yang diharapkan." => 1,
        "Pengukuran kinerja telah dilakukan secara berkala." => 2,
        "Setiap level organisasi melakukan pemantauan atas pengukuran capaian kinerja unit dibawahnya secara berjenjang." => 1,
        "Pengumpulan data kinerja telah memanfaatkan Teknologi Informasi (Aplikasi)." => 2,
        "Pengukuran capaian kinerja telah memanfaatkan Teknologi Informasi (Aplikasi)." => 2,
        "Pengukuran Kinerja telah menjadi dasar dalam penyesuaian (pemberian/pengurangan) tunjangan kinerja/penghasilan." => 1,
        "Pengukuran kinerja telah mempengaruhi penyesuaian (Refocusing) Organisasi." => 1,
        "Pengukuran kinerja telah mempengaruhi penyesuaian Strategi dalam mencapai kinerja." => 1,
        "Pengukuran kinerja telah mempengaruhi penyesuaian Kebijakan dalam mencapai kinerja." => 1,
        "Pengukuran kinerja telah mempengaruhi penyesuaian Aktivitas dalam mencapai kinerja." => 1,
        "Pengukuran kinerja telah mempengaruhi penyesuaian Anggaran dalam mencapai kinerja." => 1,
        "Terdapat efisiensi atas penggunaan anggaran dalam mencapai kinerja." => 1,
        "Setiap unit kerja (Sekretariat/Bidang/Subbidang/Seksi/UPT) memahami dan peduli atas hasil pengukuran kinerja." => 1,
        "Setiap pegawai memahami dan peduli atas hasil pengukuran kinerja." => 1,
        "Dokumen Laporan Kinerja telah disusun." => 2,
        "Dokumen Laporan Kinerja telah disusun secara berkala." => 2,
        "Dokumen Laporan Kinerja telah diformalkan." => 2,
        "Dokumen Laporan Kinerja telah direviu." => 2,
        "Dokumen Laporan Kinerja telah dipublikasikan." => 2,
        "Dokumen Laporan Kinerja telah disampaikan tepat waktu." => 2,
        "Dokumen Laporan Kinerja disusun secara berkualitas sesuai dengan standar." => 1,
        "Dokumen Laporan Kinerja telah mengungkap seluruh informasi tentang pencapaian kinerja." => 1,
        "Dokumen Laporan Kinerja telah menginfokan analisis dan evaluasi realisasi kinerja dengan target tahunan." => 1,
        "Dokumen Laporan Kinerja telah menginfokan analisis dan evaluasi realisasi kinerja dengan target jangka menengah." => 1,
        "Dokumen Laporan Kinerja telah menginfokan analisis dan evaluasi realisasi kinerja dengan realisasi kinerja tahun-tahun sebelumnya." => 1,
        "Dokumen Laporan Kinerja telah menginfokan analisis dan evaluasi realisasi kinerja dengan realiasi kinerja di level nasional/internasional (Benchmark Kinerja)." => 1,
        "Dokumen Laporan Kinerja telah menginfokan kualitas atas keberhasilan/kegagalan mencapai target kinerja beserta upaya nyata dan/atau hambatannya." => 1,
        "Dokumen Laporan Kinerja telah menginfokan efisiensi atas penggunaan sumber daya dalam mencapai kinerja." => 1,
        "Dokumen Laporan Kinerja telah menginfokan upaya perbaikan dan penyempurnaan kinerja ke depan (Rekomendasi perbaikan kinerja)." => 1,
        "Informasi dalam laporan kinerja selalu menjadi perhatian utama pimpinan (Bertanggung Jawab)." => 1,
        "Penyajian informasi dalam laporan kinerja menjadi kepedulian seluruh pegawai." => 1,
        "Informasi dalam laporan kinerja berkala telah digunakan dalam penyesuaian aktivitas untuk mencapai kinerja." => 1,
        "Informasi dalam laporan kinerja berkala telah digunakan dalam penyesuaian penggunaan anggaran untuk mencapai kinerja." => 1,
        "Informasi dalam laporan kinerja telah digunakan dalam evaluasi pencapaian keberhasilan kinerja." => 1,
        "Informasi dalam laporan kinerja telah digunakan dalam penyesuaian perencanaan kinerja yang akan dihadapi berikutnya." => 1,
        "Informasi dalam laporan kinerja selalu mempengaruhi perubahan budaya kinerja organisasi." => 1,
        "Terdapat pedoman teknis Evaluasi AKIP Internal" => 2,
        "Evaluasi AKIP Internal telah dilaksanakan pada seluruh unit kerja/ perangkat daerah" => 2,
        "Evaluasi AKIP Internal telah dilaksanakan secara berjenjang" => 2,
        "Evaluasi Akuntabilitas Kinerja telah dilaksanakan secara berjenjang" => 1,
        "Evaluasi Akuntabilitas Kinerja telah dilaksanakan dengan pendalaman yang memadai." => 1,
        "Evaluasi Akuntabilitas Kinerja telah dilaksanakan sesuai dengan pedoman teknis yang berlaku." => 1,
        "Evaluasi Akuntabilitas Kinerja telah dilaksanakan menggunakan Teknologi Informasi (Aplikasi)." => 1,
        "Seluruh rekomendasi atas hasil evaluasi akuntablitas kinerja telah ditindaklanjuti." => 1,
        "Telah terjadi peningkatan implementasi SAKIP dengan melaksanakan tindak lanjut atas rekomendasi hasil evaluasi akuntabilitas kinerja." => 1,
        "Hasil Evaluasi Akuntabilitas Kinerja telah dimanfaatkan untuk perbaikan dan peningkatan akuntabilitas kinerja." => 1,
        "Hasil dari Evaluasi Akuntabilitas Kinerja telah dimanfaatkan dalam mendukung efektivitas dan efisiensi kinerja." => 1,
        "Telah terjadi perbaikan dan peningkatan kinerja dengan memanfaatkan hasil evaluasi akuntablitas kinerja." => 1,
        "Target dapat dicapai" => 1,
        "Capaian kinerja lebih baik dari tahun sebelumnya" => 1,
        "Informasi mengenai kinerja dapat diandalkan" => 1,
    ];
}
