<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Entity extends Model
{
    use HasFactory, SoftDeletes;

    public function entityType(): BelongsTo
    {
        return $this->belongsTo(EntityType::class);
    }

    protected $fillable = [
        'name',
        'description',
        'phone_contact',
        'email_contact',
        'address',
        'logo',
        'poc_name',
        'poc_phone',
        'poc_email',
        'entity_type_id',
    ];
}
