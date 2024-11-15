<?php

namespace App\Scanner\FileSystem\Processors;

use App\Scanner\Exception\UnprocessableEntityException;
use App\Scanner\FileSystem\Data\ChapterData;
use App\Scanner\FileSystem\Data\PageData;
use App\Scanner\FileSystem\Data\SerieData;
use App\Scanner\FileSystem\Scanner;

class FolderProcessor implements Processor
{
    public static function processable(string $path): bool
    {
        return file_exists($path) && is_dir($path);
    }

    public static function process(Scanner $scanner, string $path): array
    {
        $dir = opendir($path);
        $series = [];

        if (! $dir) {
            throw new UnprocessableEntityException('Unable to open directory');
        }

        while (($entry = readdir($dir)) !== false) {
            if ($entry == '.' || $entry == '..') {
                continue;
            }

            $seriepath = realpath($path.'/'.$entry);

            if (! $seriepath) {
                continue;
            }

            $series[$entry] = $scanner->processSeries($seriepath);
        }

        return $series;
    }

    public static function serie(Scanner $scanner, string $path): SerieData
    {
        $dir = opendir($path);
        /** @var array<string, ChapterData> $chapters */
        $chapters = [];

        if (! $dir) {
            throw new UnprocessableEntityException('Unable to open directory');
        }

        while (($entry = readdir($dir)) !== false) {
            if ($entry == '.' || $entry == '..') {
                continue;
            }

            $chapterpath = realpath($path.'/'.$entry);

            if (! $chapterpath) {
                continue;
            }

            // extract number of chapter from the entry name
            if (preg_match('/[0-9.]+/', $entry, $matches) == 0) {
                continue;
            }
            /** @var string $number */
            $number = (string) ((float) reset($matches));

            $chapters[$number] = $scanner->processChapter($chapterpath, $number);
        }

        return new SerieData($path, basename($path), $chapters);
    }

    public static function chapter(Scanner $scanner, string $path, string $number): ChapterData
    {
        $dir = opendir($path);
        $pages = [];

        if (! $dir) {
            throw new UnprocessableEntityException('Unable to open directory');
        }

        while (($entry = readdir($dir)) !== false) {
            if ($entry == '.' || $entry == '..') {
                continue;
            }

            $pagepath = realpath($path.'/'.$entry);

            if (! $pagepath) {
                continue;
            }

            if (preg_match_all('/[0-9]+/', $entry, $matches) == 0) {
                continue;
            }

            if (count($matches[0]) == 0) {
                continue;
            }

            $pageNumber = (int) reset($matches[0]);
            $pages[(int) end($matches[0])] = new PageData($pageNumber, $pagepath);
        }

        return new ChapterData($number, $pages);
    }
}
