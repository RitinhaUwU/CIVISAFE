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
        // Data Import
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
        ]);

        User::factory(10)->create();
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
