<?php

namespace Database\Seeders;

use App\Models\Facultad;
use Illuminate\Database\Seeder;

class FacultadSeeder extends Seeder
{
    public function run(): void
    {
        $facultades = [
            ['code' => 'B-16', 'name' => 'Ciencias de la Vida'],
            ['code' => 'A-01', 'name' => 'Facultad de ejemplo'],
        ];

        foreach ($facultades as $facultad) {
            Facultad::updateOrCreate(
                ['code' => $facultad['code']],
                $facultad
            );
        }
    }
}
