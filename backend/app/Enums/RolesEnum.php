<?php

namespace App\Enums;

enum RolesEnum: string
{
    # https://spatie.be/docs/laravel-permission/v7/basic-usage/enums

    case ADMIN = 'admin';
    case USER = 'user';


    # Acesso a Módulos
    case MODULE_INCIDENTS = 'module_incidents';
    case MODULE_VOLUNTEERS = 'module_volunteers';
    case MODULE_DONATIONS = 'module_donations';


    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'Administrador',
            self::USER => 'Utilizador',

            self::MODULE_INCIDENTS => 'Módulo de Gestão de Ocorrências',
            self::MODULE_VOLUNTEERS => 'Módulo de Gestão de Voluntários',
            self::MODULE_DONATIONS => 'Módulo de Gestão de Doações',
        };
    }
}
