<?php

namespace App\Jobs;

use App\Models\Absensi;
use App\Models\Log;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class SaveAbsensiMasukJob implements ShouldQueue
{
    use  Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */

    protected $file;
    protected $absensiId;
    protected $userId;
    protected $time;
    public function __construct($file, $absensiId, $userId, $time)
    {
        $this->file = $file;
        $this->absensiId = $absensiId;
        $this->userId = $userId;
        $this->time = $time;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
         // Pindahkan file dari temp ke lokasi final
    $finalPath = Storage::disk('public')->putFile('absensi', Storage::path($this->filePath));

    // Update absensi
    Absensi::where('id', $this->absensiId)->update([
        'photo_masuk' => $finalPath,
    ]);

    // Buat log
    Log::create([
        'user_id' => $this->userId,
        'status' => 'masuk',
        'is_success' => true,
        'time' => $this->time->format('H:i:s'),
    ]);
    }
}
