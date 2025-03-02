<?php

namespace App\Media\Scanner\Processors;

use App\Media\Scanner\ExecutionContext;
use App\Models\ChaptersScan;
use App\Models\PagesScan;
use App\Models\SeriesScan;
use App\Services\ImageHandlerService;

class FolderProcessor implements Processor
{
    public function __construct(private readonly ImageHandlerService $imageHandler) {}

    public function processable(ExecutionContext $context, string $path = ''): bool
    {
        // paths that are containers should not be supported
        if (str_contains($path, ':')) {
            return false;
        }

        // root directories are parseable too
        if ($path === '' || $path === '/') {
            return true;
        }

        return $context->filesystem->exists($path);
    }

    public function process(ExecutionContext $context): bool
    {
        // add directories as we handle them
        foreach ($context->filesystem->directories('/') as $basepath) {
            SeriesScan::updateOrInsert(['library_id' => $context->uuid, 'basepath' => $basepath]);
        }

        // add found files as something else might handle them too
        foreach ($context->filesystem->files('/') as $basepath) {
            SeriesScan::updateOrInsert(['library_id' => $context->uuid, 'basepath' => $basepath]);
        }

        return true;
    }

    public function serie(ExecutionContext $context, SeriesScan $serie): bool
    {
        // add directories as we handle them
        foreach ($context->filesystem->directories($serie->basepath) as $x) {
            ChaptersScan::updateOrInsert(['series_scan_id' => $serie->id, 'basepath' => $x]);
        }

        // add found files as something else might handle them too
        foreach ($context->filesystem->files($serie->basepath) as $x) {
            ChaptersScan::updateOrInsert(['series_scan_id' => $serie->id, 'basepath' => $x]);
        }

        return true;
    }

    public function chapter(ExecutionContext $context, ChaptersScan $chapter): bool
    {
        foreach ($context->filesystem->files($chapter->basepath) as $file) {
            $stream = $context->filesystem->readStream($file);

            if (is_null($stream)) {
                continue;
            }

            $mimeType = $this->imageHandler->guessMimeType($file);

            if (! $this->imageHandler->isMimeTypeSupported($mimeType)) {
                fclose($stream);

                continue;
            }

            PagesScan::updateOrInsert(
                ['chapters_scan_id' => $chapter->id, 'path' => $file],
                ['mime_type' => $mimeType]
            );

            fclose($stream);
        }

        return true;
    }

    public function page(ExecutionContext $context, PagesScan $page): bool
    {
        return true;
    }
}
