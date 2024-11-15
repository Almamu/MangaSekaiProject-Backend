<?php

namespace App\Scanner\FileSystem\Data;

class PageData
{
    public function __construct(
        public int $number,
        public string $path
    ) {}
}
