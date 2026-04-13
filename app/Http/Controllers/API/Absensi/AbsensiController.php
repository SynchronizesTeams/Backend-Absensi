<?php

namespace App\Http\Controllers\API\Absensi;

use App\Http\Controllers\Controller;
use App\Jobs\SaveAbsensiMasukJob;
use App\Jobs\SaveAbsensiPulangJob;
use App\Models\Absensi;
use App\Models\Log;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Contracts\Service\Attribute\Required;
use Carbon\CarbonPeriod;

class AbsensiController extends Controller
{
    public function masuk(Request $request)
    {
        $user = auth()->user();

        $now = Carbon::now();
        $currentTime = $now->format('H:i');
        $today = $now->toDateString();

        $absensi = Absensi::where('user_id', $user->id)
            ->whereDate('tgl_masuk', $today)
            ->first();

        $request->validate([
            'photo_masuk' => 'required|image|max:10240',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'ttd' => 'nullable|string',
        ]);

        $schoolLat = -6.467017;
        $schoolLng = 106.864356;
        $radius = 100;

        if (!$this->isWithinRadius($request->latitude, $request->longitude, $schoolLat, $schoolLng, $radius)) {
            return response()->json(['message' => 'Lokasi Anda di luar area yang diperbolehkan.'], 403);
        }

        $path = $request->file('photo_masuk')->store('absensi', 'public');

        if ($absensi) {
            return response()->json(['message' => 'Anda sudah melakukan absensi hari ini']);
        }
        if ($currentTime < '12:59') {
            if (!$absensi) {
                // Penentuan predikat
                $predikat = ($currentTime <= '07:15') ? 'Tepat Waktu' : 'Telat';

                $absen = Absensi::create([
                    'user_id' => $user->user_id,
                    'tgl_masuk' => $today,
                    'jam_masuk' => $now->format('H:i:s'),
                    'keterangan_masuk' => 'hadir', // default enum
                    'predikat' => $predikat,
                    'keterangan' => null,
                    'photo_masuk' => $path,
                    'ttd' => $request->input('ttd'),
                ]);

                $log = Log::create([
                    'user_id' => $user->user_id,
                    'status' => 'masuk',
                    'is_success' => true,
                    'time' => $now->format('H:i:s'),
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

    public function masukV2(Request $request)
    {
        $user = auth()->user();
        $now = now();
        $currentTime = $now->format('H:i');
        $today = $now->toDateString();

        $request->validate([
            'photo_masuk' => 'required|image|max:10240|mimes:png,jpg,jpeg,bmp,gif,svg,webp,tiff,ico,avif',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'ttd' => 'nullable|string',
        ]);

        $alreadyAbsensi = Absensi::where('user_id', $user->user_id)
            ->whereDate('tgl_masuk', $today)
            ->exists();

        if ($alreadyAbsensi) {
            return response()->json(['message' => 'Anda sudah melakukan absensi hari ini'], 403);
        }

        $schoolLat = -6.467017;
        $schoolLng = 106.864356;
        $radius = 100;
        if (!$this->isWithinRadius($request->latitude, $request->longitude, $schoolLat, $schoolLng, $radius)) {
            return response()->json(['message' => 'Lokasi Anda di luar area yang diperbolehkan.'], 403);
        }

        if ($currentTime >= '13:00') {
            return response()->json([
                'message' => 'Waktu tidak valid untuk absen masuk. Pastikan Anda melakukan absen sebelum jam 13:00.',
                'current_time' => $currentTime,
                'allowed_until' => '12:59'
            ], 403);
        }

        $predikat = ($currentTime <= '07:15') ? 'Tepat Waktu' : 'Telat';
        $path = $request->file('photo_masuk')->store('absensi', 'public');

        $absen = Absensi::create([
            'user_id' => $user->user_id,
            'tgl_masuk' => $today,
            'jam_masuk' => $now->format('H:i:s'),
            'keterangan_masuk' => 'hadir',
            'keterangan' => $predikat,
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

        $cacheKey = "absensi-{$today}";
        Cache::forget($cacheKey);

        return response()->json([
            'message' => 'Absensi berhasil. Data sedang diproses di background.',
            'data' => $absen
        ]);
    }

    public function pulang(Request $request)
    {
        $user = auth()->user();
        $now = Carbon::now();
        $currentTime = $now->format('H:i');
        $today = $now->toDateString();

        $absensi = Absensi::where('user_id', $user->user_id)
            ->whereDate('tgl_masuk', $today)
            ->first(); // pakai first(), bukan exists()

        $request->validate([
            'photo_pulang' => 'required|image|max:10240',
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
                return response()->json(['message' => 'Belum melakukan absen masuk, tidak bisa absen pulang'], 403);
            }

            if ($absensi->jam_pulang) {
                return response()->json(['message' => 'Sudah melakukan absen pulang hari ini'], 403);
            }

            $keteranganPulang = ($currentTime >= '18:00') ? 'lembur' : 'normal';
            $predikat = ($currentTime < '17:00') ? 'Pulang Cepat' : null;
            $path = $request->file('photo_pulang')->store('absensi', 'public');

            $absensi->update([
                'jam_pulang' => $currentTime,
                'photo_keluar' => $path, // jangan lupa simpan path fotonya
                'keterangan_pulang' => $keteranganPulang,
                'tgl_pulang' => $today
            ]);

            Log::create([
                'user_id' => $user->user_id,
                'absensi_id' => $absensi->id,
                'status' => 'pulang',
                'is_success' => true,
                'time' => $now->format('H:i:s'),
            ]);

            return response()->json([
                'message' => "Absen pulang berhasil" . ($predikat ? " ($predikat)" : ""),
            ]);
        }

        return response()->json(['message' => 'Waktu tidak valid untuk absen'], 403);
    }

    public function izin(Request $request)
    {
        $user = auth()->user();
        $now = Carbon::now();
        $today = $now->toDateString();

        $request->validate([
            'keterangan' => 'required|string',
            'photo_izin' => 'required|image|max:10240',
            'ttd' => 'nullable|string',
        ]);

        // cari absensi user hari ini
        $absensi = Absensi::where('user_id', $user->user_id)
            ->whereDate('tgl_masuk', $today)
            ->first();

        // kalau sudah ada absensi hari ini dengan status izin
        if ($absensi && $absensi->keterangan_masuk == "izin") {
            return response()->json(['message' => 'Anda sudah melakukan absen izin hari ini'], 400);
        }

        // kalau sudah ada absensi tapi bukan izin (misal masuk kerja)
        if ($absensi) {
            return response()->json(['message' => 'Anda sudah melakukan absensi hari ini'], 400);
        }

        // simpan foto izin
        $path = $request->file('photo_izin')->store('absensi', 'public');

        // buat absensi baru dengan status izin
        Absensi::create([
            'user_id' => $user->user_id,
            'tgl_masuk' => $today,
            'jam_masuk' => null,
            'jam_pulang' => null,
            'keterangan_masuk' => "izin",
            'keterangan_pulang' => null,
            'photo_izin' => $path,
            'keterangan' => $request->keterangan,
            'ttd' => $request->input('ttd'),
        ]);

        return response()->json(['message' => "Absen izin berhasil"], 200);
    }

    public function seeAbsensi($tanggal)
    {
        $absensi = Absensi::whereDate('tgl_masuk', '=', $tanggal)->with('user')->select(
            'id',
            'user_id',
            'tgl_masuk',
            'tgl_pulang',
            'photo_masuk',
            'photo_izin',
            'photo_keluar',
            'jam_masuk',
            'jam_pulang',
            'keterangan_masuk',
            'keterangan_pulang',
            'keterangan',
            'ttd'
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

    public function recapAbsensi(Request $request, $user_id)
    {


        // Ambil bulan & tahun dari request atau default sekarang
        $bulan = $request->input('bulan', Carbon::now()->month);
        $tahun = $request->input('tahun', Carbon::now()->year);

        // Ambil data rekap kategori (hadir, izin, sakit, cuti, tidak hadir)
        $rekap = Absensi::selectRaw("
                    SUM(CASE WHEN keterangan_masuk = 'hadir' THEN 1 ELSE 0 END) as total_hadir,
                    SUM(CASE WHEN keterangan_masuk = 'izin' THEN 1 ELSE 0 END) as total_izin,
                    SUM(CASE WHEN keterangan_masuk = 'sakit' THEN 1 ELSE 0 END) as total_sakit,
                    SUM(CASE WHEN keterangan_masuk = 'cuti' THEN 1 ELSE 0 END) as total_cuti,
                    SUM(CASE WHEN keterangan_masuk = 'tidak hadir' THEN 1 ELSE 0 END) as total_tidak_hadir
                ")
            ->where('user_id', $user_id)
            ->whereMonth('tgl_masuk', $bulan)
            ->whereYear('tgl_masuk', $tahun)
            ->first();

        // Hitung total hari kerja dari tanggal 1 sampai hari ini (atau akhir bulan)
        $now = Carbon::now();
        $start = Carbon::create($tahun, $bulan, 1);
        $end = ($bulan == $now->month && $tahun == $now->year)
            ? $now // kalau bulan ini, cukup sampai hari ini
             : $start->copy()->endOfMonth();

        $period = CarbonPeriod::create($start, $end);

        // Ambil semua tanggal yang user punya absensi
        $tanggalAbsensi = Absensi::where('user_id', $user_id)
            ->whereBetween('tgl_masuk', [$start->toDateString(), $end->toDateString()])
            ->pluck('tgl_masuk')
            ->toArray();

        // Hitung hari kerja (Senin–Sabtu)
        $hariKerja = collect($period)->reject(fn($date) => $date->isSunday());

        // Tanggal yang belum ada absensi sama sekali
        $tanggalTidakAbsen = $hariKerja->filter(function ($date) use ($tanggalAbsensi) {
            return !in_array($date->toDateString(), $tanggalAbsensi);
        })->map->toDateString()->values();

        $totalTidakAbsen = $tanggalTidakAbsen->count();

        // Tambahkan ke hasil rekap
        $rekap->total_tidak_absen = $totalTidakAbsen;
        $rekap->tanggal_tidak_absen = $tanggalTidakAbsen;

        // Hitung total hari kerja dalam periode
        $rekap->total_hari_kerja = $hariKerja->count();

        return response()->json([
            'user_id' => $user_id,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'periode' => [
                'mulai' => $start->toDateString(),
                'sampai' => $end->toDateString(),
            ],
            'rekap' => $rekap,
            'pesan' => $totalTidakAbsen > 0
            ? "Anda belum absen sebanyak $totalTidakAbsen hari dalam periode ini."
            : "Absensi lengkap, pertahankan!"
        ]);
    }


    public function cekAbsenMasuk($user_id)
    {
        $user = User::where('user_id', $user_id)->first();
        if (!$user) {
            return response()->json(['message' => 'User tidak ditemukan'], 404);
        }

        $now = Carbon::now();
        $today = $now->toDateString();
        $yesterday = $now->copy()->subDay()->toDateString();

        // 1. Cek apakah ada sesi aktif (belum pulang) hari ini atau kemarin (untuk satpam)
        $sessionAktif = Absensi::where('user_id', $user_id)
            ->whereNull('jam_pulang')
            ->where(function ($query) use ($today, $yesterday, $user) {
                $query->whereDate('tgl_masuk', $today);
                if ($user->role === 'satpam') {
                    $query->orWhereDate('tgl_masuk', $yesterday);
                }
            })
            ->exists();

        // if ($sessionAktif) {
        //     return response()->json(['message' => 'Anda masih memiliki sesi absensi yang belum ditutup (belum absen pulang)'], 403);
        // }

        // 2. Cek apakah sudah absen masuk hari ini
        $sudahAbsen = Absensi::where('user_id', $user_id)
            ->whereDate('tgl_masuk', $today)
            ->where('keterangan_masuk', '=', 'hadir')
            ->exists();

        if ($sudahAbsen) {
            return response()->json(['message' => 'Anda sudah melakukan absen masuk hari ini']);
        }

        return response()->json(['message' => 'Anda belum melakukan absen masuk hari ini'], 401);
    }

    public function cekAbsenPulang($user_id)
    {
        $user = User::where('user_id', $user_id)->first();
        if (!$user) {
            return response()->json(['message' => 'User tidak ditemukan'], 404);
        }

        $now = Carbon::now();
        $today = $now->toDateString();
        $yesterday = $now->copy()->subDay()->toDateString();

        // Cari sesi aktif yang bisa dipulangkan
        $sessionAktif = Absensi::where('user_id', $user_id)
            ->whereNull('jam_pulang')
            ->where(function ($query) use ($today, $yesterday, $user) {
                $query->whereDate('tgl_masuk', $today);
                if ($user->role === 'satpam') {
                    $query->orWhereDate('tgl_masuk', $yesterday);
                }
            })
            ->first();

        if ($sessionAktif) {
            return response()->json(['message' => 'Anda belum melakukan absen pulang']);
        }

        // Cek apakah sudah pulang hari ini
        $sudahPulang = Absensi::where('user_id', $user_id)
            ->whereDate('tgl_masuk', $today)
            ->whereNotNull('jam_pulang')
            ->exists();

        if ($sudahPulang) {
            return response()->json(['message' => 'Anda sudah melakukan absen pulang hari ini']);
        }

        return response()->json(['message' => 'Anda belum melakukan absen masuk hari ini'], 401);
    }

    private function isWithinRadius($lat1, $lon1, $lat2, $lon2, $radius)
    {
        $earthRadius = 6371000;

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $earthRadius * $c;

        return $distance <= $radius;
    }
}