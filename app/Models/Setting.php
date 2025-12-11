<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $table = 'settings';

    protected $guarded = ['id'];

    public function tahun()
    {
        return $this->belongsTo(Tahun::class, 'tahun_id');
    }
}
