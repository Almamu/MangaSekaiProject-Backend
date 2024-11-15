<?php

namespace App\Scanner\FileSystem\Data;

class ChapterData
{
    /**
     * @param  array<int, PageData>  $files
     */
    public function __construct(
        public string $number,
        public array $files
    ) {}
}
