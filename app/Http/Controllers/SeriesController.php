<?php

namespace App\Http\Controllers;

use App\Http\OpenApi\OpenApiSpec;
use App\Http\Responses\PaginatedResponse;
use App\Http\Responses\PaginatedResponseTrait;
use App\Models\Serie;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'series', description: 'Series')]
class SeriesController
{
    use PaginatedResponseTrait;

    #[OA\Get(
        path: '/api/v1/series',
        operationId: 'listSeries',
        description: 'Full list of series available',
        security: [
            ['Token' => []],
        ],
        tags: ['series'],
        parameters: [
            new OA\Parameter(name: 'page', description: 'Page number', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'perPage', description: 'Number of items per page', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of series',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/SeriesListPaginated',
                )
            ),
        ]
    )]
    public function list(): PaginatedResponse
    {
        return $this->paginate(
            Serie::query()
        );
    }

    #[OA\Get(
        path: '/api/v1/series/{serieId}',
        operationId: 'getSerieById',
        description: 'Full info for the given series',
        security: [
            ['Token' => []],
        ],
        tags: ['series'],
        parameters: [
            new OA\Parameter(name: 'serieId', description: 'Serie ID', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Series information',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/Series'
                )
            ),
        ]
    )]
    public function get(Serie $serie): Serie
    {
        return $serie->makeVisible(['genres', 'staff']);
    }

    #[OA\Get(
        path: '/api/v1/series/{serieId}/chapters',
        operationId: 'getChaptersForSeries',
        description: 'Paginated list of all the available chapters for the given series',
        security: [
            ['Token' => []],
        ],
        tags: ['series'],
        parameters: [
            new OA\Parameter(name: 'serieId', description: 'Serie ID', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'page', description: 'Page number', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'perPage', description: 'Number of items per page', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Chapter information',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/ChapterListPaginated',
                )
            ),
        ]
    )]
    public function chapters(Serie $serie): PaginatedResponse
    {
        $perPage = request()->integer('perPage', OpenApiSpec::RECORDS_PER_PAGE);
        $page = request()->integer('page', 1);
        $pagination = $serie->chapters()->orderBy('number')->paginate($perPage, page: $page);

        return new PaginatedResponse($pagination);
    }

    public function cover(Serie $serie): \Illuminate\Http\Response
    {
        if (! $serie->hasImage()) {
            return response(status: 404);
        }

        return response($serie->image, 200, ['Content-Type' => $serie->mime_type]);
    }
}
