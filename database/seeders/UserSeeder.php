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
                // Skip if user with this email or no_induk already exists
                if (User::where('email', $row[2])->orWhere('no_induk', $row[1])->exists()) {
                    continue;
                }

                // Asumsikan urutan kolom: name, no_induk, email, password, role
                User::create([
                    'user_id' => uniqid('user_'),
                    'name' => $row[0],
                    'no_induk' => $row[1],
                    'email' => $row[2],
                    'password' => Hash::make($row[3]),
                    'role' => $row[4],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            fclose($handle);
        }
    }
}
