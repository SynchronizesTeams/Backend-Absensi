<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Laporan extends Model
{
    protected $fillable = [
        "user_id",
        "uraian_tugas",
        "waktu_capaian",
        "pencapaian_tugas"
    ];

    public function User() {
        return $this->belongsTo(User::class, "user_id", "user_id");
    }
}
