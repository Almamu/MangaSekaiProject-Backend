<?php

namespace App\Media\Storage;

use App\Models\Settings;
use Illuminate\Support\Facades\Storage as LaravelStorage;
use Symfony\Component\Filesystem\Exception\InvalidArgumentException;

class Storage
{
    /**
     * @var array<string> List of storages registered in laravel from the settings
     */
    private array $disks = [];

    /**
     * @var bool Controls whether the storage controller was initialized or not
     */
    private bool $initialized = false;

    /**
     * @param  class-string[]  $handlers
     */
    // @phpstan-ignore-next-line
    public function __construct(private readonly array $handlers = []) {}

    private function initialize(): void
    {
        if ($this->initialized) {
            return;
        }

        /** @var array<array{uuid: string, config: array<string, mixed>}> $folders */
        $folders = Settings::getScannerDirs()->value;

        foreach ($folders as $folder) {
            LaravelStorage::set($folder['uuid'], LaravelStorage::build($folder['config']));
            $this->disks[] = $folder['uuid'];
        }

        $this->initialized = true;
    }

    /**
     * @return string[] Disk names registered
     */
    public function getDisks(): array
    {
        $this->initialize();

        return $this->disks;
    }

    /**
     * Parses the given path and provides access to the correct Filesystem
     */
    public function storage(string $path): \Illuminate\Contracts\Filesystem\Filesystem
    {
        $this->initialize();

        $protocol = parse_url($path, PHP_URL_SCHEME);

        if (! is_string($protocol)) {
            throw new InvalidArgumentException('Invalid path provided');
        }

        return LaravelStorage::disk($protocol);
    }

    public static function path(string $path): string
    {
        $parsed = parse_url($path);

        if (! is_array($parsed) || ! array_key_exists('host', $parsed)) {
            throw new InvalidArgumentException('Invalid path provided');
        }

        return $parsed['host'].($parsed['path'] ?? '');
    }
}
