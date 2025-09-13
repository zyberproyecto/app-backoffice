<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Solo nuestro admin del backoffice
        $this->call([
            AdminSeeder::class,
        ]);
    }
}