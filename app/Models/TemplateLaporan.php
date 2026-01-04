<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TemplateLaporan extends Model
{
    protected $table = 'template_laporan';

    protected $fillable = [
        'nama',
        'konten',
    ];

    protected $casts = [
        'konten' => 'array', // Otomatis convert JSON ke array
    ];

    /**
     * Helper method untuk encode konten dari format Livewire properties
     * Input: ['deskripsi' => [komponenId => [subKomponenId => text]], 'catatan' => [komponenId => [text, ...]], 'rekomendasi' => [...]]
     * Output: JSON string
     */
    public static function encodeKonten($deskripsi, $catatan, $rekomendasi)
    {
        return [
            'deskripsi' => $deskripsi,
            'catatan' => $catatan,
            'rekomendasi' => $rekomendasi,
        ];
    }

    /**
     * Helper method untuk decode konten ke format Livewire properties
     * Output: ['deskripsi' => [...], 'catatan' => [...], 'rekomendasi' => [...]]
     */
    public function decodeKonten()
    {
        return [
            'deskripsi' => $this->konten['deskripsi'] ?? [],
            'catatan' => $this->konten['catatan'] ?? [],
            'rekomendasi' => $this->konten['rekomendasi'] ?? [],
        ];
    }
}
