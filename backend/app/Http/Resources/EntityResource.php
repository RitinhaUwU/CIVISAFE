<?php

namespace App\Http\Resources;

use App\Models\Entity;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;

class EntityResource extends JsonApiResource
{
    public $attributes = [
        'id',
        'name',
        'description',
        'phone_contact',
        'email_contact',
        'address',
        'logo',
        'poc_name',
        'poc_phone',
        'poc_email',
        'created_at',
        'updated_at',
    ];

    public function toLinks(Request $request)
    {
        return [
            'self' => url('/api/v1/entities/' . $this->id),
        ];
    }
}
