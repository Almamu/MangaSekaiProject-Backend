<?php

namespace App\Jobs;

use App\Models\CoverDownloadQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DownloadResources implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::debug('Running resource download job');

        CoverDownloadQueue::all()->each(function (CoverDownloadQueue $queue) {
            try {
                if ($queue->type === 'serie' && ! is_null($queue->serie)) {
                    Log::debug('Downloading cover for series: '.$queue->serie_id.': '.$queue->url);
                } elseif ($queue->type === 'staff' && ! is_null($queue->staff)) {
                    Log::debug('Downloading cover for staff: '.$queue->staff_id.': '.$queue->url);
                } else {
                    throw new \Exception('Unknown resource type: '.$queue->type);
                }

                // download the image
                $image = Http::accept('image/*')->get($queue->url)->throwIfStatus(fn ($status) => $status !== 200);
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
            } catch (\Exception $e) {
                Log::error($e->getMessage());
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
