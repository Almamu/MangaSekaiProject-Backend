<?php

namespace App\Media\Storage;

readonly class ParsedPath
{
    public function __construct(
        public string $disk,
        public string $container,
        public string $path
    ) {}

    public function __toString(): string
    {
        $result = '';

        if (strlen($this->disk) > 0) {
            $result = $this->disk.'://';
        }

        if ($this->hasContainer()) {
            $result .= $this->container;

            if (strlen($this->path) > 0) {
                $result .= ':';
            }
        }

        if (strlen($this->path) > 0) {
            $result .= $this->path;
        }

        return $result;
    }

    public function hasContainer(): bool
    {
        return strlen($this->container) > 0;
    }
}
