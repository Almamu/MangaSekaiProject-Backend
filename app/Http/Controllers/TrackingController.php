<?php

namespace App\Http\Controllers;

use App\Http\OpenApi\OpenApiSpec;
use App\Http\Requests\CookieRequest;
use App\Models\Chapter;
use App\Models\ReadHistory;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'tracking', description: 'Read history and tracking')]
#[OA\Schema(
    schema: 'TrackingCookie',
    required: ['page'],
    properties: [
        new OA\Property(
            property: 'page',
            type: 'integer',
        ),
    ],
)]
class TrackingController
{
    public function __construct(
        private \Illuminate\Contracts\Auth\Guard $guard,
    ) {
    }

    #[OA\Post(
        path: '/api/v1/tracking/cookie/{chapter_id}',
        operationId: 'generateTrackingCookieForChapter',
        description: 'Generates a tracking cookie for a chapter, used to keep track of the current read session.',
        security: OpenApiSpec::SECURITY,
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/TrackingCookie'),
        ),
        tags: ['tracking'],
        parameters: [
            new OA\Parameter(
                name: 'chapter_id',
                description: 'Chapter ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tracking cookie generated successfully',
                content: new OA\JsonContent(type: 'integer'),
            ),
        ],
    )]
    public function cookie(Chapter $chapter, CookieRequest $request): int
    {
        $historyEntry = ReadHistory::where('user_id', $this->guard->id())
            ->where('chapter_id', $chapter->id)
            ->where('updated_at', '>=', now()->subMinutes(15))
            ->first();

        // only add a new cookie if that chapter has been more than 15 minutes without activity
        if ($historyEntry === null) {
            $historyEntry = ReadHistory::create([
                'user_id' => $this->guard->id(),
                'chapter_id' => $chapter->id,
                'page_start' => min($chapter->pages_count, $request->page),
                'page_end' => min($chapter->pages_count, $request->page),
            ]);
        }

        return $historyEntry->id;
    }

    #[OA\Post(
        path: '/api/v1/tracking/refresh/{cookie_id}',
        operationId: 'refreshTrackingCookie',
        description: 'Refreshes the tracking cookie for the current user.',
        security: OpenApiSpec::SECURITY,
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/TrackingCookie'),
        ),
        tags: ['tracking'],
        parameters: [
            new OA\Parameter(
                name: 'cookie_id',
                description: 'Tracking cookie ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tracking cookie refreshed successfully',
            ),
        ],
    )]
    public function refresh(ReadHistory $cookie, CookieRequest $request): void
    {
        $validated = $request->validated();

        if ($validated['page'] > $cookie->page_end) {
            $cookie->page_end = min($cookie->chapter->pages_count, $request->page);
            $cookie->save();
        }
    }
}
