<?php

namespace App\Http\OpenApi;

use OpenApi\Attributes as OA;

#[\Attribute(
    \Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE,
)]
class PaginationSchema extends OA\Schema
{
    public function __construct(
        string $schema,
        null|string $ref = null,
        null|string $type = null,
        null|array $properties = null,
        null|array $required = null,
    ) {
        parent::__construct(
            schema: $schema,
            required: array_merge($required ?? [], [
                'data',
                'current_page',
                'records_per_page',
                'last_page',
                'total',
            ]),
            properties: array_merge($properties ?? [], [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(
                        ref: $ref,
                        type: $type,
                    ),
                ),
                new OA\Property(
                    property: 'current_page',
                    type: 'integer',
                ),
                new OA\Property(
                    property: 'records_per_page',
                    type: 'integer',
                ),
                new OA\Property(
                    property: 'last_page',
                    type: 'integer',
                ),
                new OA\Property(
                    property: 'total',
                    type: 'integer',
                ),
            ]),
        );
    }
}
