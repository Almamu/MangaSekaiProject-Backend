<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OpenApi\Attributes as OA;

#[OA\Schema(properties: [
    new OA\Property(property: 'id', type: 'integer'),
    new OA\Property(property: 'name', type: 'string'),
    new OA\Property(property: 'chapter_count', type: 'integer'),
    new OA\Property(property: 'pages_count', type: 'integer'),
    new OA\Property(property: 'description', type: 'string'),
    new OA\Property(property: 'synced', type: 'boolean'),
    new OA\Property(property: 'image_url', type: 'string'),
    new OA\Property(property: 'created_at', type: 'string'),
    new OA\Property(property: 'updated_at', type: 'string'),
])]
class Serie extends Model
{
    protected $hidden = ['image', 'mime_type'];

    protected $appends = ['image_url'];

    public function getImageUrlAttribute(): ?string
    {
        if (! $this->hasImage()) {
            return null;
        }

        return route('images.series.cover', ['serie' => $this->id]);
    }

    public function hasImage(): bool
    {
        return is_null($this->image) === false && is_null($this->mime_type) === false;
    }
}
