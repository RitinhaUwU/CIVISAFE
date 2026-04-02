<?php

namespace Database\Seeders;

use App\Enums\RolesEnum;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use function Illuminate\Support\enum_value;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::create(['name' => enum_value(RolesEnum::ADMIN)]);
        Role::create(['name' => enum_value(RolesEnum::MANAGER)]);
        Role::create(['name' => enum_value(RolesEnum::USER)]);
    }
}
