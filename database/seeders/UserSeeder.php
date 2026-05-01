<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // php artisan db:seed --class=UserSeeder
        
        $role = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

        $users = [
            [
                'name'         => 'Jordan',
                'lastname'     => 'Moran',
                'email'        => 'jordan@gmail.com',
                'carrera_id'   => 1,
                'password'     => Hash::make('123456'),
                'active_state' => false,
            ],
            [
                'name'         => 'Kevin',
                'lastname'     => 'De Prueba',
                'email'        => 'kevin@gmail.com',
                'carrera_id'   => 1,
                'password'     => Hash::make('123456'),
                'active_state' => true,
            ],
            [
                'name'         => 'Adrian',
                'lastname'     => 'De Prueba',
                'email'        => 'adrian@gmail.com',
                'carrera_id'   => 1,
                'password'     => Hash::make('123456'),
                'active_state' => true,
            ],
        ];

        foreach ($users as $userData) {
            $user = User::updateOrCreate(
                ['email' => $userData['email']], // Si ya existe, solo lo actualiza
                $userData
            );
            $user->assignRole($role);
        }
    }
}