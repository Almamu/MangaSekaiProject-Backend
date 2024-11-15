<?php

namespace App\Scanner\FileSystem;

use App\Models\Chapter;
use App\Models\Page;
use App\Models\Serie;
use App\Models\Settings;
use App\Scanner\Exception\UnprocessableEntityException;
use App\Scanner\FileSystem\Data\ChapterData;
use App\Scanner\FileSystem\Data\SerieData;
use App\Scanner\FileSystem\Processors\FolderProcessor;
use App\Scanner\FileSystem\Processors\Processor;
use App\Scanner\FileSystem\Processors\ZipProcessor;
use App\Scanner\Scanner as ScannerInterface;

class Scanner implements ScannerInterface
{
    /**
     * @var array<class-string<Processor>>
     */
    private array $processors = [
        ZipProcessor::class,
        FolderProcessor::class,
    ];

    public function scan(): void
    {
        // TODO: PHPSTAN IS ADAMANT THIS IS A STRING EVEN THO THE TYPEHINTING SAYS ARRAY, WHAT GIVES?
        /** @var array<string> $folders */
        $folders = Settings::getScannerDirs()->value;
        $series = [];

        if (! is_array($folders)) {
            return;
        }

        // TODO: OPTIMIZE THIS, HAVING EVERYTHING IN MEMORY IS NOT THE BEST OF THE IDEAS
        // TODO: THINGS SHOULD GO INTO THE DATABASE AS SOON AS THEY ARE DISCOVERED
        // TODO: (AT LEAST AT THE CHAPTER LEVEL)
        // TODO: THAT MEANS A BETTER WAY OF FIGURING OUT WHAT TO REMOVE OFF THE DATABASE
        // TODO: WILL BE A CHALLENGE TOO
        // TODO: IF POSSIBLE NOTHING TO DO WITH A FIELD MARKING WHAT WAS UPDATED AND WHAT WASN'T
        // TODO: AS THAT CAN BACKFIRE GREATLY AND EASILY
        foreach ($folders as $folder) {
            // TODO: HANDLE THIS EXCEPTION
            $series[] = $this->process($folder);
        }

        // merge all the series groups into one
        $series = $this->dirtyMergeSeries(...$series);

        // create series
        foreach ($series as $name => $serie) {
            // TODO: MAYBE CREATE ALL THE ENTRIES TO THE DATABASE AT THE SAME TIME?
            $serieEntry = Serie::findOrCreate($name);

            // create chapter entries
            foreach ($serie->chapters as $number => $chapter) {
                // get a reference to the chapter if it doesn't exist in the database yet
                // this also updates the record
                // TODO: MAYBE CREATE ALL THE ENTRIES TO THE DATABASE AT THE SAME TIME?
                $chapterEntry = Chapter::updateOrCreate($serieEntry, $number, count($chapter->files));

                // TODO: IMPROVE THIS, THE LOGIC IS VERY BASIC BUT SHOULD SUFFICE FOR NOW
                // TODO: THIS IS HOW IT WAS DONE ON THE OLD APP
                Page::where('chapter_id', '=', $chapterEntry->id)->delete();

                $newPages = [];

                foreach ($chapter->files as $pageNumber => $path) {
                    $newPages[] = [
                        'chapter_id' => $chapterEntry->id,
                        'number' => $pageNumber,
                        'path' => $path,
                    ];
                }

                // batch insert them
                Page::insert($newPages);
            }

            // TODO: SAME DEAL AS THE ONE ABOVE, THIS LOGIC IS VERY BASIC BUT SHOULD SUFFICE FOR NOW
            // TODO: THIS IS HOW IT WAS DONE ON THE OLD APP AND SHOULD BE IMPROVED
            // remove chapters no longer present
            Chapter::where('serie_id', '=', $serieEntry->id)
                ->whereNotIn('number', array_keys($serie->chapters))
                ->delete();
            // TODO: UPDATE COUNTERS ALL AT THE SAME TIME INSTEAD OF DOING IT HERE AS WE WERE DOING BEFORE
        }

        // TODO: IS THERE A BETTER WAY OF DOING THIS?
        // TODO: THIS REMOVES ALL MANGAS THAT DO NOT MATCH KEYS STORED
        // TODO: WHAT ABOUT PERFORMANCE? CHECK THIS
        Serie::whereNotIn('name', array_keys($series))->delete();

        // yes, I am aware, this has more comments than actual code, but this is a seriously naive implementation
        // that helps us get out there first, but is going to be a pain point in the future
    }

    /**
     * @param  array<string, SerieData>  ...$arrays
     * @return array<string, SerieData>
     */
    private function dirtyMergeSeries(array ...$arrays): array
    {
        $result = [];

        foreach ($arrays as $array) {
            foreach ($array as $name => $serie) {
                if (! array_key_exists($name, $result)) {
                    $result[$name] = $serie;

                    continue;
                }

                // perform the bad merge operations
                $result[$name]->chapters = $this->dirtyMergeChapters($serie->chapters, $result[$name]->chapters);
            }
        }

        return $result;
    }

    /**
     * @param  array<string, ChapterData>  ...$arrays
     * @return array<string, ChapterData>
     */
    private function dirtyMergeChapters(array ...$arrays): array
    {
        $result = [];

        foreach ($arrays as $array) {
            foreach ($array as $number => $chapter) {
                if (! array_key_exists($number, $result)) {
                    $result[$number] = $chapter;

                    continue;
                }

                $result[$number]->files = array_merge($result[$number]->files, $chapter->files);
            }
        }

        return $result;
    }

    /**
     * @return array<string, SerieData>
     *
     * @throws UnprocessableEntityException
     */
    public function process(string $path): array
    {
        foreach ($this->processors as $processor) {
            if (! $processor::processable($path)) {
                continue;
            }

            return $processor::process($this, $path);
        }

        throw new UnprocessableEntityException('Could not process the series at '.$path.': no suitable processor found');
    }

    /**
     * @throws UnprocessableEntityException
     */
    public function processSeries(string $path): SerieData
    {
        foreach ($this->processors as $processor) {
            if (! $processor::processable($path)) {
                continue;
            }

            return $processor::serie($this, $path);
        }

        throw new UnprocessableEntityException('Could not process the series at '.$path.': no suitable processor found');
    }

    /**
     * @throws UnprocessableEntityException
     */
    public function processChapter(string $path, string $number): ChapterData
    {
        foreach ($this->processors as $processor) {
            if (! $processor::processable($path)) {
                continue;
            }

            return $processor::chapter($this, $path, $number);
        }

        throw new UnprocessableEntityException('Could not process the series at '.$path.': no suitable processor found');
    }
}
