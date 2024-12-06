<?php

namespace App\Models;

use App\Http\OpenApi\PaginationSchema;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use OpenApi\Attributes as OA;

#[OA\Schema(properties: [
    new OA\Property(property: 'id', type: 'integer'),
    new OA\Property(property: 'name', type: 'string'),
    new OA\Property(property: 'description', type: 'string'),
    new OA\Property(property: 'image_url', type: 'string'),
    new OA\Property(property: 'created_at', type: 'string'),
    new OA\Property(property: 'updated_at', type: 'string'),
])]
#[OA\Schema(schema: 'StaffWithRole', properties: [
    new OA\Property(property: 'id', type: 'integer'),
    new OA\Property(property: 'name', type: 'string'),
    new OA\Property(property: 'description', type: 'string'),
    new OA\Property(property: 'image_url', type: 'string'),
    new OA\Property(property: 'created_at', type: 'string'),
    new OA\Property(property: 'updated_at', type: 'string'),
])]
#[PaginationSchema(schema: 'StaffListPaginated', type: Staff::class)]
/**
 * @mixin IdeHelperStaff
 */
class Staff extends Model
{
    /** @use HasFactory<\Database\Factories\StaffFactory> */
    use HasFactory;

    protected $hidden = ['pivot', 'mime_type', 'image', 'external_id', 'matcher'];

    protected $fillable = ['external_id', 'name', 'matcher', 'description', 'image', 'mime_type'];

    protected $appends = ['image_url'];

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
