<?php

namespace App\Http\Mappers;

use App\Http\OpenApi\PaginationSchema;
use App\Models\Staff;
use OpenApi\Attributes as OA;

/**
 * @extends Mapper<Staff>
 */
#[OA\Schema(
    schema: 'Staff',
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
#[PaginationSchema(schema: 'StaffListPaginated', ref: '#/components/schemas/Staff')]
class StaffMapper extends Mapper
{
    public function __construct(
        private \Illuminate\Routing\UrlGenerator $urlGenerator,
    ) {
    }

    /**
     * @param Staff $data
     *
     * @return array<string, mixed>
     */
    public function map(mixed $data): array
    {
        return [
            'id' => $data->id,
            'name' => $data->name,
            'description' => $data->description,
            'image_url' => $this->urlGenerator->route('images.staff.avatar', ['staff' => $data->id]),
            'created_at' => $data->created_at,
            'updated_at' => $data->updated_at,
        ];
    }
}
