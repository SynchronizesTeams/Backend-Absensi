<?php

namespace App\Http\Controllers\API\Admin;

use App\Exports\AbsensiExport;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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

    public function userCount()
    {
        return Cache::remember('user_count', now()->addHours(1), function () {
            return response()->json([
                'user_count' => User::count(),
            ]);
        });
    }

    public function userFilter($role)
    {
        return Cache::remember("user_count_by_role_{$role}", now()->addHours(1), function () use ($role) {
            $users = User::where('role', '=', $role)->count();

            return response()->json([
                'users' => $users,
            ]);
        });
    }
}
