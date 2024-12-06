<?php

namespace App\Http\Controllers;

use App\Http\OpenApi\OpenApiSpec;
use App\Jobs\ScanMedia;
use App\Models\Job;
use Illuminate\Support\Collection;
use OpenApi\Attributes as OA;

class MediaController
{
    #[OA\Post(
        path: '/api/v1/admin/media/refresh',
        operationId: 'queueRefreshMedia',
        description: 'Queues the media refresh job to be processed in the background.',
        security: OpenApiSpec::SECURITY,
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

    /**
     * @return Collection<int, string>
     */
    #[OA\Get(
        path: '/api/v1/admin/jobs/queue',
        operationId: 'getQueuedJobs',
        description: 'Gets the list of queued jobs that are running or waiting to be processed.',
        security: OpenApiSpec::SECURITY,
        tags: ['admin'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Queued jobs were retrieved successfully.',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(type: 'string'),
                )
            ),
        ]
    )]
    public function queue(): Collection
    {
        // TODO: FIND A BETTER WAY OF DOING THIS AS IT'S NOT TESTABLE
        return Job::all()->map(function (Job $job) {
            return class_basename($job->getName());
        });
    }
}
