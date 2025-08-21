<?php

namespace App\Jobs;

use App\Models\Absensi;
use App\Models\Log;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SaveAbsensiPulangJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $path;
    protected $absensiId;
    protected $userId;
    protected $time;

    public function __construct($path, $absensiId, $userId, $time)
    {
        $this->path = $path;
        $this->absensiId = $absensiId;
        $this->userId = $userId;
        $this->time = $time;
    }

    public function handle()
    {
        // Update absensi dengan foto pulang
        Absensi::where('id', $this->absensiId)->update([
            'photo_keluar' => $this->path,
        ]);

        // Simpan log
        Log::create([
            'user_id' => $this->userId,
            'status' => 'pulang',
            'is_success' => true,
            'time' => $this->time->format('H:i:s'),
        ]);
    }
}
