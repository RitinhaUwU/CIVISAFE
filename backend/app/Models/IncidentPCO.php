<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class IncidentPCO extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'incident_pcos';

    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    protected $with = [
        'incident'
    ];

    protected $fillable = [
        'function_pco',
        'resp_pco',
        'category_pco',
        'contact1_pco',
        'contact2_pco',
        'localization_pco',
        'rob_pco',
        'srp_pco',
        'activation_pco_datetime',
        'start_pco_datetime',
        'end_pco_datetime',
        'incident_id',
    ];

    protected function casts(): array
    {
        return [
            'activation_pco_datetime' => 'datetime',
            'start_pco_datetime' => 'datetime',
            'end_pco_datetime' => 'datetime',
        ];
    }
}
