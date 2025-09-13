<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('admins')->updateOrInsert(
            ['email' => 'admin@example.com'],
            [
                'ci_usuario'    => '11111111',
                'nombre'        => 'Admin Principal',
                'password'      => Hash::make('admin123'),
                'estado'        => 'activo',
                'remember_token'=> Str::random(10),
                'created_at'    => now(),
                'updated_at'    => now(),
            ]
        );
    }
}