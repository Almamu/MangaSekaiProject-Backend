<?php

namespace App\Media\Storage\Handlers;

use App\Media\Storage\Exceptions\CannotReadFileException;
use App\Media\Storage\ParsedPath;
use App\Media\Storage\Storage;

readonly class ZipHandler implements Handler
{
    public function __construct(private Storage $storage) {}

    public function providable(ParsedPath $path): bool
    {
        // zip are container paths, so no container means not possible to read
        if ($path->hasContainer() === false) {
            return false;
        }

        // ensure the extension matches too
        if (pathinfo($path->container, PATHINFO_EXTENSION) !== 'zip') {
            return false;
        }

        return true;
    }

    public function provide(ParsedPath $path, callable $callback): void
    {
        $disk = $this->storage->storage($path);

        $stream = $disk->readStream($path->container);

        if (is_null($stream)) {
            throw new CannotReadFileException($path);
        }

        // open the zip file
        $zip = new \PhpZip\ZipFile;
        $zip->openFromStream($stream);
        $entry = $zip->getEntry($path->path);

        if ($entry->isDirectory()) {
            throw new CannotReadFileException($path);
        }

        $data = $entry->getData();

        if (is_null($data)) {
            throw new CannotReadFileException($path);
        }

        $callback($data->getDataAsStream());
    }
}
