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
                // Find existing user by email or create a new instance
                $user = User::firstOrNew(['no_induk' => $row[1]]);

                // If it's a new user, assign a unique user_id
                if (!$user->exists) {
                    $user->user_id = uniqid('user_');
                }

                // Update details
                $user->name = $row[0];
                $user->no_induk = $row[1];
                $user->password = Hash::make($row[3]);
                $user->role = $row[4];
                $user->email = $row[2];
                $user->save();
            }
            fclose($handle);
        }
    }
}
