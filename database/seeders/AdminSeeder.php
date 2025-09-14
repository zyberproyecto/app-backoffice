<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('admins')->updateOrInsert(
            ['email' => 'admin@coop.test'], // criterio único
            [
                'ci_usuario' => '11111111',
                'nombre'     => 'Administrador',
                'password'   => Hash::make('admin123'),
                'estado'     => 'activo',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}