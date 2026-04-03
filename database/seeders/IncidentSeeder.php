<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class IncidentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
{
    \App\Models\Incidents::create([
        'title' => 'Tubería rota en Facultad de Hotelería',
        'description' => 'Hay una inundación en el pasillo principal del segundo piso.',
        'location' => 'Facultad de Hotelería y Turismo',
        'status' => 'pending',
        'image' => 'https://www.shutterstock.com/image-photo/aniem-dbs-goku-600w-2689176967.jpg' 
    ]);
}
}
