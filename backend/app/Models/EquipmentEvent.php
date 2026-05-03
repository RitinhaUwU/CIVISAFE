<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EquipmentEvent extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'equipment_id',
        'date',
        'event',
        'description',
    ];

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    protected function casts(): array
    {
        return [
            'date' => 'datetime',
        ];
    }
}
