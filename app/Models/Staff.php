<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'StaffReadDto', properties: [
    new OA\Property(property: 'id', type: 'integer'),
    new OA\Property(property: 'name', type: 'string'),
    new OA\Property(property: 'description', type: 'string'),
    new OA\Property(property: 'image_url', type: 'string'),
    new OA\Property(property: 'series', type: 'array', items: new OA\Items(ref: '#/components/schemas/SeriesListReadDto')),
    new OA\Property(property: 'created_at', type: 'string'),
    new OA\Property(property: 'updated_at', type: 'string'),
])]
#[OA\Schema(schema: 'StaffListReadDto', properties: [
    new OA\Property(property: 'id', type: 'integer'),
    new OA\Property(property: 'name', type: 'string'),
    new OA\Property(property: 'description', type: 'string'),
    new OA\Property(property: 'image_url', type: 'string'),
    new OA\Property(property: 'created_at', type: 'string'),
    new OA\Property(property: 'updated_at', type: 'string'),
])]
class Staff extends Model
{
    /** @use HasFactory<\Database\Factories\StaffFactory> */
    use HasFactory;

    protected $hidden = ['pivot', 'mime_type', 'image'];

    protected $appends = ['image_url', 'series'];

    public function getImageUrlAttribute(): ?string
    {
        if (! $this->hasImage()) {
            return null;
        }

        return route('images.staff.avatar', ['staff' => $this->id]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Serie>
     */
    public function getSeriesAttribute(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->series()->get();
    }

    /**
     * @return BelongsToMany<Serie, $this>
     */
    public function series(): BelongsToMany
    {
        return $this->belongsToMany(Serie::class);
    }

    public function hasImage(): bool
    {
        return is_null($this->image) === false && is_null($this->mime_type) === false;
    }
}
