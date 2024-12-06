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
                Log::debug('Downloading cover for series: '.$queue->serie_id.': '.$queue->url);
                // download the image
                $image = Http::accept('image/*')->get($queue->url)->throwIfStatus(fn ($status) => $status !== 200);
                // image downloaded, store it in the series
                $queue->serie->image = $image;
                $queue->serie->save();
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
