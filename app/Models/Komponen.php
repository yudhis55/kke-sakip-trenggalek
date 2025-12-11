<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Komponen extends Model
{
    protected $table = 'komponen';
    protected $guarded = ['id'];

    protected $casts = [
        'bobot' => 'decimal:2',
    ];

    public function tahun(): BelongsTo
    {
        return $this->belongsTo(Tahun::class, 'tahun_id');
    }

    public function sub_komponen(): HasMany
    {
        return $this->hasMany(SubKomponen::class, 'komponen_id');
    }

    public function kriteria_komponen(): HasMany
    {
        return $this->hasMany(KriteriaKomponen::class, 'komponen_id');
    }

    public function bukti_dukung(): HasMany
    {
        return $this->hasMany(BuktiDukung::class, 'komponen_id');
    }
}
