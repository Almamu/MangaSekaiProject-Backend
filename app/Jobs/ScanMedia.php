<?php

namespace App\Jobs;

use App\Media\Matcher\Matcher;
use App\Media\Scanner\Scanner;
use App\Models\Chapter;
use App\Models\ChaptersScan;
use App\Models\CoverDownloadQueue;
use App\Models\Page;
use App\Models\PagesScan;
use App\Models\Serie;
use App\Models\SeriesScan;
use App\Models\Staff;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\ThrottlesExceptions;
use Illuminate\Queue\Middleware\WithoutOverlapping;

class ScanMedia implements ShouldQueue
{
    use Queueable;
    public function __construct(
        private readonly \Illuminate\Contracts\Config\Repository $repository,
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(
        Matcher $matcher,
        Scanner $scanner,
        \Illuminate\Log\LogManager $logManager,
        \Illuminate\Database\DatabaseManager $databaseManager,
    ): void {
        $logManager->info('Starting up media scanner');

        $databaseManager->transaction(function () use ($scanner, $matcher): void {
            // delete all pages off the database so they can be re-created
            Page::query()->delete();

            // next is to discover pages for all these chapters
            $scanner->scan(
                function (SeriesScan $serie) use ($matcher): void {
                    if (!is_null($serie->serie_id)) {
                        return;
                    }

                    $extensionless = pathinfo(basename($serie->basepath), PATHINFO_FILENAME);

                    $results = $matcher->match($serie->basepath);

                    // if no results were found try again without extension
                    // it'd be good if matchers supported multiple criterias
                    if ($results === [] && $extensionless !== $serie->basepath) {
                        $results = $matcher->match($extensionless);
                    }

                    if ($results !== []) {
                        // use topmost result to create data
                        $result = $results[0];

                        // TODO: RESPECT BLOCK FIELDS
                        /** @var Serie $newSerie */
                        $newSerie = Serie::updateOrCreate(
                            [
                                'external_id' => $result->external_id,
                            ],
                            [
                                'matcher' => $result->matcher,
                                'name' => $result->name,
                                'description' => $result->description,
                                'synced' => true,
                            ],
                        );

                        if (trim($result->cover) !== '' || !is_null($newSerie->image)) {
                            CoverDownloadQueue::updateOrInsert(
                                [
                                    'serie_id' => $newSerie->id,
                                ],
                                [
                                    'type' => 'serie',
                                    'url' => $result->cover,
                                ],
                            );
                        }

                        // add staff to the database too
                        foreach ($result->authors as $staff) {
                            /** @var Staff $newStaff */
                            $newStaff = Staff::updateOrCreate(
                                [
                                    'external_id' => $staff->external_id,
                                ],
                                [
                                    'matcher' => $result->matcher,
                                    'name' => $staff->name,
                                    'description' => $staff->description,
                                ],
                            );
                            if (trim($staff->image) === '') {
                                continue;
                            }

                            if (!is_null($newStaff->image)) {
                                continue;
                            }

                            // add the join entry for the staff if it doesn't exist too
                            $newStaff->series()->newPivotQuery()->updateOrInsert(
                                [
                                    'staff_id' => $newStaff->id,
                                    'serie_id' => $newSerie->id,
                                ],
                                [
                                    'role' => $staff->role,
                                ],
                            );

                            CoverDownloadQueue::updateOrInsert(
                                [
                                    'staff_id' => $newStaff->id,
                                ],
                                [
                                    'type' => 'staff',
                                    'url' => $staff->image,
                                ],
                            );
                        }
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
                function (ChaptersScan $chapter): void {
                    // no need to do anything for chapters already recorded in the database
                    if (!is_null($chapter->chapter_id)) {
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
                function (PagesScan $page): void {
                    // extract number of chapter from the entry name
                    if (preg_match('/\d+.?\d*/', basename($page->path), $matches) == 0) {
                        return;
                    }

                    $number = (int) reset($matches);
                    Page::insert([
                        'number' => $number,
                        'path' => $page->chapter->serie->library_id . '://' . $page->path,
                        'chapter_id' => $page->chapter->chapter_id,
                        'mime_type' => $page->mime_type,
                    ]);
                },
            );
        });

        // finally update all counters
        Chapter::query()->update([
            'pages_count' => $databaseManager->raw('(' . Chapter::query()
                ->select([])
                ->withCount('pages')
                ->toRawSql() . ')'),
        ]);

        // finally update chapter count and page count
        Serie::query()->update([
            // TODO: ISN'T THIS A BIT DIRTY? SHOULDN'T WE BE ABLE TO RUN THIS EASILY?
            'chapter_count' => $databaseManager->raw('(' . Serie::query()
                ->select([])
                ->withCount('chapters')
                ->toRawSql() . ')'),
            'pages_count' => $databaseManager->raw('(' . Serie::query()
                ->select([])
                ->withSum('chapters', 'pages_count')
                ->toRawSql() . ')'),
        ]);

        // queue the next job that will sync the covers
        DownloadResources::dispatch();
    }

    /**
     * @return array<WithoutOverlapping|ThrottlesExceptions>
     */
    public function middleware(): array
    {
        // tests use this job to run the full import job
        // but throttling the exceptions prevents them for being reported while running as dispatchSync
        // so act a bit special on testing environments and do not include that middleware
        if ($this->repository->get('app.env') === 'testing') {
            return [
                new WithoutOverlapping('scan-media'),
            ];
        }

        // @codeCoverageIgnoreStart
        return [
            new WithoutOverlapping('scan-media'),
            new ThrottlesExceptions(3, 5 * 60)->backoff(5),
        ];

        // @codeCoverageIgnoreEnd
    }
}
