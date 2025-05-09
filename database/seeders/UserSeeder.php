<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'sviluppo@zwan.it', // Change this to your desired email
            'email_verified_at' => now(),
            'password' => Hash::make('Selda@2025!'), // Change this to a strong password
        ]);
        $superAdmin->assignRole('SuperAmministratore');

        // You can create other default users here if needed, for example:
        // $adminUser = User::create([
        //     'name' => 'Admin User',
        //     'email' => 'admin@example.com',
        //     'email_verified_at' => now(),
        //     'password' => Hash::make('password'),
        // ]);
        // $adminUser->assignRole('Amministratore');

        // $operatorUser = User::create([
        //     'name' => 'Operator User',
        //     'email' => 'operator@example.com',
        //     'email_verified_at' => now(),
        //     'password' => Hash::make('password'),
        // ]);
        // $operatorUser->assignRole('Operatore');
    }
}
