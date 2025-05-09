<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission; // Import Permission if you plan to seed them here too

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create roles
        Role::create(['name' => 'SuperAmministratore']);
        Role::create(['name' => 'Amministratore']);
        Role::create(['name' => 'Operatore']);

        // You can also create permissions here if needed, for example:
        // Permission::create(['name' => 'edit articles']);
        // Permission::create(['name' => 'delete articles']);
        // Permission::create(['name' => 'publish articles']);
        // Permission::create(['name' => 'unpublish articles']);

        // And assign them to roles, for example:
        // $role = Role::findByName('Amministratore');
        // $role->givePermissionTo('edit articles');
        // $role->givePermissionTo('delete articles');
    }
}
