<?php

namespace Database\Seeders;

use App\Enums\RolesEnum;
use App\Models\Category;
use App\Models\Entity;
use App\Models\Incident;
use App\Models\IncidentEntitiesMapping;
use App\Models\IncidentPriority;
use App\Models\IncidentState;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Validation\Rules\In;
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
        Role::create(['name' => enum_value(RolesEnum::ADMIN)]);
        Role::create(['name' => enum_value(RolesEnum::MANAGER)]);
        Role::create(['name' => enum_value(RolesEnum::USER)]);

        User::factory()->create([
            'name' => 'Utilizador Administrador',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'locked' => false,
        ]);

        User::factory(10)->create();

        Category::factory(10)->create();
        Entity::factory(10)->create();

        IncidentState::factory()->create([
            'name' => 'Aberto',
            'is_active' => true,
            'terminates_incident' => false,
        ]);

        IncidentState::factory()->create([
            'name' => 'Em Curso',
            'is_active' => true,
            'terminates_incident' => false,
        ]);

        IncidentState::factory()->create([
            'name' => 'Fechado',
            'is_active' => true,
            'terminates_incident' => true
        ]);

        IncidentPriority::factory()->create([
            'name' => 'Baixa',
        ]);

        IncidentPriority::factory()->create([
            'name' => 'Normal',
        ]);

        IncidentPriority::factory()->create([
            'name' => 'Urgente',
        ]);

        IncidentPriority::factory()->create([
            'name' => 'Emergente',
        ]);

        Incident::factory(10)->create();

        IncidentEntitiesMapping::factory(10)->create();

    }
}
