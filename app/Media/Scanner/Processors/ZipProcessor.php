<?php

namespace App\Media\Scanner\Processors;

use App\Media\Scanner\ExecutionContext;
use App\Models\ChaptersScan;
use App\Models\PagesScan;
use App\Models\SeriesScan;
use App\Services\ImageHandlerService;

class ZipProcessor implements Processor
{
    /**
     * @var array<string> List of zips already parsed to prevent reading them multiple times
     */
    private array $handles = [];

    public function __construct(private readonly ImageHandlerService $imageHandler) {}

    private function extractZipContainer(string $path): string
    {
        $parts = explode(':', $path);

        return reset($parts);
    }

    public function processable(ExecutionContext $context, string $path = ''): bool
    {
        $archive = $this->extractZipContainer($path);

        // validate that the archive's extension is valid and that it's not pointing to a directory
        // (this prevents using a zip as a library, but that'd be a massive headache right now)
        if (! $context->filesystem->exists($archive)) {
            return false;
        }

        // check if the archive is a directory and discard it
        $parentDirectories = $context->filesystem->directories(dirname($path), false);

        if (in_array(basename($archive), $parentDirectories)) {
            return false;
        }

        return pathinfo($archive, PATHINFO_EXTENSION) === 'zip';
    }

    public function process(ExecutionContext $context, string $path = ''): bool
    {
        // zip file found, should be a series only, add it to the list
        SeriesScan::updateOrInsert([
            'library_id' => $context->uuid, 'basepath' => $path,
        ]);

        return true;
    }

    public function serie(ExecutionContext $context, SeriesScan $serie): bool
    {
        $archive = $this->extractZipContainer($serie->basepath);
        $stream = $context->filesystem->readStream($archive);

        if (is_null($stream)) {
            return false;
        }

        /** @var array<string, ChaptersScan> $chapters */
        $chapters = [];

        // open the zip file
        $zip = new \PhpZip\ZipFile;
        $zip->openFromStream($stream);

        // get the number of files available on the zip and iterate through them
        foreach ($zip->getListFiles() as $entry) {
            // ignore __MACOSX entries
            if (str_starts_with($entry, '__MACOSX/')) {
                continue;
            }

            if (preg_match_all('/\d+/', $entry, $matches) < 2) {
                continue;
            }

            // this chapter number is only used to identify chapters that are already added
            // and not used as actual chapter names
            $chapterNumber = (string) ((float) $matches[0][0]);

            if (array_key_exists($chapterNumber, $chapters) === false) {
                // add chapter to the scan data
                $chapters[$chapterNumber] = ChaptersScan::updateOrCreate([
                    'series_scan_id' => $serie->id,
                    'basepath' => $serie->basepath.':'.dirname($entry),
                ]);
            }

            $mimeType = $this->imageHandler->guessMimeType($entry);

            if (! $this->imageHandler->isMimeTypeSupported($mimeType)) {
                continue;
            }

            PagesScan::updateOrInsert(
                ['chapters_scan_id' => $chapters[$chapterNumber]->id, 'path' => $serie->basepath.':'.$entry],
                ['mime_type' => $mimeType],
            );
        }

        // add zip to the list of parsed files
        $this->handles[] = $context->uuid.'://'.$archive;

        $zip->close();

        return true;
    }

    public function chapter(ExecutionContext $context, ChaptersScan $chapter): bool
    {
        $archive = $this->extractZipContainer($chapter->basepath);

        // this zip was already parsed before, so it can be ignored
        if (in_array($context->uuid.'://'.$archive, $this->handles)) {
            return true;
        }

        $stream = $context->filesystem->readStream($archive);

        if (is_null($stream)) {
            return false;
        }

        // open the zip file
        $zip = new \PhpZip\ZipFile;
        $zip->openFromStream($stream);

        foreach ($zip->getListFiles() as $entry) {
            // ignore __MACOSX entries
            if (str_starts_with($entry, '__MACOSX/')) {
                continue;
            }

            if (preg_match_all('/\d+/', $entry, $matches) == 0) {
                continue;
            }

            if (count($matches[0]) == 0) {
                continue;
            }

            $mimeType = $this->imageHandler->guessMimeType($entry);

            if (! $this->imageHandler->isMimeTypeSupported($mimeType)) {
                continue;
            }

            PagesScan::updateOrInsert(
                ['chapters_scan_id' => $chapter->id, 'path' => $chapter->basepath.':'.$entry],
                ['mime_type' => $mimeType],
            );
        }

        // add zip to the list of parsed files
        $this->handles[] = $context->uuid.'://'.$archive;

        $zip->close();

        return true;
    }

    public function page(ExecutionContext $context, PagesScan $page): bool
    {
        $archive = $this->extractZipContainer($page->path);

        // this zip was already parsed before, so it can be ignored
        return in_array($context->uuid.'://'.$archive, $this->handles);
    }
}
