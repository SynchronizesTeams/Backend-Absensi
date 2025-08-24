<?php

namespace App\Jobs;

use App\Models\Absensi;
use App\Models\Log;
use Carbon\Carbon;
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
        $this->file = $file; // misalnya: temp/abc123.jpg
        $this->absensiId = $absensiId;
        $this->userId = $userId;
        $this->time = $time; // simpan sebagai string
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $sourcePath = storage_path('app/' . $this->file);
        $targetPath = 'absensi/' . basename($this->file);

        if (!file_exists($sourcePath)) {
            Log::create([
                'user_id' => $this->userId,
                'status' => 'masuk',
                'is_success' => false,
                'time' => Carbon::parse($this->time)->format('H:i:s'),
            ]);
            return;
        }

        // pindahkan file
        Storage::disk('public')->put($targetPath, file_get_contents($sourcePath));
        unlink($sourcePath);

        // update absensi
        Absensi::where('id', '=',$this->absensiId)->update([
            'photo_masuk' => $targetPath,
        ]);

        // log sukses
        Log::create([
            'user_id' => $this->userId,
            'status' => 'masuk',
            'is_success' => true,
            'time' => Carbon::parse($this->time)->format('H:i:s'),
        ]);
    }
}
