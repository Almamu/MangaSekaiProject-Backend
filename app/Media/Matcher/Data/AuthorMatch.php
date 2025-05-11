<?php

namespace App\Media\Matcher\Data;

class AuthorMatch
{
    public function __construct(
        public int $external_id,
        public string $role,
        public string $name,
        public string $image,
        public string $description,
    ) {
    }
}
