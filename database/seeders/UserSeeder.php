<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Administradores
        User::create([
            'name' => 'Admin Principal',
            'username' => 'admin',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);
        User::create([
            'name' => 'Admin Secundario',
            'username' => 'admin2',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        // Usuario estándar
        User::create([
            'name' => 'Usuario Demo',
            'username' => 'demo',
            'password' => Hash::make('password'),
            'role' => 'user',
        ]);
    }
}