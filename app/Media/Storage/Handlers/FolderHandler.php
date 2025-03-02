<?php

namespace App\Media\Storage\Handlers;

use App\Media\Storage\Exceptions\CannotReadFileException;
use App\Media\Storage\ParsedPath;
use App\Media\Storage\Storage;

readonly class FolderHandler implements Handler
{
    public function __construct(private Storage $storage) {}

    public function providable(ParsedPath $path): bool
    {
        // normal paths shouldn't have a container
        if ($path->hasContainer()) {
            return false;
        }

        return $this->storage->storage($path->disk)->exists($path->path);
    }

    public function provide(ParsedPath $path, callable $callback): void
    {
        $stream = $this->storage->storage($path->disk)->readStream($path->path);

        if (is_null($stream)) {
            throw new CannotReadFileException($path);
        }

        $callback($stream);

        fclose($stream);
    }
}
