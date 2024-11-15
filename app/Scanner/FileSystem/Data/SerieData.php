<?php

namespace App\Scanner\FileSystem\Data;

class SerieData
{
    /**
     * @param  array<string, ChapterData>  $chapters
     */
    public function __construct(
        public string $fullpath,
        public string $key,
        public array $chapters
    ) {}
}
