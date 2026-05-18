<?php

namespace Database\Seeders;

use App\Enums\PermissionsEnum;
use App\Enums\RolesEnum;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $admin = Role::firstOrCreate(['name' => RolesEnum::ADMIN->value]);
        $manager = Role::firstOrCreate(['name' => RolesEnum::MANAGER->value]);
        $user = Role::firstOrCreate(['name' => RolesEnum::USER->value]);

        $admin->syncPermissions(
            collect(PermissionsEnum::cases())->pluck('value')
        );

        $manager->syncPermissions([

        ]);

        $user->syncPermissions([
            PermissionsEnum::USERS_VIEW_OWN->value,
            PermissionsEnum::USERS_UPDATE_OWN->value,
            PermissionsEnum::VOLUNTEERS_LIST->value,
            PermissionsEnum::ENTITIES_LIST->value,
            PermissionsEnum::ENTITY_TYPES_LIST->value,
            PermissionsEnum::INCIDENTS_LIST->value,
            PermissionsEnum::INCIDENTS_CREATE->value,
            PermissionsEnum::INCIDENT_TYPES_LIST->value,
            PermissionsEnum::INCIDENT_STATES_LIST->value,
            PermissionsEnum::INCIDENT_PRIORITIES_LIST->value,
        ]);
    }
}
