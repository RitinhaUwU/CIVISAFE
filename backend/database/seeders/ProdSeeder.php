<?php

namespace Database\Seeders;

use App\Enums\RolesEnum;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProdSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
            IncidentTypesSeeder::class,
            IncidentStateSeeder::class,
            IncidentPrioritySeeder::class,
            EntityTypesSeeder::class,
            DonationGoodsTypeSeeder::class
        ]);


        User::factory()->create([
            'name' => 'Utilizador Administrador',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'locked' => false,
        ])->assignRole(RolesEnum::ADMIN->value);
    }
}
