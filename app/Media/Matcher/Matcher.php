<?php

namespace App\Media\Matcher;

interface Matcher
{
    /**
     * Searches for any media that matches the given search criteria
     *
     *
     * @return SeriesMatch[]
     */
    public function match(string $search): array;
}
