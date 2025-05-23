<?php

namespace App\Media\Storage;

use App\Media\Storage\Exceptions\CannotReadFileException;
use App\Media\Storage\Handlers\Handler;
use App\Models\Settings;
use Illuminate\Contracts\Foundation\Application;
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
     * @var Handler[] List of media handlers to read images
     */
    private array $instances = [];

    /**
     * @param  class-string<Handler>[]  $handlers
     */
    public function __construct(
        private readonly array $handlers,
        private readonly Application $app,
        private readonly \Illuminate\Filesystem\FilesystemManager $filesystemManager,
    ) {
    }

    private function initialize(): void
    {
        if ($this->initialized) {
            return;
        }

        // @phpstan-ignore assign.propertyType (For some reason PHPStan doesn't properly detect types even tho they match)
        $this->instances = array_map(fn($x) => $this->app->make($x), $this->handlers);

        /** @var array<array{uuid: string, config: array<string, mixed>}> $folders */
        $folders = Settings::getScannerDirs()->value;

        foreach ($folders as $folder) {
            $this->filesystemManager->set($folder['uuid'], $this->filesystemManager->build($folder['config']));
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
     * @param  callable(resource): void  $callback  Function called with the valid resource to be used by the app
     *
     * @throws CannotReadFileException
     */
    public function open(ParsedPath|string $path, callable $callback): void
    {
        $this->initialize();

        if (!($path instanceof ParsedPath)) {
            $path = $this->path($path);
        }

        foreach ($this->instances as $handler) {
            if ($handler->providable($path)) {
                $handler->provide($path, $callback);

                return;
            }
        }

        throw new CannotReadFileException($path);
    }

    /**
     * @param  ParsedPath|string  $path  The full path of a file or the name of the disk to obtain
     */
    public function storage(ParsedPath|string $path): \Illuminate\Contracts\Filesystem\Filesystem
    {
        $this->initialize();
        if ($path instanceof ParsedPath) {
            return $this->filesystemManager->disk($path->disk);
        }

        if (str_contains($path, '://')) {
            return $this->filesystemManager->disk(self::path($path)->disk);
        }

        return $this->filesystemManager->disk($path);
    }

    /**
     * Parses the given path and removes the disk part off it
     */
    public static function path(string $path): ParsedPath
    {
        throw_unless(str_contains($path, '://'), new InvalidArgumentException('Invalid path provided'));

        // paths with a semicolon can be a bit problematic, so extract the second part
        $path = explode(':', $path);

        throw_if(count($path) < 2 || count($path) > 3, new InvalidArgumentException('Invalid path provided'));

        $disk = array_shift($path);
        $container = array_shift($path);
        $path = array_shift($path);

        // remove the double forward slash
        if (!is_null($container)) {
            $container = substr($container, 2);
        }

        if (!is_null($path)) {
            // path points inside a container, return the appropiate object
            return new ParsedPath($disk, $container ?? '', $path);
        }

        // the path doesn't point inside a container, make sure to reflect that
        return new ParsedPath($disk, '', $container ?? '');
    }
}
