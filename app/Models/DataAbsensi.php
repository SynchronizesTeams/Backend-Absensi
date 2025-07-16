<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataAbsensi extends Model
{
    protected $fillable = [
        'user_id',
        'tanggal',
        'jam_masuk',
        'jam_pulang',
        'keterangan_masuk',
        'keterangan_pulang',
    ];

    public function User()
    {
        return $this->hasMany(User::class, 'user_id', 'user_id');
    }

    protected function casts()
    {
        return [
            'jam_masuk' => 'datetime:H:i',
            'jam_pulang' => 'datetime:H:i',
        ];
    }
}
