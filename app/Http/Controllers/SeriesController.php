<?php

namespace App\Http\Controllers;

use App\Models\Serie;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'series', description: 'Series')]
class SeriesController
{
    // @phpstan-ignore-next-line
    #[OA\Get(
        path: '/api/v1/series',
        operationId: 'listSeries',
        description: 'Full list of series available',
        security: [
            ['Token' => []],
        ],
        tags: ['series'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of series',
                content: new OA\JsonContent(
                    type: Serie::class,
                    collectionFormat: 'multi'
                )
            ),
        ]
    )]
    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Serie>
     */
    public function list(): \Illuminate\Database\Eloquent\Collection
    {
        return Serie::all();
    }

    public function cover(Serie $serie): \Illuminate\Http\Response
    {
        if (! $serie->hasImage()) {
            return response(status: 404);
        }

        return response($serie->image, 200, ['Content-Type' => $serie->mime_type]);
    }
}
