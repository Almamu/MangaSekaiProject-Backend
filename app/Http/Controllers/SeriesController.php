<?php

namespace App\Http\Controllers;

use App\Http\OpenApi\OpenApiSpec;
use App\Http\Responses\PaginatedResponse;
use App\Http\Responses\PaginatedResponseTrait;
use App\Media\Storage\Storage;
use App\Models\Chapter;
use App\Models\Page;
use App\Models\Serie;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'series', description: 'Series')]
class SeriesController
{
    use PaginatedResponseTrait;

    public function __construct(private readonly Storage $storage, private \Illuminate\Contracts\Routing\ResponseFactory $responseFactory) {}

    #[OA\Get(
        path: '/api/v1/series',
        operationId: 'listSeries',
        description: 'Full list of series available',
        security: OpenApiSpec::SECURITY,
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

    /**
     * @return \Illuminate\Support\Collection<int, Serie>
     */
    #[OA\Get(
        path: '/api/v1/series/recentlyUpdated',
        operationId: 'recentlyUpdatedSeries',
        description: 'List of the last 10 series updated',
        security: OpenApiSpec::SECURITY,
        tags: ['series'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of series',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/SeriesListItem'),
                )
            ),
        ]
    )]
    public function recentlyUpdated(): \Illuminate\Support\Collection
    {
        return Serie::query()->orderBy('updated_at', 'desc')->take(10)->get();
    }

    #[OA\Get(
        path: '/api/v1/series/{serieId}',
        operationId: 'getSerieById',
        description: 'Full info for the given series',
        security: OpenApiSpec::SECURITY,
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
        return $serie->makeVisible(['genres', 'staff', 'blocked_fields']);
    }

    #[OA\Get(
        path: '/api/v1/series/{serieId}/chapters',
        operationId: 'getChaptersForSeries',
        description: 'Paginated list of all the available chapters for the given series',
        security: OpenApiSpec::SECURITY,
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
    public function chapters(Serie $serie, \Illuminate\Http\Request $request): PaginatedResponse
    {
        $perPage = $request->integer('perPage', OpenApiSpec::RECORDS_PER_PAGE);
        $page = $request->integer('page', 1);
        $pagination = $serie->chapters()->orderBy('number')->paginate($perPage, page: $page);

        return new PaginatedResponse($pagination);
    }

    /**
     * @return \Illuminate\Support\Collection<(int|string), mixed>
     */
    #[OA\Get(
        path: '/api/v1/series/{serieId}/chapters/{chapterId}/pages',
        operationId: 'getPagesForChapter',
        description: 'List of pages for the given chapter',
        security: OpenApiSpec::SECURITY,
        tags: ['series'],
        parameters: [
            new OA\Parameter(name: 'serieId', description: 'Serie ID', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'chapterId', description: 'Chapter ID', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of pages',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(type: 'string'),
                )
            ),
        ]
    )]
    public function pages(Serie $serie, Chapter $chapter): \Illuminate\Support\Collection
    {
        return $chapter->pages()->orderBy('number')->get()->pluck('public_url');
    }

    public function page(Page $page): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        return $this->responseFactory->stream(function () use ($page): void {
            // read the file in blocks of 4096 bytes and output it to the client
            $this->storage->open($page->path, function ($stream): void {
                while (feof($stream) === false) {
                    echo fread($stream, 8192);
                    ob_flush();
                    flush();
                }
            });
        }, 200, [
            'Content-Type' => $page->mime_type,
        ]);
    }

    public function cover(Serie $serie): \Illuminate\Http\Response
    {
        if (! $serie->hasImage()) {
            return $this->responseFactory->make(status: 404);
        }

        // @phpstan-ignore-next-line this one is not a real issue because hasImage already checks for nulls before, so this can only be string
        return $this->responseFactory->make($serie->image, 200, ['Content-Type' => $serie->mime_type]);
    }
}
