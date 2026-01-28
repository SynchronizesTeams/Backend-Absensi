<?php

namespace App\Services;

use App\Models\Absensi;
use App\Models\Log;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AbsensiService
{
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
                'pulang_start' => '17:00',
                'lintas_hari' => false,
            ];
        }

        if ($user->role === 'tata-usaha') {
            return [
                'role' => 'tata-usaha',
                'masuk_end' => '07:15',
                'pulang_start' => '17:00',
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
            if ($time >= '17:00' || $time <= '06:00') {
                return [
                    'role' => 'satpam',
                    'shift' => 'malam',
                    'masuk_start' => '18:59',
                    'pulang_end' => '06:00',
                    'lintas_hari' => true,
                ];
            }

            return [
                'role' => 'satpam',
                'shift' => 'pagi',
                'masuk_end' => '06:59',
                'pulang_start' => '18:00',
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
        if ($rule['lintas_hari'] && $now->hour < 12) {
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
        $tanggal = $this->resolveTanggal($rule, $now);

        $absensi = Absensi::where('user_id', $user->user_id)
            ->whereDate('tanggal', $tanggal)
            ->first();

        if ($absensi) {
            abort(403, 'Sudah absen masuk hari ini');
        }

        $predikat = null;
        if (isset($rule['masuk_end'])) {
            $predikat = $now->format('H:i') <= $rule['masuk_end']
                ? 'Tepat Waktu'
                : 'Telat';
        }

        $path = $request->file('photo_masuk')->store('absensi', 'public');

        $absen = Absensi::create([
            'user_id' => $user->user_id,
            'tanggal' => $tanggal,
            'jam_masuk' => $now->format('H:i:s'),
            'keterangan_masuk' => 'hadir',
            'predikat' => $predikat,
            'photo_masuk' => $path,
            'ttd' => $request->input('ttd'),
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
        $rule = $this->getRule($user, $now);
        $tanggal = $this->resolveTanggal($rule, $now);

        $absensi = Absensi::where('user_id', $user->user_id)
            ->whereDate('tanggal', $tanggal)
            ->first();

        if (!$absensi) {
            abort(403, 'Belum absen masuk');
        }

        if ($absensi->jam_pulang) {
            abort(403, 'Sudah absen pulang');
        }

        $keteranganPulang = 'normal';

        if ($user->role === 'ob' && $now->format('H:i') > '18:59') {
            $keteranganPulang = 'lembur';
        }

        if ($user->role === 'satpam' && ($rule['shift'] ?? null) === 'malam') {
            if ($now->format('H:i') > '06:00') {
                abort(403, 'Waktu pulang shift malam sudah lewat');
            }
        }

        $path = $request->file('photo_pulang')->store('absensi', 'public');

        $absensi->update([
            'jam_pulang' => $now->format('H:i:s'),
            'photo_keluar' => $path,
            'keterangan_pulang' => $keteranganPulang,
        ]);

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
