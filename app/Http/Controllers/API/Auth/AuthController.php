<?php

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        $user = User::where('no_induk', $request->no_induk)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        return response()->json([
            'message' => 'Login berhasil!',
            'user' => $user,
            'token' => $user->createToken('auth_token')->plainTextToken,
        ]);
    }

    public function register(RegisterRequest $request)
    {
        DB::beginTransaction();

        try {
            $user = User::create([
                'user_id' => uniqid('user_'),
                'name' => $request->name,
                'no_induk' => $request->no_induk,
                'email' => $request->email,
                'password' => bcrypt($request->password),
                'role' => $request->role,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'User berhasil ditambahkan!',
                'data' => $user
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Terjadi kesalahan saat menyimpan data.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function editUserInfo(Request $request, $user_id)
    {
        DB::beginTransaction();

        try {
            $user = User::where('user_id', '=',$user_id)->first();

            $user->update($request->only(['name', 'email', 'no_induk', 'no_telp']));

            DB::commit();

            return response()->json([
                'message' => 'User berhasil diperbarui!',
                'data' => $user
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Terjadi kesalahan saat memperbarui data.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getUserInfo(Request $request, $user_id)
    {
        $user = User::where('user_id', '=',$user_id)->first();

        return response()->json([
            'data' => $user
        ]);
    }

    public function editPassword(Request $request, $user_id)
    {
        DB::beginTransaction();

        try {
            $user = User::where('user_id', '=',$user_id)->first();

            $user->update([
                'password' => bcrypt($request->password)
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Password berhasil diperbarui!',
                'data' => $user
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Terjadi kesalahan saat memperbarui data.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
