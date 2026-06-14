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

        $roles = collect([
            'super_admin',
            'supervisor_general',
            'supervisor',
            'conserje',
        ])->mapWithKeys(fn (string $name): array => [
            $name => Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']),
        ]);

        $users = [
            [
                'name'         => 'Jordan',
                'lastname'     => 'Moran',
                'email'        => 'jordan@live.uleam.edu.ec',
                'facultad_id'  => 1,
                'password'     => Hash::make('123456'),
                'active_state' => true,
            ],
            [
                'name'         => 'Kevin',
                'lastname'     => 'De Prueba',
                'email'        => 'kevin@live.uleam.edu.ec',
                'facultad_id'  => 1,
                'password'     => Hash::make('123456'),
                'active_state' => false,
            ],
            [
                'name'         => 'Adrian',
                'lastname'     => 'De Prueba',
                'email'        => 'adrian@live.uleam.edu.ec',
                'facultad_id'  => 1,
                'password'     => Hash::make('123456'),
                'active_state' => true,
            ],
        ];

        foreach ($users as $userData) {
            $user = User::updateOrCreate(
                ['email' => $userData['email']], 
                $userData
            );
            $user->syncRoles([$roles['super_admin']]);
        }
    }
}
