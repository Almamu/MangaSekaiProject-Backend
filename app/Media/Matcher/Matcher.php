<?php

namespace App\Media\Matcher;

use App\Media\Matcher\Data\SeriesMatch;
use App\Media\Matcher\Sources\Source;
use Illuminate\Support\Facades\Log;

class Matcher
{
    /** @var Source[] Instances of metadata sources */
    private array $instances = [];

    /**
     * @param  class-string<Source>[]  $sources
     */
    public function __construct(
        private readonly array $sources
    ) {
        $this->instances = array_map(fn ($x) => new $x, $this->sources);
    }

    /**
     * Searches for any media that matches the given search criteria
     *
     *
     * @return SeriesMatch[]
     */
    public function match(string $search): array
    {
        foreach ($this->instances as $instance) {
            $result = $instance->match($search);

            if (! is_array($result) || count($result) === 0) {
                continue;
            }

            return $result;
        }

        Log::info('No results found for "'.$search.'"');

        return [];
    }
}
