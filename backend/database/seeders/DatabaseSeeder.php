<?php

namespace Database\Seeders;

use App\Enums\RolesEnum;
use App\Models\Donations\DonationContent;
use App\Models\Donations\DonationGoodsType;
use App\Models\Donations\DonationLog;
use App\Models\Entity;
use App\Models\Facility;
use App\Models\Incident;
use App\Models\IncidentParty;
use App\Models\IncidentPCO;
use App\Models\User;
use App\Models\Volunteer;
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
            DonationGoodsTypeSeeder::class
        ]);


        User::factory()->create([
            'name' => 'Utilizador Administrador',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'locked' => false,
        ])->assignRole(enum_value(RolesEnum::ADMIN));

        User::factory()->create([
            'name' => 'Utilizador Doações',
            'email' => 'doacoes@example.com',
            'locked' => false,
        ])->assignRole(enum_value(RolesEnum::DONATION_MANAGER));

        User::factory()->create([
            'name' => 'Utilizador Manager',
            'email' => 'manager@example.com',
            'password' => bcrypt('password'),
            'locked' => true,
        ])->assignRole(enum_value(RolesEnum::MANAGER));

        User::factory()->create([
            'name' => 'Utilizador User',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
            'locked' => false,
        ])->assignRole(enum_value(RolesEnum::USER));

        User::factory(100)->create()->each(function ($user) {
            $role = Role::inRandomOrder()->first();
            $user->assignRole($role->name);
        });
        Entity::factory(30)->create();
        Incident::factory(600)->create();
        Volunteer::factory(100)->create();
        Facility::factory(30)->create();
        IncidentPCO::factory(60)->create();

        for ($i = 0; $i <= 100; $i++) {
            Incident::factory()->create([
                'is_major' => false,
                'incident_id' => Incident::where(['is_major' => true])->inRandomOrder()->first()->id,
            ]);
        }

        IncidentParty::factory(500)->create();

        DonationLog::factory(70)->create();
        DonationContent::factory(150)->create();

    }
}
