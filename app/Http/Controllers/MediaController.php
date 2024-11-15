<?php

namespace App\Http\Controllers;

use App\Jobs\ScanMedia;
use OpenApi\Attributes as OA;

class MediaController
{
    #[OA\Post(
        path: '/api/v1/admin/media/refresh',
        operationId: 'queueRefreshMedia',
        description: 'Queues the media refresh job to be processed in the background.',
        security: [
            ['Token' => []],
        ],
        tags: ['admin'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Job execution was queued successfully.'
            ),
        ]
    )]
    public function refresh(): void
    {
        ScanMedia::dispatch();
    }
}
