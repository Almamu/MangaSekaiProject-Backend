<?php

namespace App;

use App\Models\Settings;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Filesystem\Exception\InvalidArgumentException;

class ScannerDirs
{
    /**
     * @var array<string> List of storages registered in laravel from the settings
     */
    public static array $dirs = [];

    /**
     * Sets up laravel storages based off the current settings
     */
    public static function setup(): void
    {
        $folders = self::get();
        self::$dirs = [];

        // register storages for the app to use
        foreach ($folders as $folder) {
            Storage::set($folder['uuid'], Storage::build($folder['config']));
            self::$dirs[] = $folder['uuid'];
        }
    }

    /**
     * @return array<array{uuid: string, config: array<string, mixed>}> $folders
     */
    public static function get(): array
    {
        return Settings::getScannerDirs()->value;
    }

    public static function storage(string $path): \Illuminate\Contracts\Filesystem\Filesystem
    {
        $protocol = parse_url($path, PHP_URL_SCHEME);

        if (! is_string($protocol)) {
            throw new InvalidArgumentException('Invalid path provided');
        }

        return Storage::disk($protocol);
    }

    public static function path(string $path): string
    {
        $parsed = parse_url($path);

        if (! is_array($parsed) || ! array_key_exists('host', $parsed)) {
            throw new InvalidArgumentException('Invalid path provided');
        }

        return $parsed['host'].($parsed['path'] ?? '');
    }

    public static function size(string $path): int
    {
        $storage = self::storage($path);
        $path = self::path($path);

        return $storage->size($path);
    }

    public static function exists(string $path): bool
    {
        $storage = self::storage($path);
        $path = self::path($path);

        return $storage->exists($path);
    }

    public static function delete(string $path): void
    {
        $storage = self::storage($path);
        $path = self::path($path);

        $storage->delete($path);
    }
}
