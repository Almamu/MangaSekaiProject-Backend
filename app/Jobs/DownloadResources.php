<?php

namespace App\Jobs;

use App\Models\CoverDownloadQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Http;

class DownloadResources implements ShouldQueue
{
    use Queueable;

    /**
     * Execute the job.
     */
    public function handle(\Illuminate\Log\LogManager $logManager): void
    {
        $logManager->debug('Running resource download job');

        CoverDownloadQueue::all()->each(function (CoverDownloadQueue $queue) use ($logManager): void {
            try {
                if ($queue->type === 'serie' && ! is_null($queue->serie)) {
                    $logManager->debug('Downloading cover for series: '.$queue->serie_id.': '.$queue->url);
                } elseif ($queue->type === 'staff' && ! is_null($queue->staff)) {
                    $logManager->debug('Downloading cover for staff: '.$queue->staff_id.': '.$queue->url);
                } else {
                    throw new \Exception('Unknown resource type: '.$queue->type);
                }

                // download the image
                $image = Http::accept('image/*')->get($queue->url)->throwIfStatus(fn ($status): bool => $status !== 200);
                $mime_type = $image->getHeader('Content-Type')[0] ?? '';

                // image downloaded, store it where it belongs
                if ($queue->type === 'serie') {
                    $queue->serie->image = $image;
                    $queue->serie->mime_type = $mime_type;
                    $queue->serie->save();

                } elseif ($queue->type === 'staff') {
                    $queue->staff->image = $image;
                    $queue->staff->mime_type = $mime_type;
                    $queue->staff->save();
                }

                // the queue entry can be removed
                $queue->delete();
            } catch (\Exception $exception) {
                $logManager->error($exception->getMessage());
            }
        });
    }

    /**
     * @return WithoutOverlapping[]
     */
    public function middleware(): array
    {
        return [
            new WithoutOverlapping('download-resources'),
        ];
    }
}
