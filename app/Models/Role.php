<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $table = 'role';

    protected $guarded = ['id'];

    public function users()
    {
        return $this->hasMany(User::class, 'role_id');
    }

    public function komponen()
    {
        return $this->hasMany(Komponen::class, 'role_id');
    }

    public function penilaian_verifikator()
    {
        return $this->hasMany(PenilaianVerifikator::class, 'role_id');
    }
}
