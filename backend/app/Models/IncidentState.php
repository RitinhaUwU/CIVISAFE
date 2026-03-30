<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class IncidentState extends Model
{
    use HasFactory, SoftDeletes;

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'terminates_incident' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
