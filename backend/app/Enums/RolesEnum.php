<?php

namespace App\Enums;

enum RolesEnum: string
{
    # https://spatie.be/docs/laravel-permission/v7/basic-usage/enums

    case ADMIN = 'admin';
    case MANAGER = 'manager';
    case USER = 'user';

    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'Administrador',
            self::MANAGER => 'Gestor',
            self::USER => 'Utilizador',
        };
    }
}
