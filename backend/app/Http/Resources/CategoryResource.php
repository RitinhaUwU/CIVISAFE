<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;

class CategoryResource extends JsonApiResource
{
    /**
     * The resource's attributes.
     */
    public $attributes = [
        'code',
        'name',
        'description',
        'is_active',
        'created_at',
        'updated_at',
    ];

    public function toLinks(Request $request)
    {
        return [
            'self' => url('/api/v1/categories/' . $this->code),
        ];
    }
}
