<?php

namespace App\Enums;

enum PermissionsEnum: string
{
    # https://spatie.be/docs/laravel-permission/v7/basic-usage/enums

    # USERS
    case USERS_VIEW_ANY = 'USERS_VIEW_ANY';
    case USERS_VIEW_OWN = 'USERS_VIEW_OWN';
    case USERS_CREATE = 'USERS_CREATE';
    case USERS_UPDATE_ANY = 'USERS_UPDATE_ANY';
    case USERS_UPDATE_OWN = 'USERS_UPDATE_OWN';
    case USERS_DELETE = 'USERS_DELETE';

    # VOLUNTEERS
    case VOLUNTEERS_LIST = 'VOLUNTEERS_LIST';
    case VOLUNTEERS_CREATE = 'VOLUNTEERS_CREATE';
    case VOLUNTEERS_UPDATE = 'VOLUNTEERS_UPDATE';
    case VOLUNTEERS_DELETE = 'VOLUNTEERS_DELETE';

    # ENTITIES
    case ENTITIES_LIST = 'ENTITIES_LIST';
    case ENTITIES_CREATE = 'ENTITIES_CREATE';
    case ENTITIES_UPDATE = 'ENTITIES_UPDATE';
    case ENTITIES_DELETE = 'ENTITIES_DELETE';

    # ENTITY TYPES
    case ENTITY_TYPES_LIST = 'ENTITY_TYPES_LIST';
    case ENTITY_TYPES_CREATE = 'ENTITY_TYPES_CREATE';
    case ENTITY_TYPES_UPDATE = 'ENTITY_TYPES_UPDATE';
    case ENTITY_TYPES_DELETE = 'ENTITY_TYPES_DELETE';

    # INCIDENTS
    case INCIDENTS_LIST = 'INCIDENTS_LIST';
    case INCIDENTS_CREATE = 'INCIDENTS_CREATE';
    case INCIDENTS_UPDATE = 'INCIDENTS_UPDATE';
    case INCIDENTS_DELETE = 'INCIDENTS_DELETE';

    # INCIDENT TYPES
    case INCIDENT_TYPES_LIST = 'INCIDENT_TYPES_LIST';
    case INCIDENT_TYPES_CREATE = 'INCIDENT_TYPES_CREATE';
    case INCIDENT_TYPES_UPDATE = 'INCIDENT_TYPES_UPDATE';
    case INCIDENT_TYPES_DELETE = 'INCIDENT_TYPES_DELETE';

    # INCIDENT STATES
    case INCIDENT_STATES_LIST = 'INCIDENT_STATES_LIST';
    case INCIDENT_STATES_CREATE = 'INCIDENT_STATES_CREATE';
    case INCIDENT_STATES_UPDATE = 'INCIDENT_STATES_UPDATE';
    case INCIDENT_STATES_DELETE = 'INCIDENT_STATES_DELETE';

    # INCIDENT PRIORITIES
    case INCIDENT_PRIORITIES_LIST = 'INCIDENT_PRIORITIES_LIST';
    case INCIDENT_PRIORITIES_CREATE = 'INCIDENT_PRIORITIES_CREATE';
    case INCIDENT_PRIORITIES_UPDATE = 'INCIDENT_PRIORITIES_UPDATE';
    case INCIDENT_PRIORITIES_DELETE = 'INCIDENT_PRIORITIES_DELETE';


    public function label(): string
    {
        return match ($this) {
            # USERS
            self::USERS_VIEW_ANY => 'Ver Utilizadores',
            self::USERS_VIEW_OWN => 'Ver o meu utilizador',
            self::USERS_CREATE => 'Criar Utilizadores',
            self::USERS_UPDATE_ANY => 'Editar Utilizadores',
            self::USERS_UPDATE_OWN => 'Editar o meu utilizador',
            self::USERS_DELETE => 'Eliminar Utilizadores',
            # VOLUNTEERS
            self::VOLUNTEERS_LIST => 'Ver Voluntários',
            self::VOLUNTEERS_CREATE => 'Criar Voluntários',
            self::VOLUNTEERS_UPDATE => 'Editar Voluntários',
            self::VOLUNTEERS_DELETE => 'Eliminar Voluntários',
            # ENTITIES
            self::ENTITIES_LIST => 'Ver Entidades',
            self::ENTITIES_CREATE => 'Criar Entidades',
            self::ENTITIES_UPDATE => 'Editar Entidades',
            self::ENTITIES_DELETE => 'Eliminar Entidades',
            # ENTITY TYPES
            self::ENTITY_TYPES_LIST => 'Ver Tipos de Entidade',
            self::ENTITY_TYPES_CREATE => 'Criar Tipos de Entidade',
            self::ENTITY_TYPES_UPDATE => 'Editar Tipos de Entidade',
            self::ENTITY_TYPES_DELETE => 'Eliminar Tipos de Entidade',
            # INCIDENTS
            self::INCIDENTS_LIST => 'Ver Incidentes',
            self::INCIDENTS_CREATE => 'Criar Incidentes',
            self::INCIDENTS_UPDATE => 'Editar Incidentes',
            self::INCIDENTS_DELETE => 'Eliminar Incidentes',
            # INCIDENT TYPES
            self::INCIDENT_TYPES_LIST => 'Ver Tipos de Incidente',
            self::INCIDENT_TYPES_CREATE => 'Criar Tipos de Incidente',
            self::INCIDENT_TYPES_UPDATE => 'Editar Tipos de Incidente',
            self::INCIDENT_TYPES_DELETE => 'Eliminar Tipos de Incidente',
            # INCIDENT STATES
            self::INCIDENT_STATES_LIST => 'Ver Estados de Incidente',
            self::INCIDENT_STATES_CREATE => 'Criar Estados de Incidente',
            self::INCIDENT_STATES_UPDATE => 'Editar Estados de Incidente',
            self::INCIDENT_STATES_DELETE => 'Eliminar Estados de Incidente',
            # INCIDENT PRIORITIES
            self::INCIDENT_PRIORITIES_LIST => 'Ver Prioridades de Incidente',
            self::INCIDENT_PRIORITIES_CREATE => 'Criar Prioridades de Incidente',
            self::INCIDENT_PRIORITIES_UPDATE => 'Editar Prioridades de Incidente',
            self::INCIDENT_PRIORITIES_DELETE => 'Eliminar Prioridades de Incidente',
        };
    }
}
