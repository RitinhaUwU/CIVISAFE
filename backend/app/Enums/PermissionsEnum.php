<?php

namespace App\Enums;

enum PermissionsEnum: string
{
    # https://spatie.be/docs/laravel-permission/v7/basic-usage/enums

    case CATEGORIES_LIST = 'CATEGORIES_LIST';
    case CATEGORIES_CREATE = 'CATEGORIES_CREATE';
    case CATEGORIES_UPDATE = 'CATEGORIES_UPDATE';
    case CATEGORIES_DELETE = 'CATEGORIES_DELETE';

    case ENTITIES_LIST = 'ENTITIES_LIST';
    case ENTITIES_CREATE = 'ENTITIES_CREATE';
    case ENTITIES_UPDATE = 'ENTITIES_UPDATE';
    case ENTITIES_DELETE = 'ENTITIES_DELETE';


    public function label(): string
    {
        return match ($this) {
            self::CATEGORIES_LIST => 'Ver Categorias',
            self::CATEGORIES_CREATE => 'Criar Categorias',
            self::CATEGORIES_UPDATE => 'Editar Categorias',
            self::CATEGORIES_DELETE => 'Eliminar Categorias',

            self::ENTITIES_LIST => 'Ver Entidades',
            self::ENTITIES_CREATE => 'Criar Entidades',
            self::ENTITIES_UPDATE => 'Editar Entidades',
            self::ENTITIES_DELETE => 'Eliminar Entidades',
        };
    }

}
