<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BuktiDukung extends Model
{
    protected $table = 'bukti_dukung';
    protected $guarded = ['id'];
    protected $appends = ['bobot'];

    public function kriteria_komponen(): BelongsTo
    {
        return $this->belongsTo(KriteriaKomponen::class, 'kriteria_komponen_id');
    }

    public function sub_komponen(): BelongsTo
    {
        return $this->belongsTo(SubKomponen::class, 'sub_komponen_id');
    }

    public function komponen(): BelongsTo
    {
        return $this->belongsTo(Komponen::class, 'komponen_id');
    }

    public function tahun(): BelongsTo
    {
        return $this->belongsTo(Tahun::class, 'tahun_id');
    }

    public function file_bukti_dukung(): HasMany
    {
        return $this->hasMany(FileBuktiDukung::class, 'bukti_dukung_id');
    }

    protected function bobot(): Attribute
    {
        return Attribute::make(
            get: function () {
                // Check if kriteria_komponen relationship is loaded
                if (!$this->relationLoaded('kriteria_komponen') || !$this->kriteria_komponen) {
                    return 0;
                }

                // Check if bukti_dukung_count exists
                if (
                    !isset($this->kriteria_komponen->bukti_dukung_count) ||
                    $this->kriteria_komponen->bukti_dukung_count == 0
                ) {
                    return 0;
                }

                // Get bobot from kriteria komponen and divide by count
                $kriteriaBobot = $this->kriteria_komponen->bobot;
                return round($kriteriaBobot / $this->kriteria_komponen->bukti_dukung_count, 2);
            }
        );
    }
}
