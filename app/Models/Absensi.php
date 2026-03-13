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
        'tgl_masuk',
        'tgl_pulang',
        'jam_masuk',
        'jam_pulang',
        'keterangan_masuk',
        'keterangan_pulang',
        'keterangan',
        'predikat',
        'ttd',
        'shift',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
