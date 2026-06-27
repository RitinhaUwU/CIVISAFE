<?php

namespace App\Http\Helpers;

use App\Models\Entity;
use App\Models\IncidentType;
use App\Models\IncidentState;
use App\Models\IncidentPriority;
use Carbon\Carbon;

class ActivityHelper{

    public static function transformActivityValues(array $values): array{
        $dateFields = [
            'start_datetime',
            'end_datetime',
            'activation_pco_datetime',
            'start_pco_datetime',
            'end_pco_datetime',
        ];

        foreach ($values as $field => &$value) {
            if ($value === null) {
                continue;
            }

            if (in_array($field, $dateFields) && !empty($value)) {
                $value = Carbon::parse($value)->format('d/m/Y H:i');
            }

            switch ($field) {
                case 'incident_type_id':
                    $value = IncidentType::withTrashed()->find($value)?->code ?? $value;
                    break;
                case 'incident_state_id':
                    $value = IncidentState::find($value)?->name ?? $value;
                    break;
                case 'incident_priority_id':
                    $value = IncidentPriority::find($value)?->description ?? $value;
                    break;
                case 'entity_id':
                    $value = Entity::find($value)?->name ?? $value;
                    break;
            }
        }

        return $values;
    }
}
