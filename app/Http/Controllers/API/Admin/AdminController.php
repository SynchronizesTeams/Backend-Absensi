<?php

namespace App\Http\Controllers\API\Admin;

use App\Exports\AbsensiExport;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class AdminController extends Controller
{
    public function export(Request $request)
    {
        $from = $request->input('from');
        $to   = $request->input('to');

        $filename = 'absensi';

        if ($from && $to) {
            $filename .= "_{$from}_to_{$to}.xlsx";
        } elseif ($from) {
            $filename .= "_{$from}.xlsx";
        } else {
            $filename .= ".xlsx";
        }

        return Excel::download(new AbsensiExport($from, $to), $filename);
    }

    public function CountUser()
    {
        return Cache::remember('user_count', now()->addHours(1), function () {
            // total semua user
            $total = User::count();

            // total per role
            $perRole = User::select('role', DB::raw('count(*) as total'))
                ->groupBy('role')
                ->pluck('total', 'role'); // hasilnya: ['admin' => 5, 'user' => 20, ...]

            return response()->json([
                'total_users' => $total,
                'per_role' => $perRole,
            ]);
        });
    }



    // public function countUsersByRole($role)
    // {
    //     return Cache::remember("user_count_by_role_{$role}", now()->addHours(1), function () use ($role) {
    //         $users = User::where('role', '=', $role)->count();

    //         return response()->json([
    //             'users' => $users,
    //         ]);
    //     });
    // }
}
