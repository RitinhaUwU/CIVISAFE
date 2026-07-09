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
        $user = Role::firstOrCreate(['name' => RolesEnum::USER->value]);

        $admin->syncPermissions(
            collect(PermissionsEnum::cases())->pluck('value')
        );

        $user->syncPermissions([
            PermissionsEnum::USERS_VIEW_OWN->value,
            PermissionsEnum::USERS_UPDATE_OWN->value,
        ]);

        # Permissões dos Módulos

        Role::firstOrCreate(['name' => RolesEnum::MODULE_INCIDENTS->value])->syncPermissions([
            PermissionsEnum::INCIDENTS_LIST->value,
            PermissionsEnum::INCIDENTS_CREATE->value,
            PermissionsEnum::INCIDENTS_UPDATE->value,
            PermissionsEnum::INCIDENTS_DELETE->value,

            PermissionsEnum::INCIDENT_STATES_LIST->value,
            PermissionsEnum::INCIDENT_PRIORITIES_LIST->value,
            PermissionsEnum::INCIDENT_TYPES_LIST->value,
            PermissionsEnum::ENTITIES_LIST->value,
            PermissionsEnum::ENTITY_TYPES_LIST->value,
            PermissionsEnum::FACILITIES_LIST->value,
        ]);

        Role::firstOrCreate(['name' => RolesEnum::MODULE_VOLUNTEERS->value])->syncPermissions([
            PermissionsEnum::VOLUNTEERS_LIST->value,
            PermissionsEnum::VOLUNTEERS_CREATE->value,
            PermissionsEnum::VOLUNTEERS_UPDATE->value,
            PermissionsEnum::VOLUNTEERS_DELETE->value,

            PermissionsEnum::INCIDENTS_LIST->value,
        ]);

        Role::firstOrCreate(['name' => RolesEnum::MODULE_DONATIONS->value])->syncPermissions([
            PermissionsEnum::DONATION_LOG_LIST->value,
            PermissionsEnum::DONATION_LOG_CREATE->value,
            PermissionsEnum::DONATION_LOG_UPDATE->value,

            PermissionsEnum::DONATION_GOODS_TYPES_LIST->value,
            PermissionsEnum::FACILITIES_LIST->value,

            PermissionsEnum::DONATION_GOODS_TYPES_CREATE->value,
            PermissionsEnum::DONATION_GOODS_TYPES_UPDATE->value,
            PermissionsEnum::DONATION_GOODS_TYPES_DELETE->value,
        ]);
    }
}
