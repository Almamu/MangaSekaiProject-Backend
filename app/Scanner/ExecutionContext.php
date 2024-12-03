<?php

namespace App\Scanner;

use Illuminate\Contracts\Filesystem\Filesystem;

class ExecutionContext
{
    public function __construct(
        public string $uuid,
        public Filesystem $filesystem,
        public Scanner $scanner
    ) {}
}
