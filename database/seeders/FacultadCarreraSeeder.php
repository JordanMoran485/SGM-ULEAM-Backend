<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Facultad;
use App\Models\Carrera;

class FacultadCarreraSeeder extends Seeder
{
    public function run(): void

    // php artisan db:seed --class=FacultadCarreraSeeder
    {
        $faci = Facultad::create(['name' => 'Facultad de Ciencias Informáticas']);

        Carrera::create([
            'name' => 'Tecnologías de la Información',
            'facultad_id' => $faci->id
        ]);

        Carrera::create([
            'name' => 'Ingeniería en Sistemas',
            'facultad_id' => $faci->id
        ]);

        Carrera::create([
            'name' => 'Software',
            'facultad_id' => $faci->id
        ]);

        $face = Facultad::create(['name' => 'Facultad de Ciencias Económicas']);
        Carrera::create([
            'name' => 'Economía',
            'facultad_id' => $face->id
        ]);
    }
}