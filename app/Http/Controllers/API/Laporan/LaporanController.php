<?php

namespace App\Http\Controllers\API\Laporan;

use App\Http\Controllers\Controller;
use App\Models\Laporan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class LaporanController extends Controller
{
    public function index()
    {
        $laporans = Laporan::all();
        return response()->json($laporans);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'uraian_tugas' => 'required|string|max:255',
            'waktu_capaian' => 'required|string',
            'pencapaian_tugas' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $laporan = Laporan::create([
            'user_id' => Auth::user()->user_id,
            'uraian_tugas' => $request->uraian_tugas,
            'waktu_capaian' => $request->waktu_capaian,
            'pencapaian_tugas' => $request->pencapaian_tugas,
        ]);

        return response()->json($laporan, 201);
    }

    public function show($id)
    {
        $laporan = Laporan::find($id);

        if (!$laporan) {
            return response()->json(['error' => 'Laporan not found'], 404);
        }

        return response()->json($laporan);
    }

    public function update(Request $request, $id)
    {
        $laporan = Laporan::find($id);

        if (!$laporan) {
            return response()->json(['error' => 'Laporan not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'uraian_tugas' => 'sometimes|required|string|max:255',
            'waktu_capaian' => 'sometimes|required|string',
            'pencapaian_tugas' => 'sometimes|required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $laporan->update($request->only(['uraian_tugas', 'waktu_capaian', 'pencapaian_tugas']));

        return response()->json($laporan);
    }

    public function destroy($id)
    {
        $laporan = Laporan::find($id);

        if (!$laporan) {
            return response()->json(['error' => 'Laporan not found'], 404);
        }

        $laporan->delete();
        return response()->json(['message' => 'Laporan deleted successfully']);
    }

    public function showLaporanByUser(Request $request, $user_id) {
        $laporan = Laporan::where("user_id", '=', $user_id)->get();

        return response()->json($laporan);
    }

    public function showLaporanByDate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $start = Carbon::parse($request->start_date)->startOfDay();
        $end = Carbon::parse($request->end_date)->endOfDay();

        $laporans = Laporan::whereDate('created_at', '>=', $request->start_date)
                        ->whereDate('created_at', '<=', $request->end_date)
                        ->get();

        return response()->json($laporans);
    }

    public function showDailyLaporan(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $laporans = Laporan::whereDate('created_at', $request->date)->get();

        return response()->json($laporans);
    }
}
