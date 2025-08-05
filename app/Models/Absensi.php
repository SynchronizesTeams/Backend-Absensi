<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Absensi extends Model
{
    protected $fillable = [
        'user_id',
        'photo_masuk',
        'photo_keluar',
        'photo_izin',
        'latitude',
        'longitude',
        'tanggal',
        'jam_masuk',
        'jam_pulang',
        'keterangan_masuk',
        'keterangan_pulang',
        'keterangan',
    ];

    public function User()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
