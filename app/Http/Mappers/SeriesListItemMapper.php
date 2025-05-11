<?php

namespace App\Http\Mappers;

use App\Http\OpenApi\PaginationSchema;
use App\Models\Serie;
use OpenApi\Attributes as OA;

/**
 * @extends Mapper<Serie>
 */

#[OA\Schema(
    schema: 'SeriesListItem',
    required: [
        'id',
        'name',
        'chapter_count',
        'pages_count',
        'description',
        'synced',
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
            property: 'chapter_count',
            type: 'integer',
        ),
        new OA\Property(
            property: 'pages_count',
            type: 'integer',
        ),
        new OA\Property(
            property: 'description',
            type: 'string',
        ),
        new OA\Property(
            property: 'synced',
            type: 'boolean',
        ),
        new OA\Property(
            property: 'image_url',
            type: 'string',
            nullable: true,
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
#[PaginationSchema(schema: 'SeriesListPaginated', ref: '#/components/schemas/SeriesListItem')]
class SeriesListItemMapper extends Mapper
{
    public function __construct(
        private \Illuminate\Routing\UrlGenerator $urlGenerator,
    ) {
    }

    /**
     * @param Serie $data
     *
     * @return array<string, mixed>
     */
    public function map(mixed $data): array
    {
        return [
            'id' => $data->id,
            'name' => $data->name,
            'chapter_count' => $data->chapter_count,
            'pages_count' => $data->pages_count,
            'description' => $data->description,
            'synced' => $data->synced,
            'image_url' => $data->hasImage() ? $this->urlGenerator->route('images.series.cover', [$data->id]) : null,
            'created_at' => $data->created_at,
            'updated_at' => $data->updated_at,
        ];
    }
}
