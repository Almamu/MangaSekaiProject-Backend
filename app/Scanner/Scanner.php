<?php

namespace App\Scanner;

use App\Models\Chapter;
use App\Models\ChaptersScan;
use App\Models\PagesScan;
use App\Models\Serie;
use App\Models\SeriesScan;
use App\Scanner\Processors\Processor;
use App\ScannerDirs;
use App\Services\ImageHandlerService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class Scanner
{
    /**
     * @var Processor[] Processor instances to scan
     */
    private array $instances = [];

    /**
     * @param  array<class-string<Processor>>  $processors
     */
    public function __construct(private readonly array $processors, ImageHandlerService $imageHandlerService)
    {
        $this->instances = array_map(fn (string $class) => new $class($imageHandlerService), $this->processors);
    }

    /**
     * Performs the second pass on the filesystem. Takes care of updating all the chapters in the database
     *
     * @param  callable(SeriesScan): void  $serieCallback
     * @param  callable(ChaptersScan): void  $chapterCallback
     * @param  callable(PagesScan): void  $pageCallback
     *
     * @throws \Throwable
     */
    public function scan(callable $serieCallback, callable $chapterCallback, callable $pageCallback): void
    {
        foreach (ScannerDirs::$dirs as $storage) {
            Log::info('Scanning storage '.$storage.' for new manga');

            $context = new ExecutionContext($storage, Storage::disk($storage), $this);

            $this->process($context);
        }

        // clean the pages table so it is re-created
        PagesScan::query()->delete();

        // go through every series and remove the ones we do not find in processors anymore
        SeriesScan::query()->chunk(100, function ($series) use ($serieCallback) {
            foreach ($series as $serie) {
                $context = new ExecutionContext($serie->library_id, Storage::disk($serie->library_id), $this);

                Log::info('Processing '.$serie->library_id.'://'.$serie->basepath);

                if ($this->processSeries($context, $serie)) {
                    $serieCallback($serie);
                } else {
                    // not processable, remove from the database
                    if (! is_null($serie->serie_id)) {
                        Log::info('Deleting '.$serie->library_id.'://'.$serie->basepath.' association on the database');
                    }

                    $serie->delete();
                }
            }
        });

        // series that do not have any scan record can be removed
        Serie::whereNotIn('id', SeriesScan::query()->select('serie_id AS id'))->delete();

        // finally go through every chapter scanned
        // and create the corresponding records in the database
        ChaptersScan::with('serie')
            ->chunk(100, function ($chapters) use ($chapterCallback) {
                foreach ($chapters as $chapter) {
                    $library_id = $chapter->serie->library_id;
                    $context = new ExecutionContext($library_id, Storage::disk($library_id), $this);

                    Log::info('Processing '.$chapter->basepath.' on library '.$library_id);

                    if ($this->processChapter($context, $chapter)) {
                        $chapterCallback($chapter);
                    } else {
                        // chapter could not be processed, that means whatever was supporting it is not there
                        // (either a file/folder went missing or the processor is not supporting it anymore)
                        $chapter->delete();
                    }
                }
            });

        // same applies for chapters, the ones that do not have a chapter scan can be removed
        Chapter::whereNotIn('id', ChaptersScan::query()->select('chapter_id'))->delete();

        // equally, do the same with pages
        PagesScan::with(['chapter', 'chapter.serie'])->chunk(100, function ($pages) use ($pageCallback) {
            foreach ($pages as $page) {
                $library_id = $page->chapter->serie->library_id;
                $context = new ExecutionContext($library_id, Storage::disk($library_id), $this);

                if ($this->processPage($context, $page)) {
                    $pageCallback($page);
                } else {
                    // page could not be processed, that means whatever was supporting it is not there
                    // (either a file/folder went missing or the processor is not supporting it anymore)
                    $page->delete();
                }
            }
        });
    }

    public function process(ExecutionContext $context): bool
    {
        foreach ($this->instances as $processor) {
            if (! $processor->processable($context, '/')) {
                continue;
            }

            if ($processor->process($context)) {
                return true;
            }
        }

        Log::error('Could not process the filesystem '.$context->uuid.': no suitable processor found');

        return false;
    }

    public function processSeries(ExecutionContext $context, SeriesScan $serie): bool
    {
        foreach ($this->instances as $processor) {
            if (! $processor->processable($context, $serie->basepath)) {
                continue;
            }

            if ($processor->serie($context, $serie)) {
                return true;
            }
        }

        Log::error('Could not process series on '.$context->uuid.'://'.$serie->basepath);

        return false;
    }

    public function processChapter(ExecutionContext $context, ChaptersScan $chapter): bool
    {
        foreach ($this->instances as $processor) {
            if (! $processor->processable($context, $chapter->basepath)) {
                continue;
            }

            if ($processor->chapter($context, $chapter)) {
                return true;
            }
        }

        Log::error('Could not process chapters on '.$context->uuid.'://'.$chapter->basepath);

        return false;
    }

    public function processPage(ExecutionContext $context, PagesScan $page): bool
    {
        foreach ($this->instances as $processor) {
            if (! $processor->processable($context, $page->path)) {
                continue;
            }

            if ($processor->page($context, $page)) {
                return true;
            }
        }

        Log::error('Could not process pages on '.$context->uuid.'://'.$page->path);

        return false;
    }
}
