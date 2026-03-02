<?php

namespace App\Services;

use App\Models\Absensi;
use App\Models\Log;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AbsensiService
{
    private const OFFICE_LATITUDE = -6.467017;
    private const OFFICE_LONGITUDE = 106.864356;
    private const MAX_DISTANCE_METERS = 100;

    private function isWithinRadius($latitude, $longitude): bool
    {
        $earthRadius = 6371000; // Earth radius in meters

        $latFrom = deg2rad(self::OFFICE_LATITUDE);
        $lonFrom = deg2rad(self::OFFICE_LONGITUDE);
        $latTo = deg2rad($latitude);
        $lonTo = deg2rad($longitude);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
            cos($latFrom) * cos($latTo) *
            sin($lonDelta / 2) * sin($lonDelta / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        $distance = $earthRadius * $c;

        return $distance <= self::MAX_DISTANCE_METERS;
    }

    /* =========================
     * RULE ABSENSI PER ROLE
     * ========================= */
    public function getRule($user, Carbon $now): array
    {
        $time = $now->format('H:i');

        if ($user->role === 'guru') {
            return [
                'role' => 'guru',
                'masuk_end' => '07:15',
                'pulang_start' => '13:00',
                'lintas_hari' => false,
            ];
        }

        if ($user->role === 'tata-usaha') {
            return [
                'role' => 'tata-usaha',
                'masuk_end' => '07:15',
                'pulang_start' => '13:00',
                'lintas_hari' => false,
            ];
        }

        if ($user->role === 'ob') {
            return [
                'role' => 'ob',
                'masuk_end' => '06:59',
                'pulang_start' => '18:59',
                'lembur_start' => '19:00',
                'lintas_hari' => false,
            ];
        }

        if ($user->role === 'satpam') {
            if ($now->format('H:i') >= '18:00' || $now->format('H:i') < '05:30') {
                return [
                    'role' => 'satpam',
                    'shift' => 'malam',
                    'masuk_start' => '18:00',
                    'pulang_start' => '05:28',
                    'lintas_hari' => true,
                ];
            }

            return [
                'role' => 'satpam',
                'shift' => 'pagi',
                'masuk_start' => '05:30',
                'pulang_start' => '17:58',
                'lintas_hari' => false,
            ];
        }

        abort(403, 'Role tidak dikenali');
    }

    /* =========================
     * TANGGAL ABSENSI
     * ========================= */
    public function resolveTanggal(array $rule, Carbon $now): string
    {
        if (($rule['shift'] ?? null) === 'malam'
            && $now->format('H:i') < $rule['pulang_start']) {
            return $now->copy()->subDay()->toDateString();
        }

        return $now->toDateString();
    }

    /* =========================
     * ABSEN MASUK
     * ========================= */
    public function masuk(Request $request, $user)
    {
        $now = Carbon::now();

        $rule = $this->getRule($user, $now);

        // ===== VALIDASI LOKASI =====
        if (!$this->isWithinRadius($request->latitude, $request->longitude)) {
            abort(403, 'Anda berada di luar area kantor');
        }

        if (isset($rule['masuk_start'])) {
            if ($now->format('H:i') < $rule['masuk_start']) {
                abort(403, 'Belum waktunya absen masuk');
            }
        }

        // ===== RULE & TANGGAL =====
        $tanggal = $this->resolveTanggal($rule, $now);

        // ===== CEK SUDAH ABSEN =====
        $absensi = Absensi::where('user_id', $user->user_id)
            ->whereDate('tanggal', $tanggal)
            ->first();

        if ($absensi) {
            abort(403, 'Sudah absen masuk hari ini');
        }

        // ===== PREDIKAT =====
        $predikat = null;
        if (isset($rule['masuk_end'])) {
            $predikat = $now->format('H:i') <= $rule['masuk_end']
                ? 'Tepat Waktu'
                : 'Telat';
        }

        // ===== SIMPAN FOTO =====
        $path = $request->file('photo_masuk')->store('absensi', 'public');

        $absen = Absensi::create([
            'user_id' => $user->user_id,
            'tanggal' => $tanggal,
            'jam_masuk' => $now->format('H:i:s'),
            'keterangan_masuk' => 'hadir',
            'predikat' => $predikat,
            'photo_masuk' => $path,
            'ttd' => $request->input('ttd'),
            'latitude_masuk' => $request->latitude,
            'longitude_masuk' => $request->longitude,
        ]);

        Log::create([
            'user_id' => $user->user_id,
            'absensi_id' => $absen->id,
            'status' => 'masuk',
            'is_success' => true,
            'time' => $now->format('H:i:s'),
        ]);

        return $absen;
    }

    /* =========================
     * ABSEN PULANG
     * ========================= */
    public function pulang(Request $request, $user)
    {
        $now = Carbon::now();

        // ===== VALIDASI LOKASI =====
        if (!$this->isWithinRadius($request->latitude, $request->longitude)) {
            abort(403, 'Anda berada di luar area kantor');
        }

        // ===== AMBIL RULE =====
        $rule = $this->getRule($user, $now);

        // ===== RESOLVE TANGGAL =====
        $tanggal = $this->resolveTanggal($rule, $now);

        // ===== CEK DATA ABSENSI =====
        $absensi = Absensi::where('user_id', $user->user_id)
            ->whereDate('tanggal', $tanggal)
            ->first();

        if (!$absensi) {
            abort(403, 'Belum absen masuk');
        }

        if ($absensi->jam_pulang) {
            abort(403, 'Sudah absen pulang');
        }

        $jamSekarang = $now->format('H:i');

        // =========================================================
        // VALIDASI WAKTU BERDASARKAN ROLE & SHIFT
        // =========================================================

        if ($user->role === 'satpam') {
            if ($rule['shift'] === 'pagi') {
                // Shift pagi: pulang minimal jam 18:00
                if ($jamSekarang < $rule['pulang_start']) {
                    abort(403, 'Belum waktunya pulang (shift pagi)');
                }
            } elseif ($rule['shift'] === 'malam') {
                // Shift malam: hanya boleh pulang antara 00:00 - 05:30
                if ($jamSekarang > $rule['pulang_start']) {
                    abort(403, 'Belum waktunya pulang (shift malam)');
                }
            }
        } else {
            // Role selain satpam
            if (isset($rule['pulang_start']) && $jamSekarang < $rule['pulang_start']) {
                abort(403, 'Belum waktunya pulang');
            }
        }

        // =========================================================
        // KETERANGAN PULANG
        // =========================================================

        $keteranganPulang = 'normal';

        if ($user->role === 'ob' && isset($rule['lembur_start'])) {
            if ($jamSekarang >= $rule['lembur_start']) {
                $keteranganPulang = 'lembur';
            }
        }

        // =========================================================
        // SIMPAN FOTO
        // =========================================================

        $path = $request->file('photo_pulang')->store('absensi', 'public');

        $absensi->update([
            'jam_pulang' => $now->format('H:i:s'),
            'photo_keluar' => $path,
            'keterangan_pulang' => $keteranganPulang,
            'latitude_pulang' => $request->latitude,
            'longitude_pulang' => $request->longitude,
        ]);

        // =========================================================
        // LOG
        // =========================================================

        Log::create([
            'user_id' => $user->user_id,
            'absensi_id' => $absensi->id,
            'status' => 'pulang',
            'is_success' => true,
            'time' => $now->format('H:i:s'),
        ]);

        return $absensi;
    }
}
