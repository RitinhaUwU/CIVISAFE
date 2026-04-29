<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class IncidentParty extends Model
{
    use SoftDeletes, HasFactory;


    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }
}
