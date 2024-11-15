<?php

namespace App\Scanner\FileSystem\Processors;

use App\Scanner\Exception\UnprocessableEntityException;
use App\Scanner\FileSystem\Data\ChapterData;
use App\Scanner\FileSystem\Data\PageData;
use App\Scanner\FileSystem\Data\SerieData;
use App\Scanner\FileSystem\Scanner;

class ZipProcessor implements Processor
{
    public static function processable(string $path): bool
    {
        return file_exists($path) && ! is_dir($path) && pathinfo($path, PATHINFO_EXTENSION) === 'zip';
    }

    public static function process(Scanner $scanner, string $path): array
    {
        throw new UnprocessableEntityException('Zip files are not supported as root of libraries');
    }

    public static function serie(Scanner $scanner, string $path): SerieData
    {
        /** @var array<string, ChapterData> $chapters */
        $chapters = [];

        // open the zip file
        $zip = new \ZipArchive;
        $zip->open($path);

        $count = $zip->count();

        // get the number of files available on the zip and iterate through them
        for ($i = 0; $i < $count; $i++) {
            $entry = $zip->getNameIndex($i);

            if (! $entry) {
                continue;
            }

            // ignore __MACOSX entries
            if (str_starts_with($entry, '__MACOSX/')) {
                continue;
            }

            if (preg_match_all('/[0-9]+/', $entry, $matches) < 2) {
                continue;
            }

            /** @var string $chapterNumber */
            $chapterNumber = (string) ((float) $matches[0][0]);
            $pageNumber = (int) end($matches[0]);

            if (array_key_exists($chapterNumber, $chapters) === false) {
                $chapters[$chapterNumber] = new ChapterData($chapterNumber, []);
            }

            $chapters[$chapterNumber]->files[$pageNumber] = new PageData($pageNumber, realpath($path).':/'.$entry);
        }

        $zip->close();

        return new SerieData($path, basename($path), $chapters);
    }

    public static function chapter(Scanner $scanner, string $path, string $number): ChapterData
    {
        $pages = [];
        $zip = new \ZipArchive;
        $zip->open($path);

        // get the number of files available on the zip and iterate through them
        $count = $zip->count();

        for ($i = 0; $i < $count; $i++) {
            $entry = $zip->getNameIndex($i);

            if (! $entry) {
                continue;
            }

            // ignore __MACOSX entries
            if (strpos($entry, '__MACOSX/') === 0) {
                continue;
            }
            if (preg_match_all('/[0-9]+/', $entry, $matches) == 0) {
                continue;
            }
            if (count($matches[0]) == 0) {
                continue;
            }

            $pageNumber = (int) end($matches[0]);
            $pages[$pageNumber] = new PageData($pageNumber, realpath($path).':/'.$entry);
        }

        $zip->close();

        return new ChapterData($number, $pages);
    }
}
