<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // Cambiá la CI/email/password a los definitivos
        if (!Admin::find('41234567')) {
            Admin::create([
                'ci_usuario'      => '11111111',
                'primer_nombre'   => 'Admin',
                'primer_apellido' => 'Zyber',
                'email'           => 'admin@zyber.com',
                'telefono'        => '099000000',
                'password'        => Hash::make('adm123'), // cambiá luego
                'estado'          => 'activo',
            ]);
        }
    }
}