<?php

namespace App\Media\Scanner\Processors;

use App\Media\Scanner\ExecutionContext;
use App\Models\ChaptersScan;
use App\Models\PagesScan;
use App\Models\SeriesScan;
use App\Services\ImageHandlerService;

interface Processor
{
    /**
     * Constructor for processors, cannot take any data in
     */
    public function __construct(ImageHandlerService $imageHandler);

    /**
     * Checks if the given path can be processed by this processor
     */
    public function processable(ExecutionContext $context, string $path = ''): bool;

    /**
     * Processes the given path in search for content
     */
    public function process(ExecutionContext $context): bool;

    /**
     * Processes the given series
     */
    public function serie(ExecutionContext $context, SeriesScan $serie): bool;

    /**
     * Processes the given chapter
     */
    public function chapter(ExecutionContext $context, ChaptersScan $chapter): bool;

    /**
     * Processes the given page
     */
    public function page(ExecutionContext $context, PagesScan $page): bool;
}
