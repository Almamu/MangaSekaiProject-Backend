<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\ThrottlesExceptions;
use Illuminate\Queue\Middleware\WithoutOverlapping;

class ScanMedia implements ShouldQueue
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
        //
    }

    /**
     * @return array<WithoutOverlapping|ThrottlesExceptions>
     */
    public function middleware(): array
    {
        return [
            new WithoutOverlapping('scan-media'),
            (new ThrottlesExceptions(3, 5 * 60))->backoff(5),
        ];
    }
}
