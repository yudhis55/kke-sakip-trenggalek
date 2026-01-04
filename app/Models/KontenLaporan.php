<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KontenLaporan extends Model
{
    protected $table = 'konten_laporan';

    protected $fillable = [
        'type',
        'komponen_id',
        'sub_komponen_id',
        'opd_id',
        'tahun_id',
        'konten',
        'urutan',
    ];

    /**
     * Relationship: Belongs to Komponen
     */
    public function komponen()
    {
        return $this->belongsTo(Komponen::class);
    }

    /**
     * Relationship: Belongs to SubKomponen
     */
    public function subKomponen()
    {
        return $this->belongsTo(SubKomponen::class);
    }

    /**
     * Relationship: Belongs to OPD
     */
    public function opd()
    {
        return $this->belongsTo(Opd::class);
    }

    /**
     * Relationship: Belongs to Tahun
     */
    public function tahun()
    {
        return $this->belongsTo(Tahun::class);
    }

    /**
     * Scope: Filter by type
     */
    public function scopeType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: Filter by OPD and Tahun
     */
    public function scopeForOpdTahun($query, $opdId, $tahunId)
    {
        return $query->where('opd_id', $opdId)
            ->where('tahun_id', $tahunId);
    }

    /**
     * Scope: Deskripsi
     */
    public function scopeDeskripsi($query)
    {
        return $query->where('type', 'deskripsi');
    }

    /**
     * Scope: Catatan
     */
    public function scopeCatatan($query)
    {
        return $query->where('type', 'catatan')->orderBy('urutan');
    }

    /**
     * Scope: Rekomendasi
     */
    public function scopeRekomendasi($query)
    {
        return $query->where('type', 'rekomendasi')->orderBy('urutan');
    }
}
