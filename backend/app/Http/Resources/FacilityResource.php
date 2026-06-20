<?php

namespace App\Http\Resources;

use App\Models\Facility;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Storage;

/** @mixin Facility */
class FacilityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'address' => $this->address,
            'email' => $this->email,
            'contact' => $this->contact,
            'image' => $this->image !== null ? Storage::disk('data_bucket')->temporaryUrl($this->image, now()->addMinutes(10)) : null,
            'description' => $this->description,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'documents' => $this->getMedia('documents')->map(fn($media) => [
                'id'        => $media->id,
                'name'      => $media->file_name,
                'mime_type' => $media->mime_type,
                'size'      => $media->size,
                // URL temporário de 30 min para download (funciona com MinIO privado)
                'url'       => $media->getTemporaryUrl(now()->addMinutes(30)),
            ]),
        ];
    }
}
