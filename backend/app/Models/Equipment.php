<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Equipment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'equipment_type_id',
        'entity_id',
        'facility_id',
        'custom_fields_data',
        'image',
    ];

    public function equipmentType(): BelongsTo
    {
        return $this->belongsTo(EquipmentType::class);
    }

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    protected function casts(): array
    {
        return [
            'custom_fields_data' => 'array',
        ];
    }
}
