<?php

namespace App\Media\Matcher\Sources;

use App\Media\Matcher\Data\SeriesMatch;

interface Source
{
    /**
     * Searches for any media that matches the given search criteria
     *
     *
     * @return SeriesMatch[]
     */
    public function match(string $search): array;
}
