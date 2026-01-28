<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/trabsen.csv');

        if (!file_exists($path) || !is_readable($path)) {
            return;
        }

        if (($handle = fopen($path, 'r')) !== false) {
            while (($row = fgetcsv($handle, 1000, ',')) !== false) {

                // Ambil user berdasarkan no_induk
                $user = User::firstOrNew(['no_induk' => $row[1]]);

                // Jika user baru
                if (!$user->exists) {
                    $user->user_id = uniqid('user_');
                }

                // Set data
                $user->name  = $row[0];
                $user->email = $row[2];
                $user->role  = $row[4];

                /**
                 * Password hanya di-update jika:
                 * - user baru
                 * - atau password CSV berbeda
                 */
                if (!$user->exists || !Hash::check($row[3], $user->password)) {
                    $user->password = Hash::make($row[3]);
                }

                /**
                 * Simpan HANYA jika ada perubahan
                 */
                if ($user->isDirty()) {
                    $user->save();
                }
            }

            fclose($handle);
        }
    }

}
