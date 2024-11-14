<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'SeriesReadDto', properties: [
    new OA\Property(property: 'id', type: 'integer'),
    new OA\Property(property: 'name', type: 'string'),
    new OA\Property(property: 'chapter_count', type: 'integer'),
    new OA\Property(property: 'pages_count', type: 'integer'),
    new OA\Property(property: 'description', type: 'string'),
    new OA\Property(property: 'synced', type: 'boolean'),
    new OA\Property(property: 'image_url', type: 'string'),
    new OA\Property(property: 'genres', type: Genre::class, collectionFormat: 'multi'),
    new OA\Property(property: 'chapters', type: Chapter::class, collectionFormat: 'multi'),
    new OA\Property(property: 'staff', type: Staff::class, collectionFormat: 'multi'),
    new OA\Property(property: 'created_at', type: 'string'),
    new OA\Property(property: 'updated_at', type: 'string'),
])]
#[OA\Schema(schema: 'SeriesListReadDto', properties: [
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
    /** @use HasFactory<\Database\Factories\SerieFactory> */
    use HasFactory;

    protected $hidden = ['image', 'mime_type'];

    protected $appends = ['image_url', 'genres', 'chapters', 'staff'];

    public function getImageUrlAttribute(): ?string
    {
        if (! $this->hasImage()) {
            return null;
        }

        return route('images.series.cover', ['serie' => $this->id]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Genre>
     */
    public function getGenresAttribute(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->genres()->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Chapter>
     */
    public function getChaptersAttribute(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->chapters()->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Staff>
     */
    public function getStaffAttribute(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->staff()->get();
    }

    /**
     * @return BelongsToMany<Genre, $this>
     */
    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<Chapter, $this>
     */
    public function chapters(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Chapter::class);
    }

    /**
     * @return BelongsToMany<Staff, $this>
     */
    public function staff(): BelongsToMany
    {
        return $this->belongsToMany(Staff::class);
    }

    public function hasImage(): bool
    {
        return is_null($this->image) === false && is_null($this->mime_type) === false;
    }
}
