<?php

namespace App\Media\Matcher\Data;

class SeriesMatch
{
    public function __construct(
        /** @var int The external ID of the matcher */
        public int $external_id,
        /** @var string The matcher used to match this series */
        public string $matcher,
        /** @var string Visible name for the match */
        public string $name,
        /** @var string URL for the manga's cover */
        public string $cover,
        /** @var string Description for the manga */
        public string $description,
        /** @var string[] List of genres */
        public array $genres,
        /** @var int Average score */
        public int $score,
        /** @var string Start date */
        public string $start,
        /** @var string End date */
        public string $end,
        /** @var AuthorMatch[] Extra info for the matcher that found this manga */
        public array $extrainfo,
    ) {}
}
