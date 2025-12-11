<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubKomponen extends Model
{
    protected $table = 'sub_komponen';
    protected $guarded = ['id'];

    protected $casts = [
        'bobot' => 'decimal:2',
    ];

    public function komponen(): BelongsTo
    {
        return $this->belongsTo(Komponen::class, 'komponen_id');
    }

    public function kriteria_komponen(): HasMany
    {
        return $this->hasMany(KriteriaKomponen::class, 'sub_komponen_id');
    }

    public function bukti_dukung(): HasMany
    {
        return $this->hasMany(BuktiDukung::class, 'sub_komponen_id');
    }
}
