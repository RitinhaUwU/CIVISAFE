<?php

namespace App\Http\Resources;

use App\Models\Entity;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Entity */
class EntityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'phone_contact' => $this->phone_contact,
            'email_contact' => $this->email_contact,
            'address' => $this->address,
            'logo' => $this->logo,
            'poc_name' => $this->poc_name,
            'poc_phone' => $this->poc_phone,
            'poc_email' => $this->poc_email,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
