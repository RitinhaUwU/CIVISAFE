<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class IncidentState extends Model
{
    use HasFactory, SoftDeletes;

    public $timestamps = false;

    protected $fillable = [
        'name',
        'description',
        'hex_color',
        'terminates_incident',
        'is_active'
    ];

    protected function casts(): array
    {
        return [
            'terminates_incident' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
