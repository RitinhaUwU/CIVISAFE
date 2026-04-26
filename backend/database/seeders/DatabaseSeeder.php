<?php

namespace Database\Seeders;

use App\Enums\RolesEnum;
use App\Models\Category;
use App\Models\Entity;
use App\Models\Incident;
use App\Models\IncidentParty;
use App\Models\IncidentPriority;
use App\Models\IncidentState;
use App\Models\User;
use App\Models\Volunteer;
use DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use function Illuminate\Support\enum_value;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
            IncidentTypesSeeder::class,
            IncidentStateSeeder::class,
            IncidentPrioritySeeder::class,
            EntityTypesSeeder::class,
        ]);


        User::factory()->create([
            'name' => 'Utilizador Administrador',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'locked' => false,
        ])->assignRole(enum_value(RolesEnum::ADMIN));

        User::factory()->create([
            'name' => 'Utilizador Manager',
            'email' => 'manager@example.com',
            'password' => bcrypt('password'),
            'locked' => false,
        ])->assignRole(enum_value(RolesEnum::MANAGER));

        User::factory()->create([
            'name' => 'Utilizador User',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
            'locked' => false,
        ])->assignRole(enum_value(RolesEnum::USER));

        User::factory(10)->create()->each(function ($user) {
            $role = Role::inRandomOrder()->first();
            $user->assignRole($role->name);
        });
        Entity::factory(10)->create();
        Incident::factory(60)->create();
        Volunteer::factory(10)->create();

        for ($i = 0; $i <= 20; $i++) {
            Incident::factory()->create([
                'is_major' => false,
                'incident_id' => Incident::where(['is_major' => true])->inRandomOrder()->first()->id,
            ]);
        }

        IncidentParty::factory(100)->create();

    }
}
