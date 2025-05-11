<?php

namespace App\Models;

use App\Http\OpenApi\PaginationSchema;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Routing\UrlGenerator;
use OpenApi\Attributes as OA;

#[OA\Schema(
    required: ['id', 'name', 'description', 'image_url', 'created_at', 'updated_at'],
    properties: [
        new OA\Property(
            property: 'id',
            type: 'integer',
        ),
        new OA\Property(
            property: 'name',
            type: 'string',
        ),
        new OA\Property(
            property: 'description',
            type: 'string',
        ),
        new OA\Property(
            property: 'image_url',
            type: 'string',
        ),
        new OA\Property(
            property: 'created_at',
            type: 'string',
            format: 'date-time',
        ),
        new OA\Property(
            property: 'updated_at',
            type: 'string',
            format: 'date-time',
        ),
    ],
)]
#[OA\Schema(
    schema: 'StaffWithRole',
    required: [
        'id',
        'name',
        'description',
        'image_url',
        'created_at',
        'updated_at',
    ],
    properties: [
        new OA\Property(
            property: 'id',
            type: 'integer',
        ),
        new OA\Property(
            property: 'name',
            type: 'string',
        ),
        new OA\Property(
            property: 'description',
            type: 'string',
        ),
        new OA\Property(
            property: 'image_url',
            type: 'string',
        ),
        new OA\Property(
            property: 'created_at',
            type: 'string',
            format: 'date-time',
        ),
        new OA\Property(
            property: 'updated_at',
            type: 'string',
            format: 'date-time',
        ),
    ],
)]
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

    /**
     * Create a new Eloquent model instance.
     *
     * @param array<mixed,mixed> $attributes
     */
    public function __construct(
        array $attributes,
        private readonly \Illuminate\Routing\UrlGenerator $urlGenerator,
    ) {
        parent::__construct($attributes);
    }

    // @phpstan-ignore missingType.generics (This doesn't really have generics but something we have is triggering it)
    protected function imageUrl(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(get: function () {
            if (!$this->hasImage()) {
                return null;
            }

            return $this->urlGenerator->route('images.staff.avatar', ['staff' => $this->id]);
        });
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
