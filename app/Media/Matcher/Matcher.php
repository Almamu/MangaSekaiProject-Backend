<?php

namespace App\Media\Matcher;

use App\Media\Matcher\Data\SeriesMatch;
use App\Media\Matcher\Sources\Source;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Foundation\Application;

class Matcher
{
    /** @var Source[] Instances of metadata sources */
    private array $instances = [];

    /**
     * @param  class-string<Source>[]  $sources
     *
     * @throws BindingResolutionException
     */
    public function __construct(
        private readonly array $sources,
        Application $app,
        private readonly \Illuminate\Log\LogManager $logManager,
    ) {
        // @phpstan-ignore assign.propertyType (For some reason PHPStan doesn't properly detect types even tho they match)
        $this->instances = array_map(fn($x) => $app->make($x), $this->sources);
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

            if (count($result) === 0) {
                continue;
            }

            return $result;
        }

        $this->logManager->info('No results found for "' . $search . '"');

        return [];
    }
}
