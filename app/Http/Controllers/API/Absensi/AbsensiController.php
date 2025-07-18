<?php

namespace App\Http\Controllers\API\Absensi;

use App\Http\Controllers\Controller;
use App\Models\DataAbsensi;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AbsensiController extends Controller
{
    public function presensi($user_id)
    {
        $user = User::where('user_id', '=', $user_id)->firstOrFail();
        $now = Carbon::now();
        $currentTime = $now->format('H:i');
        $today = $now->toDateString();

        $absensi = DataAbsensi::where('user_id', $user_id)
            ->whereDate('tanggal', $today)
            ->first();

        // Jam masuk
        if ($currentTime < '12:59') {
            if (!$absensi) {
                // Penentuan predikat
                $predikat = ($currentTime <= '07:15') ? 'Tepat Waktu' : 'Telat';

                DataAbsensi::create([
                    'user_id' => $user_id,
                    'tanggal' => $today,
                    'jam_masuk' => $now->format('H:i:s'),
                    'keterangan_masuk' => 'hadir', // default enum
                    'predikat' => $predikat,
                    'keterangan' => null,
                ]);

                return response()->json(['message' => "Absen masuk berhasil: $predikat"]);
            }

            return response()->json(['message' => 'Sudah melakukan absen masuk hari ini']);
        }

        // Jam pulang
        if ($currentTime >= '13:00' && $currentTime <= '23:59') {
            if (!$absensi) {
                return response()->json(['message' => 'Belum melakukan absen masuk, tidak bisa absen pulang']);
            }

            if ($absensi->jam_pulang) {
                return response()->json(['message' => 'Sudah melakukan absen pulang hari ini']);
            }

            $keteranganPulang = ($currentTime >= '18:00') ? 'lembur' : 'normal';
            $predikat = ($currentTime < '17:00') ? 'Pulang Cepat' : null;

            $absensi->update([
                'jam_pulang' => $now->format('H:i:s'),
                'keterangan_pulang' => $keteranganPulang,
                'predikat' => $predikat,
            ]);

            return response()->json([
                'message' => "Absen pulang berhasil" . ($predikat ? " ($predikat)" : ""),
            ]);
        }

        return response()->json(['message' => 'Waktu tidak valid untuk absen']);
    }
}
