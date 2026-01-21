<?php

namespace App\Http\Controllers\API\Admin;

use App\Exports\AbsensiExport;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;

class AdminController extends Controller
{
    public function export(Request $request)
    {
        $from = $request->input('from');
        $to = $request->input('to');

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
    public function index()
    {
        $users = User::all();
        return response()->json([
            'status' => 'success',
            'data' => $users
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'no_induk' => 'required|string|unique:users,no_induk',
            'role' => 'required|string',
            'telepon' => 'nullable|string',
            'alamat' => 'nullable|string',
            'photo' => 'nullable|string',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['user_id'] = uniqid("user_");

        $user = User::create($validated);

        Cache::forget('user_count');

        return response()->json([
            'status' => 'success',
            'message' => 'User created successfully',
            'data' => $user
        ], 201);
    }

    public function show($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $user
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found'
            ], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'password' => 'nullable|string|min:6',
            'no_induk' => 'sometimes|string|unique:users,no_induk,' . $id,
            'role' => 'sometimes|string',
            'telepon' => 'nullable|string',
            'alamat' => 'nullable|string',
            'photo' => 'nullable|string',
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        Cache::forget('user_count');

        return response()->json([
            'status' => 'success',
            'message' => 'User updated successfully',
            'data' => $user
        ]);
    }

    public function destroy($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found'
            ], 404);
        }

        $user->delete();

        Cache::forget('user_count');

        return response()->json([
            'status' => 'success',
            'message' => 'User deleted successfully'
        ]);
    }
}
