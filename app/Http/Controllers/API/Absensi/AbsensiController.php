<?php

namespace App\Http\Controllers\API\Absensi;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Contracts\Service\Attribute\Required;

class AbsensiController extends Controller
{
    public function masuk(Request $request)
    {
        $user = auth()->user();

        $now = Carbon::now();
        $currentTime = $now->format('H:i');
        $today = $now->toDateString();

        $absensi = Absensi::where('user_id', $user->id)
            ->whereDate('tanggal', $today)
            ->first();

        $request->validate([
            'photo_masuk' => 'required|image',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $schoolLat = -6.467017;
        $schoolLng = 106.864356;
        $radius = 100;

        if (!$this->isWithinRadius($request->latitude, $request->longitude, $schoolLat, $schoolLng, $radius)) {
            return response()->json(['message' => 'Lokasi Anda di luar area yang diperbolehkan.'], 403);
        }

        $path = $request->file('photo_masuk')->store('absensi', 'public');

        if ($currentTime < '12:59') {
            if (!$absensi) {
                // Penentuan predikat
                $predikat = ($currentTime <= '07:15') ? 'Tepat Waktu' : 'Telat';

                $absen = Absensi::create([
                    'user_id' => $user->user_id,
                    'tanggal' => $today,
                    'jam_masuk' => $now->format('H:i:s'),
                    'keterangan_masuk' => 'hadir', // default enum
                    'predikat' => $predikat,
                    'keterangan' => null,
                    'photo_masuk' => $path,
                    // 'latitude' => $request->latitude,
                    // 'longitude' => $request->longitude,
                ]);

                return response()->json([
                    'message' => 'Absensi berhasil.',
                    'data' => $absen
                ]);
            }

            return response()->json(['message' => 'Anda sudah melakukan absensi hari ini']);
        }

        return response()->json([
            'message' => 'Waktu tidak valid untuk absen masuk. Pastikan Anda melakukan absen sebelum jam 13:00.',
            'current_time' => $currentTime,
            'allowed_until' => '12:59'
        ])->setStatusCode(403, 'Forbidden');
    }

    public function pulang(Request $request)
    {
        $user = auth()->user();
        $now = Carbon::now();
        $currentTime = $now->format('H:i');
        $today = $now->toDateString();

        $absensi = Absensi::where('user_id', $user->id)
            ->whereDate('tanggal', $today)
            ->first();

            $request->validate([
                'photo_pulang' => 'required|image',
                'latitude' => 'required|numeric',
                'longitude' => 'required|numeric',
            ]);

            $schoolLat = -6.467017;
            $schoolLng = 106.864356;
            $radius = 100;

            if (!$this->isWithinRadius($request->latitude, $request->longitude, $schoolLat, $schoolLng, $radius)) {
                return response()->json(['message' => 'Lokasi Anda di luar area yang diperbolehkan.'], 403);
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
            $path = $request->file('photo_pulang')->store('absensi', 'public');

            $absensi->update([
                'jam_pulang' => $now->format('H:i:s'),
                'keterangan_pulang' => $keteranganPulang,
                'predikat' => $predikat,
            ]);
            $absensi->photo_keluar = $path;
            $absensi->save();

            return response()->json([
                'message' => "Absen pulang berhasil" . ($predikat ? " ($predikat)" : ""),
            ]);
        }

        return response()->json(['message' => 'Waktu tidak valid untuk absen']);
    }

    public function izin(Request $request)
    {
        $user = auth()->user();
        $now = Carbon::now();
        $currentTime = $now->format('H:i');
        $today = $now->toDateString();
        $request->validate([
            'keterangan' => 'required|string',
        ]);

        $absensi = Absensi::where('user_id', $user->user_id)
        ->whereDate('tanggal', $today)
        ->first();

        if (!$absensi) {
            Absensi::create([
                'user_id' => $user->user_id,
                'tanggal' => $today,
                'jam_masuk' => null,
                'jam_pulang' => null,
                'keterangan_masuk' => 'izin',
                'keterangan_pulang' => null,
                'keterangan' => $request->keterangan,
            ]);

            return response()->json(['message' => "Absen izin berhasil"]);
        } else {
            return response()->json(['message' => 'Sudah melakukan absen hari ini']);
        }

    }

    public function seeAbsensi($tanggal)
    {
        $absensi = Absensi::whereDate('tanggal', '=',$tanggal)->with('user')->select(
            'user_id',
            'tanggal',
            'jam_masuk',
            'jam_pulang',
            'keterangan_masuk',
            'keterangan_pulang',
            'keterangan'
        )->get();

        if ($absensi->isEmpty()) {
            return response()->json(['message' => 'Tidak ada data absensi untuk tanggal tersebut'], 404);
        }

        return response()->json($absensi);
    }

    public function getAbsensiByUserId($user_id)
    {
        $absensi = Absensi::where('user_id', $user_id)->with('user')->get();

        if ($absensi->isEmpty()) {
            return response()->json(['message' => 'Tidak ada data absensi untuk user ini'], 404);
        }

        return response()->json($absensi);
    }

    private function isWithinRadius($lat1, $lon1, $lat2, $lon2, $radius)
    {
        $earthRadius = 6371000;

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon/2) * sin($dLon/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        $distance = $earthRadius * $c;

        return $distance <= $radius;
    }
}
