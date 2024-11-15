<?php

namespace App\Media;

class AuthorMatch
{
    public function __construct(
        public string $role,
        public string $name,
        public string $image,
        public string $description,
    ) {}
}
