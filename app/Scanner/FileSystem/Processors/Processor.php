<?php

namespace App\Scanner\FileSystem\Processors;

use App\Scanner\FileSystem\Data\ChapterData;
use App\Scanner\FileSystem\Data\SerieData;
use App\Scanner\FileSystem\Scanner;

interface Processor
{
    /**
     * Checks if the given path can be processed by this processor
     */
    public static function processable(string $path): bool;

    /**
     * Processes the given path in search for content
     *
     * @return array<string, SerieData>
     */
    public static function process(Scanner $scanner, string $path): array;

    /**
     * Processes the given path as a series storage
     */
    public static function serie(Scanner $scanner, string $path): SerieData;

    /**
     * Processes the given path as a chapter storage
     */
    public static function chapter(Scanner $scanner, string $path, string $number): ChapterData;
}
