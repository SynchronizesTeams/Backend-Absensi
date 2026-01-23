<?php

namespace App\Http\Controllers\API\Absensi\V2;

use App\Http\Controllers\Controller;
use App\Services\AbsensiService;
use Illuminate\Http\Request;

class AbsensiControllerV2 extends Controller
{
    public function __construct(private AbsensiService $absensiService) {}

    public function masuk(Request $request)
    {
        $request->validate([
            'photo_masuk' => 'required|image|max:10240',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $absen = $this->absensiService->masuk($request, auth()->user());

        return response()->json([
            'message' => 'Absen masuk berhasil',
            'data' => $absen
        ]);
    }

    public function pulang(Request $request)
    {
        $request->validate([
            'photo_pulang' => 'required|image|max:10240',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $this->absensiService->pulang($request, auth()->user());

        return response()->json([
            'message' => 'Absen pulang berhasil'
        ]);
    }
}
