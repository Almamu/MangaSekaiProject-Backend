<?php

namespace App\Media\Storage\Exceptions;

use App\Media\Storage\ParsedPath;

class CannotReadFileException extends \Exception
{
    public function __construct(ParsedPath|string $file)
    {
        parent::__construct('Cannot read file ' . $file, 1);
    }
}
