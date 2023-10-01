<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\Role;
use App\Models\TypeOrder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

        \App\Models\Store::create([
            'name' => 'Lybo',
            'ruc' => '10750772221',
        ]);

        $roles = [
            ['name' => 'Super Admin'],
            ['name' => 'Admin Tienda'],
            ['name' => 'Usuario Tienda']
        ];

        foreach ($roles as $roleData) {
            Role::create($roleData);
        }

        \App\Models\User::create([
            'name' => 'Test User',
            'email' => 'admin@admin.com',
            'password' => bcrypt('123456'),
            'store_id' => 1, // ID de la tienda asociada al usuario
            'rol_id' => 1,
        ]);
      
        $typeOrder = [
            ['description' => 'Cocina'],
            ['description' => 'Personal']
        ];
        foreach ($typeOrder as $typo) {
            TypeOrder::create($typo);
        }
        

    }
}
