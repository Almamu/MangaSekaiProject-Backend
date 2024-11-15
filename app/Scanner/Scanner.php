<?php

namespace App\Scanner;

/**
 * Base interface for any manga source scanner
 */
interface Scanner
{
    public function scan(): void;
}
