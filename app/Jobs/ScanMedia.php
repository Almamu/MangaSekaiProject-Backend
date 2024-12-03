<?php

namespace App\Jobs;

use App\Media\AniList\Matcher;
use App\Media\Scanner\Scanner;
use App\Models\Chapter;
use App\Models\Page;
use App\Models\Serie;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\ThrottlesExceptions;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
    public function handle(Scanner $scanner): void
    {
        Log::info('Starting up media scanner');

        // TODO: SUPPORT MULTIPLE MATCHERS

        DB::transaction(function () use ($scanner) {
            // delete all pages off the database so they can be re-created
            Page::query()->delete();

            // next is to discover pages for all these chapters
            $scanner->scan(
                function ($serie) {
                    if (! is_null($serie->serie_id)) {
                        return;
                    }

                    $matcher = new Matcher;

                    $extensionless = pathinfo(basename($serie->basepath), PATHINFO_FILENAME);

                    $results = $matcher->match($serie->basepath);

                    // if no results were found try again without extension
                    // it'd be good if matchers supported multiple criterias
                    if (count($results) === 0) {
                        $results = $matcher->match($extensionless);
                    }

                    if (count($results) > 0) {
                        // use topmost result to create data
                        $result = $results[0];

                        // TODO: RESPECT BLOCK FIELDS
                        $newSerie = Serie::updateOrCreate([
                            'external_id' => $result->external_id,
                        ], [
                            'matcher' => 'anilist',
                            'name' => $result->name,
                            'description' => $result->description,
                            'synced' => true,
                        ]);

                        // TODO: STORE IMAGE PATH TO SYNC
                    } else {
                        // no match, create an empty serie with the folder name so the user can update it manually
                        $newSerie = Serie::create([
                            'synced' => false,
                            'name' => $serie->basepath,
                            'description' => '',
                        ]);
                    }

                    $serie->serie_id = $newSerie->id;
                    $serie->save();
                },
                function ($chapter) {
                    // no need to do anything for chapters already recorded in the database
                    if (! is_null($chapter->chapter_id)) {
                        return;
                    }

                    // extract number of chapter from the entry name
                    if (preg_match('/[0-9.]+/', basename($chapter->basepath), $matches) == 0) {
                        return;
                    }

                    if (is_null($chapter->serie->serie_id)) {
                        return;
                    }

                    $number = (string) ((float) reset($matches));

                    $newChapter = Chapter::updateOrCreate($chapter->serie->serie_id, $number, 0);

                    // update record with the chapter_id
                    $chapter->chapter_id = $newChapter->id;
                    $chapter->save();
                },
                function ($page) {
                    // extract number of chapter from the entry name
                    if (preg_match('/[0-9.]+/', basename($page->path), $matches) == 0) {
                        return;
                    }

                    $number = (int) reset($matches);
                    Page::insert([
                        'number' => $number,
                        'path' => $page->chapter->serie->library_id.'://'.$page->path,
                        'chapter_id' => $page->chapter->chapter_id,
                        'mime_type' => $page->mime_type,
                    ]);
                }
            );

        });

        // finally update all counters
        Chapter::query()->update([
            'pages_count' => DB::raw('('.Chapter::query()->select([])->withCount('pages')->toRawSql().')'),
        ]);

        // finally update chapter count and page count
        Serie::query()->update([
            // TODO: ISN'T THIS A BIT DIRTY? SHOULDN'T WE BE ABLE TO RUN THIS EASILY?
            'chapter_count' => DB::raw('('.Serie::query()->select([])->withCount('chapters')->toRawSql().')'),
            'pages_count' => DB::raw('('.Serie::query()->select([])->withSum('chapters', 'pages_count')->toRawSql().')'),
        ]);
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
