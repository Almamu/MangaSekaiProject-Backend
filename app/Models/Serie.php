<?php

namespace App\Models;

use App\Http\OpenApi\PaginationSchema;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'Series', properties: [
    new OA\Property(property: 'id', type: 'integer'),
    new OA\Property(property: 'name', type: 'string'),
    new OA\Property(property: 'chapter_count', type: 'integer'),
    new OA\Property(property: 'pages_count', type: 'integer'),
    new OA\Property(property: 'description', type: 'string'),
    new OA\Property(property: 'synced', type: 'boolean'),
    new OA\Property(property: 'image_url', type: 'string'),
    new OA\Property(property: 'genres', type: 'array', items: new OA\Items(type: Genre::class)),
    new OA\Property(property: 'staff', type: 'array', items: new OA\Items(ref: '#/components/schemas/StaffWithRole')),
    new OA\Property(property: 'created_at', type: 'string'),
    new OA\Property(property: 'updated_at', type: 'string'),
])]
#[OA\Schema(schema: 'SeriesListItem', properties: [
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
#[PaginationSchema(schema: 'SeriesListPaginated', ref: '#/components/schemas/SeriesListItem')]
class Serie extends Model
{
    /** @use HasFactory<\Database\Factories\SerieFactory> */
    use HasFactory;

    protected $hidden = ['image', 'mime_type', 'genres', 'staff', 'path'];

    protected $appends = ['image_url', 'genres', 'staff'];

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
        return $this->belongsToMany(Staff::class)->withPivot('role');
    }

    public function hasImage(): bool
    {
        return is_null($this->image) === false && is_null($this->mime_type) === false;
    }

    public static function findOrCreate(string $name): self
    {
        return self::query()->firstOrCreate(['path' => $name], [
            'name' => $name,
            'chapter_count' => 0,
            'pages_count' => 0,
            'description' => '',
            'path' => $name,
        ]);
    }
}
